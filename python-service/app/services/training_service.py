"""
Training Service for Active Learning
Processes user feedback and retrains dedupe models
"""
import os
import json
import logging
from typing import Dict, Any, List, Optional
from datetime import datetime
from pathlib import Path

logger = logging.getLogger(__name__)


class TrainingService:
    """Service for processing feedback and retraining models"""

    def __init__(self, dedupe_service=None):
        self.training_data_path = Path(os.getenv("TRAINING_DATA_PATH", "./training_data"))
        self.training_data_path.mkdir(parents=True, exist_ok=True)

        self.min_samples_for_training = int(os.getenv("DEDUPE_MIN_TRAINING_SAMPLES", "50"))
        self.retrain_threshold = int(os.getenv("RETRAIN_THRESHOLD", "10"))

        self.dedupe_service = dedupe_service

    def set_dedupe_service(self, dedupe_service):
        """Set the dedupe service for model retraining."""
        self.dedupe_service = dedupe_service

    async def process_feedback(
        self,
        source_tenant: str,
        target_tenant: str,
        source_field: str,
        source_value: str,
        target_field: str,
        corrected_value: str
    ) -> bool:
        """
        Process user feedback for active learning.

        Args:
            source_tenant: Source tenant code
            target_tenant: Target tenant code
            source_field: Source field name
            source_value: Original source value
            target_field: Target field name
            corrected_value: User-corrected value

        Returns:
            True if feedback was processed successfully
        """
        try:
            feedback_entry = {
                "timestamp": datetime.utcnow().isoformat(),
                "sourceTenant": source_tenant,
                "targetTenant": target_tenant,
                "sourceField": source_field,
                "sourceValue": source_value,
                "targetField": target_field,
                "correctedValue": corrected_value
            }

            # Save feedback to training data file
            feedback_file = self.training_data_path / f"{source_tenant}_{target_tenant}_feedback.jsonl"

            with open(feedback_file, 'a') as f:
                f.write(json.dumps(feedback_entry) + '\n')

            logger.info(
                f"Feedback saved: {source_tenant} -> {target_tenant}, "
                f"{source_field}:{source_value} = {corrected_value}"
            )

            # Update the knowledge base immediately for product corrections
            if target_field == 'product' and self.dedupe_service:
                self.dedupe_service.add_to_knowledge_base(source_value, corrected_value)

            # Check if we have enough feedback to retrain
            feedback_count = self._count_feedback_entries(feedback_file)
            if feedback_count >= self.retrain_threshold and feedback_count % self.retrain_threshold == 0:
                await self._retrain_model(source_tenant, target_tenant)

            return True

        except Exception as e:
            logger.error(f"Failed to process feedback: {str(e)}", exc_info=True)
            return False

    def _count_feedback_entries(self, feedback_file: Path) -> int:
        """Count number of feedback entries in file"""
        if not feedback_file.exists():
            return 0

        with open(feedback_file, 'r') as f:
            return sum(1 for _ in f)

    def _load_feedback(self, feedback_file: Path) -> List[Dict[str, Any]]:
        """Load all feedback entries from file."""
        entries = []
        if feedback_file.exists():
            with open(feedback_file, 'r') as f:
                for line in f:
                    if line.strip():
                        entries.append(json.loads(line))
        return entries

    async def _retrain_model(self, source_tenant: str, target_tenant: str) -> bool:
        """
        Retrain dedupe model with accumulated feedback.
        """
        logger.info(f"Starting model retraining for {source_tenant} -> {target_tenant}")

        feedback_file = self.training_data_path / f"{source_tenant}_{target_tenant}_feedback.jsonl"
        feedback_entries = self._load_feedback(feedback_file)

        if len(feedback_entries) < self.min_samples_for_training:
            logger.info(
                f"Not enough samples for training: {len(feedback_entries)} < {self.min_samples_for_training}"
            )
            return False

        # Convert feedback to training format
        training_data = self._convert_to_training_format(feedback_entries)

        if not self.dedupe_service:
            logger.warning("No dedupe service available for retraining")
            return False

        # Train the model
        model_key = f"{source_tenant}_{target_tenant}"
        result = await self.dedupe_service.train_model(model_key, training_data)

        if result["success"]:
            logger.info(f"Model retrained successfully for {model_key}")
            # Archive the processed feedback
            await self._archive_feedback(feedback_file, source_tenant, target_tenant)
        else:
            logger.error(f"Model retraining failed: {result['message']}")

        return result["success"]

    def _convert_to_training_format(
        self,
        feedback_entries: List[Dict[str, Any]]
    ) -> List[Dict[str, Any]]:
        """
        Convert feedback entries to dedupe training format.
        Groups by field type and creates messy/canonical pairs.
        """
        training_data = []

        for entry in feedback_entries:
            target_field = entry.get("targetField", "product")

            training_item = {
                "messy": {
                    target_field: entry["sourceValue"]
                },
                "canonical": {
                    target_field: entry["correctedValue"]
                },
                "is_match": True  # User corrections are positive examples
            }

            training_data.append(training_item)

        # Add some synthetic negative examples if we have enough data
        if len(training_data) >= 10:
            training_data.extend(self._generate_negative_examples(feedback_entries))

        return training_data

    def _generate_negative_examples(
        self,
        feedback_entries: List[Dict[str, Any]]
    ) -> List[Dict[str, Any]]:
        """
        Generate negative training examples by pairing non-matching entries.
        """
        negative_examples = []
        unique_values = set()

        for entry in feedback_entries:
            unique_values.add((entry["sourceValue"], entry["correctedValue"]))

        values_list = list(unique_values)

        # Create some non-matching pairs
        for i, (source1, target1) in enumerate(values_list):
            for source2, target2 in values_list[i+1:i+3]:  # Limit pairs
                if target1 != target2:  # Different canonical values
                    negative_examples.append({
                        "messy": {"product": source1},
                        "canonical": {"product": target2},
                        "is_match": False
                    })

        return negative_examples[:len(feedback_entries) // 2]  # Limit negatives

    async def _archive_feedback(
        self,
        feedback_file: Path,
        source_tenant: str,
        target_tenant: str
    ) -> None:
        """Archive processed feedback after successful training."""
        if not feedback_file.exists():
            return

        archive_dir = self.training_data_path / "archive"
        archive_dir.mkdir(exist_ok=True)

        timestamp = datetime.utcnow().strftime("%Y%m%d_%H%M%S")
        archive_file = archive_dir / f"{source_tenant}_{target_tenant}_{timestamp}.jsonl"

        # Move feedback to archive
        feedback_file.rename(archive_file)
        logger.info(f"Archived feedback to {archive_file}")

    def get_training_stats(self, source_tenant: str, target_tenant: str) -> Dict[str, Any]:
        """Get training statistics for a tenant pair"""
        feedback_file = self.training_data_path / f"{source_tenant}_{target_tenant}_feedback.jsonl"

        if not feedback_file.exists():
            stats = {
                "feedbackCount": 0,
                "lastUpdated": None,
                "readyForTraining": False,
                "samplesNeeded": self.min_samples_for_training
            }
        else:
            feedback_count = self._count_feedback_entries(feedback_file)
            last_modified = datetime.fromtimestamp(
                feedback_file.stat().st_mtime
            ).isoformat()

            stats = {
                "feedbackCount": feedback_count,
                "lastUpdated": last_modified,
                "readyForTraining": feedback_count >= self.min_samples_for_training,
                "samplesNeeded": max(0, self.min_samples_for_training - feedback_count)
            }

        # Add model stats if dedupe service is available
        if self.dedupe_service:
            model_key = f"{source_tenant}_{target_tenant}"
            model_stats = self.dedupe_service.get_model_stats(model_key)
            stats["modelExists"] = model_stats.get("exists", False)
            stats["modelCached"] = model_stats.get("cached", False)

        return stats

    async def force_retrain(self, source_tenant: str, target_tenant: str) -> Dict[str, Any]:
        """
        Force model retraining regardless of sample count.
        Useful for admin-triggered retraining.
        """
        feedback_file = self.training_data_path / f"{source_tenant}_{target_tenant}_feedback.jsonl"
        feedback_count = self._count_feedback_entries(feedback_file)

        if feedback_count == 0:
            return {
                "success": False,
                "message": "No feedback available for training"
            }

        # Temporarily lower the threshold
        original_threshold = self.min_samples_for_training
        self.min_samples_for_training = 1

        success = await self._retrain_model(source_tenant, target_tenant)

        # Restore threshold
        self.min_samples_for_training = original_threshold

        return {
            "success": success,
            "message": "Retraining completed" if success else "Retraining failed",
            "samplesUsed": feedback_count
        }
