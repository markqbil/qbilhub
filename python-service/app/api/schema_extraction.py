"""
Schema Extraction API endpoint
Uses LangChain + OpenAI to map source fields to target schema
"""
from fastapi import APIRouter, HTTPException
import logging

from app.models.schemas import SchemaExtractionRequest, SchemaExtractionResponse
from app.services.llm_service import LLMService

router = APIRouter()
logger = logging.getLogger(__name__)


@router.post("/extract-schema", response_model=SchemaExtractionResponse)
async def extract_schema(request: SchemaExtractionRequest):
    """
    Extract and normalize schema from raw document data using LLM.

    This endpoint analyzes the structure of incoming data and maps it to
    the standardized QbilHub contract schema.
    """
    try:
        logger.info(f"Extracting schema from raw data with {len(request.rawData)} fields")

        llm_service = LLMService()
        result = await llm_service.extract_schema(request.rawData)

        logger.info("Schema extraction completed successfully")
        return result

    except Exception as e:
        logger.error(f"Schema extraction failed: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Schema extraction failed: {str(e)}"
        )
