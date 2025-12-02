"""
Training Service for Active Learning
Processes user feedback and retrains dedupe models
"""
import os
import json
import logging
from typing import Dict, Any
from datetime import datetime

logger = logging.getLogger(__name__)


class TrainingService:
    """Service for processing feedback and retraining models"""

    def __init__(self):
        self.training_data_path = os.getenv("TRAINING_DATA_PATH", "./training_data")
        os.makedirs(self.training_data_path, exist_ok=True)

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
            feedback_file = os.path.join(
                self.training_data_path,
                f"{source_tenant}_{target_tenant}_feedback.jsonl"
            )

            with open(feedback_file, 'a') as f:
                f.write(json.dumps(feedback_entry) + '\n')

            logger.info(
                f"Feedback saved: {source_tenant} -> {target_tenant}, "
                f"{source_field}:{source_value} = {corrected_value}"
            )

            # Check if we have enough feedback to retrain
            feedback_count = self._count_feedback_entries(feedback_file)
            if feedback_count >= 10:  # Retrain after 10 corrections
                await self._retrain_model(source_tenant, target_tenant)

            return True

        except Exception as e:
            logger.error(f"Failed to process feedback: {str(e)}", exc_info=True)
            return False

    def _count_feedback_entries(self, feedback_file: str) -> int:
        """Count number of feedback entries in file"""
        if not os.path.exists(feedback_file):
            return 0

        with open(feedback_file, 'r') as f:
            return sum(1 for _ in f)

    async def _retrain_model(self, source_tenant: str, target_tenant: str):
        """
        Retrain dedupe model with accumulated feedback.

        This is a placeholder for the actual retraining logic.
        In production, this would:
        1. Load all feedback data
        2. Convert to dedupe training format
        3. Retrain the model
        4. Save the updated model
        """
        logger.info(f"Retraining model for {source_tenant} -> {target_tenant}")

        feedback_file = os.path.join(
            self.training_data_path,
            f"{source_tenant}_{target_tenant}_feedback.jsonl"
        )

        # Load feedback data
        training_pairs = []
        with open(feedback_file, 'r') as f:
            for line in f:
                entry = json.loads(line)
                training_pairs.append({
                    "source": entry["sourceValue"],
                    "target": entry["correctedValue"],
                    "field": entry["targetField"]
                })

        logger.info(f"Loaded {len(training_pairs)} training pairs")

        # In production, implement actual model retraining here
        # For MVP, we'll just log the intent
        logger.info(
            f"Model retraining queued for {source_tenant} -> {target_tenant} "
            f"with {len(training_pairs)} examples"
        )

        return True

    def get_training_stats(self, source_tenant: str, target_tenant: str) -> Dict[str, Any]:
        """Get training statistics for a tenant pair"""
        feedback_file = os.path.join(
            self.training_data_path,
            f"{source_tenant}_{target_tenant}_feedback.jsonl"
        )

        if not os.path.exists(feedback_file):
            return {
                "feedbackCount": 0,
                "lastUpdated": None
            }

        feedback_count = self._count_feedback_entries(feedback_file)
        last_modified = datetime.fromtimestamp(
            os.path.getmtime(feedback_file)
        ).isoformat()

        return {
            "feedbackCount": feedback_count,
            "lastUpdated": last_modified
        }
