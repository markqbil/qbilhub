"""
QbilHub Intelligence Microservice
FastAPI application for schema extraction and entity resolution
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import logging

from app.api import schema_extraction, entity_resolution, feedback

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

logger = logging.getLogger(__name__)

# Create FastAPI app
app = FastAPI(
    title="QbilHub Intelligence Service",
    description="AI-powered schema extraction and entity resolution for B2B document exchange",
    version="1.0.0"
)

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure appropriately for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Include routers
app.include_router(schema_extraction.router, prefix="/api", tags=["Schema Extraction"])
app.include_router(entity_resolution.router, prefix="/api", tags=["Entity Resolution"])
app.include_router(feedback.router, prefix="/api", tags=["Active Learning"])


@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "QbilHub Intelligence Service",
        "version": "1.0.0"
    }


@app.get("/health")
async def health_check():
    """Detailed health check"""
    return {
        "status": "healthy",
        "components": {
            "api": "operational",
            "dedupe": "operational",
            "llm": "operational"
        }
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "app.main:app",
        host="0.0.0.0",
        port=8000,
        reload=True,
        log_level="info"
    )
