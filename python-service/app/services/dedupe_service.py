"""
Dedupe Service for Entity Resolution
Uses rule-based matching for MVP (dedupe library for future enhancement)
"""
import os
import json
import logging
from typing import Dict, Any

logger = logging.getLogger(__name__)


class DedupeService:
    """Service for entity resolution using dedupe library"""

    def __init__(self):
        self.model_path = os.getenv("DEDUPE_MODEL_PATH", "./models")
        os.makedirs(self.model_path, exist_ok=True)
        self.models = {}

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

        # Load or create dedupe model for this tenant pair
        if model_key not in self.models:
            self.models[model_key] = self._load_or_create_model(model_key)

        # For MVP, use rule-based matching with confidence scores
        # In production, this would use the trained dedupe model
        mapped_data, confidence_scores = self._perform_matching(
            extracted_data,
            source_tenant,
            target_tenant
        )

        return {
            "mappedData": mapped_data,
            "confidenceScores": confidence_scores
        }

    def _load_or_create_model(self, model_key: str):
        """Load existing model or create new one"""
        logger.info(f"Using rule-based matching for: {model_key}")
        # In production, this would load a trained dedupe model
        return None

    def _perform_matching(
        self,
        extracted_data: Dict[str, Any],
        source_tenant: str,
        target_tenant: str
    ) -> tuple:
        """
        Perform entity matching with confidence scoring.

        This is a simplified implementation for MVP.
        In production, this would use the trained dedupe model.
        """
        mapped_data = {}
        confidence_scores = {}

        # Product matching knowledge base (simplified for MVP)
        product_mappings = {
            'WPC 80': 'Whey Protein Concentrate 80%',
            'WPC80': 'Whey Protein Concentrate 80%',
            'Whey Prot. Conc. 80': 'Whey Protein Concentrate 80%',
            'SMP': 'Skimmed Milk Powder',
            'Skim Milk Powder': 'Skimmed Milk Powder',
            'Butter 82%': 'Butter 82% Fat',
            'Butter82': 'Butter 82% Fat',
        }

        for field, value in extracted_data.items():
            if field == 'product' and isinstance(value, str):
                # Perform fuzzy product matching
                matched_product = product_mappings.get(value, value)
                mapped_data[field] = matched_product

                # Calculate confidence score (simplified)
                confidence = 0.95 if value in product_mappings else 0.75
                confidence_scores[field] = confidence
            else:
                # Direct mapping for other fields
                mapped_data[field] = value

                # High confidence for non-product fields
                if value:
                    confidence_scores[field] = 0.98
                else:
                    confidence_scores[field] = 0.5

        return mapped_data, confidence_scores

    def save_model(self, model_key: str, model):
        """Save trained dedupe model"""
        logger.info(f"Model saving not yet implemented for: {model_key}")
        # In production, this would save the trained model
