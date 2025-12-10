"""
Dedupe Service for Entity Resolution
Uses the dedupe library for probabilistic record linkage with active learning
"""
import os
import json
import logging
import pickle
from pathlib import Path
from typing import Dict, Any, List, Optional, Tuple
from difflib import SequenceMatcher

import dedupe
import numpy as np

logger = logging.getLogger(__name__)


class DedupeService:
    """
    Service for entity resolution using the dedupe library.
    Supports per-tenant-pair model training and inference.
    """

    # Product knowledge base for commodity trading (seed data)
    PRODUCT_KNOWLEDGE_BASE = {
        # Dairy products
        'wpc 80': 'Whey Protein Concentrate 80%',
        'wpc80': 'Whey Protein Concentrate 80%',
        'whey prot. conc. 80': 'Whey Protein Concentrate 80%',
        'whey protein conc 80': 'Whey Protein Concentrate 80%',
        'wpc 34': 'Whey Protein Concentrate 34%',
        'wpc34': 'Whey Protein Concentrate 34%',
        'wpi': 'Whey Protein Isolate 90%',
        'wpi 90': 'Whey Protein Isolate 90%',
        'smp': 'Skimmed Milk Powder',
        'skim milk powder': 'Skimmed Milk Powder',
        'skim milk': 'Skimmed Milk Powder',
        'wmp': 'Whole Milk Powder',
        'whole milk powder': 'Whole Milk Powder',
        'butter 82%': 'Butter 82% Fat',
        'butter82': 'Butter 82% Fat',
        'butter 80%': 'Butter 80% Fat',
        'amf': 'Anhydrous Milk Fat',
        'anhydrous milk fat': 'Anhydrous Milk Fat',
        'lactose': 'Lactose Powder',
        'casein': 'Casein',
        'mpc 80': 'Milk Protein Concentrate 80%',
        'mpc80': 'Milk Protein Concentrate 80%',
        # Oils and fats
        'palm oil': 'Palm Oil',
        'coconut oil': 'Coconut Oil',
        'sunflower oil': 'Sunflower Oil',
        'rapeseed oil': 'Rapeseed Oil',
        'soybean oil': 'Soybean Oil',
        # Grains
        'wheat': 'Wheat',
        'corn': 'Corn/Maize',
        'maize': 'Corn/Maize',
        'barley': 'Barley',
        'oats': 'Oats',
    }

    def __init__(self):
        self.model_path = Path(os.getenv("DEDUPE_MODEL_PATH", "./models"))
        self.model_path.mkdir(parents=True, exist_ok=True)

        self.min_training_samples = int(os.getenv("DEDUPE_MIN_TRAINING_SAMPLES", "50"))
        self.confidence_threshold = float(os.getenv("DEDUPE_CONFIDENCE_THRESHOLD", "0.7"))

        self.models: Dict[str, Any] = {}
        self.gazetteer_cache: Dict[str, dedupe.Gazetteer] = {}

        # Define the fields for dedupe matching
        self.fields = [
            {'field': 'product', 'type': 'String', 'has missing': True},
            {'field': 'supplier', 'type': 'String', 'has missing': True},
        ]

        logger.info(f"DedupeService initialized with model path: {self.model_path}")

    async def resolve_entities(
        self,
        extracted_data: Dict[str, Any],
        source_tenant: str,
        target_tenant: str
    ) -> Dict[str, Any]:
        """
        Resolve entities between source and target tenants.

        Args:
            extracted_data: Extracted schema data
            source_tenant: Source tenant code
            target_tenant: Target tenant code

        Returns:
            Dictionary with mappedData and confidenceScores
        """
        model_key = f"{source_tenant}_{target_tenant}"

        # Try to load trained model, fall back to knowledge base
        model = self._load_model(model_key)

        if model is not None:
            logger.info(f"Using trained dedupe model for {model_key}")
            mapped_data, confidence_scores = await self._dedupe_matching(
                extracted_data, model, model_key
            )
        else:
            logger.info(f"No trained model for {model_key}, using knowledge base")
            mapped_data, confidence_scores = self._knowledge_base_matching(extracted_data)

        return {
            "mappedData": mapped_data,
            "confidenceScores": confidence_scores
        }

    def _load_model(self, model_key: str) -> Optional[dedupe.Gazetteer]:
        """Load a trained dedupe model from disk."""
        if model_key in self.gazetteer_cache:
            return self.gazetteer_cache[model_key]

        model_file = self.model_path / f"{model_key}_model.pkl"
        settings_file = self.model_path / f"{model_key}_settings"

        if settings_file.exists():
            try:
                with open(settings_file, 'rb') as f:
                    gazetteer = dedupe.StaticGazetteer(f)

                self.gazetteer_cache[model_key] = gazetteer
                logger.info(f"Loaded trained model for {model_key}")
                return gazetteer
            except Exception as e:
                logger.error(f"Failed to load model {model_key}: {e}")
                return None

        return None

    async def _dedupe_matching(
        self,
        extracted_data: Dict[str, Any],
        model: dedupe.Gazetteer,
        model_key: str
    ) -> Tuple[Dict[str, Any], Dict[str, float]]:
        """
        Use trained dedupe model for entity matching.
        """
        mapped_data = {}
        confidence_scores = {}

        # Prepare the record for matching
        record = {
            'product': extracted_data.get('product', ''),
            'supplier': extracted_data.get('supplier', ''),
        }

        # Match using gazetteer
        try:
            matches = model.search([record], threshold=self.confidence_threshold)

            for field, value in extracted_data.items():
                if field == 'product' and matches:
                    # Get the best match for this record
                    if matches[0]:  # If there are matches for the first (only) record
                        best_match = max(matches[0], key=lambda x: x[1])
                        matched_product, score = best_match
                        mapped_data['product'] = matched_product
                        confidence_scores['product'] = float(score)
                    else:
                        mapped_data['product'] = value
                        confidence_scores['product'] = 0.5
                else:
                    mapped_data[field] = value
                    confidence_scores[field] = 0.98 if value else 0.5

        except Exception as e:
            logger.error(f"Dedupe matching failed: {e}")
            return self._knowledge_base_matching(extracted_data)

        return mapped_data, confidence_scores

    def _knowledge_base_matching(
        self,
        extracted_data: Dict[str, Any]
    ) -> Tuple[Dict[str, Any], Dict[str, float]]:
        """
        Knowledge base matching with fuzzy string matching fallback.
        """
        mapped_data = {}
        confidence_scores = {}

        for field, value in extracted_data.items():
            if field == 'product' and isinstance(value, str):
                matched_product, confidence = self._match_product(value)
                mapped_data[field] = matched_product
                confidence_scores[field] = confidence
            else:
                mapped_data[field] = value
                confidence_scores[field] = 0.98 if value else 0.5

        return mapped_data, confidence_scores

    def _match_product(self, product_name: str) -> Tuple[str, float]:
        """
        Match a product name against the knowledge base.
        Uses exact matching first, then fuzzy matching.
        """
        product_lower = product_name.lower().strip()

        # Try exact match
        if product_lower in self.PRODUCT_KNOWLEDGE_BASE:
            return self.PRODUCT_KNOWLEDGE_BASE[product_lower], 0.98

        # Try fuzzy matching
        best_match = None
        best_score = 0.0

        for known_product, canonical_name in self.PRODUCT_KNOWLEDGE_BASE.items():
            score = SequenceMatcher(None, product_lower, known_product).ratio()
            if score > best_score:
                best_score = score
                best_match = canonical_name

        # Return match if above threshold
        if best_score >= 0.7:
            return best_match, best_score

        # No good match, return original
        return product_name, 0.5

    async def train_model(
        self,
        model_key: str,
        training_data: List[Dict[str, Any]]
    ) -> Dict[str, Any]:
        """
        Train a new dedupe model for a tenant pair.

        Args:
            model_key: Tenant pair identifier (source_target)
            training_data: List of training examples with labeled pairs

        Returns:
            Training result with metrics
        """
        if len(training_data) < self.min_training_samples:
            return {
                "success": False,
                "message": f"Need at least {self.min_training_samples} samples, got {len(training_data)}"
            }

        logger.info(f"Training dedupe model for {model_key} with {len(training_data)} samples")

        try:
            # Create deduper with field definitions
            gazetteer = dedupe.Gazetteer(self.fields)

            # Prepare canonical data (target values)
            canonical_data = {}
            messy_data = {}

            for i, item in enumerate(training_data):
                if 'canonical' in item:
                    canonical_data[f"c_{i}"] = {
                        'product': item['canonical'].get('product', ''),
                        'supplier': item['canonical'].get('supplier', ''),
                    }
                if 'messy' in item:
                    messy_data[f"m_{i}"] = {
                        'product': item['messy'].get('product', ''),
                        'supplier': item['messy'].get('supplier', ''),
                    }

            # Sample and prepare for training
            gazetteer.prepare_training(messy_data, canonical_data)

            # If we have labeled pairs, use them for training
            labeled_pairs = self._extract_labeled_pairs(training_data)
            if labeled_pairs['match'] or labeled_pairs['distinct']:
                gazetteer.mark_pairs(labeled_pairs)

            # Train the model
            gazetteer.train()

            # Save the model
            settings_file = self.model_path / f"{model_key}_settings"
            with open(settings_file, 'wb') as f:
                gazetteer.write_settings(f)

            # Index the canonical data
            gazetteer.index(canonical_data)

            # Cache the model
            self.gazetteer_cache[model_key] = gazetteer

            logger.info(f"Successfully trained model for {model_key}")

            return {
                "success": True,
                "message": f"Model trained with {len(training_data)} samples",
                "model_key": model_key
            }

        except Exception as e:
            logger.error(f"Training failed for {model_key}: {e}")
            return {
                "success": False,
                "message": str(e)
            }

    def _extract_labeled_pairs(
        self,
        training_data: List[Dict[str, Any]]
    ) -> Dict[str, List]:
        """Extract labeled pairs for dedupe training."""
        matches = []
        distinct = []

        for item in training_data:
            if 'messy' in item and 'canonical' in item:
                messy = {
                    'product': item['messy'].get('product', ''),
                    'supplier': item['messy'].get('supplier', ''),
                }
                canonical = {
                    'product': item['canonical'].get('product', ''),
                    'supplier': item['canonical'].get('supplier', ''),
                }

                if item.get('is_match', True):
                    matches.append((messy, canonical))
                else:
                    distinct.append((messy, canonical))

        return {'match': matches, 'distinct': distinct}

    def add_to_knowledge_base(
        self,
        source_value: str,
        canonical_value: str
    ) -> None:
        """
        Add a new mapping to the product knowledge base.
        Used for active learning updates.
        """
        source_lower = source_value.lower().strip()
        if source_lower not in self.PRODUCT_KNOWLEDGE_BASE:
            self.PRODUCT_KNOWLEDGE_BASE[source_lower] = canonical_value
            logger.info(f"Added to knowledge base: {source_value} -> {canonical_value}")

    def get_model_stats(self, model_key: str) -> Dict[str, Any]:
        """Get statistics about a trained model."""
        settings_file = self.model_path / f"{model_key}_settings"

        if not settings_file.exists():
            return {
                "exists": False,
                "model_key": model_key
            }

        stat = settings_file.stat()
        return {
            "exists": True,
            "model_key": model_key,
            "file_size": stat.st_size,
            "last_modified": stat.st_mtime,
            "cached": model_key in self.gazetteer_cache
        }

    def save_model(self, model_key: str, model) -> None:
        """Save a trained dedupe model."""
        settings_file = self.model_path / f"{model_key}_settings"
        with open(settings_file, 'wb') as f:
            model.write_settings(f)
        logger.info(f"Saved model for {model_key}")
