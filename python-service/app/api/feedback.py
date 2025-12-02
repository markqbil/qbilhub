"""
Active Learning Feedback API endpoint
Receives user corrections to retrain the dedupe model
"""
from fastapi import APIRouter, HTTPException
import logging

from app.models.schemas import FeedbackRequest, FeedbackResponse
from app.services.training_service import TrainingService

router = APIRouter()
logger = logging.getLogger(__name__)


@router.post("/feedback", response_model=FeedbackResponse)
async def submit_feedback(request: FeedbackRequest):
    """
    Submit user corrections for active learning.

    When users correct AI-mapped fields, this feedback is used to
    retrain the dedupe model and improve future predictions.
    """
    try:
        logger.info(
            f"Received feedback for {request.sourceTenantCode} -> "
            f"{request.targetTenantCode}: {request.sourceField} = {request.correctedValue}"
        )

        training_service = TrainingService()
        await training_service.process_feedback(
            source_tenant=request.sourceTenantCode,
            target_tenant=request.targetTenantCode,
            source_field=request.sourceField,
            source_value=request.sourceValue,
            target_field=request.targetField,
            corrected_value=request.correctedValue
        )

        logger.info("Feedback processed successfully")
        return FeedbackResponse(
            success=True,
            message="Feedback received and model updated"
        )

    except Exception as e:
        logger.error(f"Feedback processing failed: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Feedback processing failed: {str(e)}"
        )
