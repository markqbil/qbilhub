"""
Entity Resolution API endpoint
Uses dedupe library for probabilistic record linkage and fuzzy matching
"""
from fastapi import APIRouter, HTTPException
import logging

from app.models.schemas import EntityResolutionRequest, EntityResolutionResponse

router = APIRouter()
logger = logging.getLogger(__name__)


def get_dedupe_service():
    """Dependency to get Dedupe service from main app"""
    from app.main import get_dedupe_service as _get_dedupe_service
    return _get_dedupe_service()


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

        dedupe_service = get_dedupe_service()
        if not dedupe_service:
            raise HTTPException(
                status_code=503,
                detail="Dedupe service not initialized"
            )

        result = await dedupe_service.resolve_entities(
            request.extractedData,
            request.sourceTenantCode,
            request.targetTenantCode
        )

        logger.info("Entity resolution completed successfully")
        return result

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Entity resolution failed: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Entity resolution failed: {str(e)}"
        )


@router.get("/model-stats/{source_tenant}/{target_tenant}")
async def get_model_stats(source_tenant: str, target_tenant: str):
    """
    Get statistics about the trained model for a tenant pair.
    """
    try:
        dedupe_service = get_dedupe_service()
        if not dedupe_service:
            raise HTTPException(
                status_code=503,
                detail="Dedupe service not initialized"
            )

        model_key = f"{source_tenant}_{target_tenant}"
        return dedupe_service.get_model_stats(model_key)

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Failed to get model stats: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Failed to get model stats: {str(e)}"
        )
