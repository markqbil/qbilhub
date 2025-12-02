"""
Entity Resolution API endpoint
Uses dedupe library for probabilistic record linkage and fuzzy matching
"""
from fastapi import APIRouter, HTTPException
import logging

from app.models.schemas import EntityResolutionRequest, EntityResolutionResponse
from app.services.dedupe_service import DedupeService

router = APIRouter()
logger = logging.getLogger(__name__)


@router.post("/resolve-entities", response_model=EntityResolutionResponse)
async def resolve_entities(request: EntityResolutionRequest):
    """
    Resolve entities (product codes, supplier names, etc.) between tenants
    using probabilistic matching.

    This endpoint uses the dedupe library to match disparate product codes
    and terminology between different trading partners.
    """
    try:
        logger.info(
            f"Resolving entities from {request.sourceTenantCode} "
            f"to {request.targetTenantCode}"
        )

        dedupe_service = DedupeService()
        result = await dedupe_service.resolve_entities(
            request.extractedData,
            request.sourceTenantCode,
            request.targetTenantCode
        )

        logger.info("Entity resolution completed successfully")
        return result

    except Exception as e:
        logger.error(f"Entity resolution failed: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Entity resolution failed: {str(e)}"
        )
