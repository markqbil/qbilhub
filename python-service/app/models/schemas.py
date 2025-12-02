"""
Pydantic models for request/response schemas
"""
from typing import Dict, Any, Optional, List
from pydantic import BaseModel, Field


class SchemaExtractionRequest(BaseModel):
    """Request model for schema extraction"""
    rawData: Dict[str, Any] = Field(..., description="Raw data from incoming document")


class SchemaExtractionResponse(BaseModel):
    """Response model for schema extraction"""
    extractedSchema: Dict[str, Any] = Field(..., description="Extracted and normalized schema")
    fieldMappings: Dict[str, str] = Field(..., description="Source to target field mappings")


class EntityResolutionRequest(BaseModel):
    """Request model for entity resolution"""
    extractedData: Dict[str, Any] = Field(..., description="Extracted data from schema extraction")
    sourceTenantCode: str = Field(..., description="Source tenant identifier")
    targetTenantCode: str = Field(..., description="Target tenant identifier")


class EntityResolutionResponse(BaseModel):
    """Response model for entity resolution"""
    mappedData: Dict[str, Any] = Field(..., description="Resolved and mapped entity data")
    confidenceScores: Dict[str, float] = Field(..., description="Confidence scores for each field (0-1)")


class FeedbackRequest(BaseModel):
    """Request model for active learning feedback"""
    sourceTenantCode: str
    targetTenantCode: str
    sourceField: str
    sourceValue: str
    targetField: str
    correctedValue: str


class FeedbackResponse(BaseModel):
    """Response model for feedback submission"""
    success: bool
    message: str
