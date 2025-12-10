"""
Schema Extraction API endpoint
Uses LangChain + OpenAI to map source fields to target schema
"""
from fastapi import APIRouter, HTTPException, Depends
import logging

from app.models.schemas import SchemaExtractionRequest, SchemaExtractionResponse

router = APIRouter()
logger = logging.getLogger(__name__)


def get_llm_service():
    """Dependency to get LLM service from main app"""
    from app.main import get_llm_service as _get_llm_service
    return _get_llm_service()


@router.post("/extract-schema", response_model=SchemaExtractionResponse)
async def extract_schema(request: SchemaExtractionRequest):
    """
    Extract and normalize schema from raw document data using LLM.

    This endpoint analyzes the structure of incoming data and maps it to
    the standardized QbilHub contract schema.
    """
    try:
        logger.info(f"Extracting schema from raw data with {len(request.rawData)} fields")

        llm_service = get_llm_service()
        if not llm_service:
            raise HTTPException(
                status_code=503,
                detail="LLM service not initialized"
            )

        result = await llm_service.extract_schema(request.rawData)

        logger.info("Schema extraction completed successfully")
        return result

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Schema extraction failed: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Schema extraction failed: {str(e)}"
        )


@router.post("/analyze-document")
async def analyze_document(request: SchemaExtractionRequest):
    """
    Analyze document structure (useful for understanding new partner formats).
    """
    try:
        llm_service = get_llm_service()
        if not llm_service:
            raise HTTPException(
                status_code=503,
                detail="LLM service not initialized"
            )

        result = await llm_service.analyze_document_structure(request.rawData)
        return result

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Document analysis failed: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Document analysis failed: {str(e)}"
        )
