"""
QbilHub Intelligence Microservice
FastAPI application for schema extraction and entity resolution
"""
import os
from contextlib import asynccontextmanager
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import logging

from app.api import schema_extraction, entity_resolution, feedback
from app.services.llm_service import LLMService
from app.services.dedupe_service import DedupeService
from app.services.training_service import TrainingService

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

logger = logging.getLogger(__name__)


# Service instances (shared across requests)
class ServiceContainer:
    """Container for service instances"""
    llm_service: LLMService = None
    dedupe_service: DedupeService = None
    training_service: TrainingService = None


services = ServiceContainer()


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Application lifespan - initialize and cleanup services"""
    # Startup
    logger.info("Initializing services...")

    services.llm_service = LLMService()
    services.dedupe_service = DedupeService()
    services.training_service = TrainingService(dedupe_service=services.dedupe_service)

    logger.info("Services initialized successfully")

    yield

    # Shutdown
    logger.info("Shutting down services...")


# Create FastAPI app
app = FastAPI(
    title="QbilHub Intelligence Service",
    description="AI-powered schema extraction and entity resolution for B2B document exchange",
    version="1.0.0",
    lifespan=lifespan
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


def get_llm_service() -> LLMService:
    """Get the LLM service instance"""
    return services.llm_service


def get_dedupe_service() -> DedupeService:
    """Get the Dedupe service instance"""
    return services.dedupe_service


def get_training_service() -> TrainingService:
    """Get the Training service instance"""
    return services.training_service


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
    llm_status = "operational" if services.llm_service else "not initialized"
    dedupe_status = "operational" if services.dedupe_service else "not initialized"

    # Check if LLM is using OpenAI or rule-based
    llm_mode = "openai" if (services.llm_service and services.llm_service.use_llm) else "rule-based"

    return {
        "status": "healthy",
        "components": {
            "api": "operational",
            "dedupe": dedupe_status,
            "llm": llm_status,
            "llm_mode": llm_mode
        },
        "config": {
            "openai_configured": bool(os.getenv("OPENAI_API_KEY")),
            "model_path": os.getenv("DEDUPE_MODEL_PATH", "./models"),
            "training_data_path": os.getenv("TRAINING_DATA_PATH", "./training_data")
        }
    }


@app.get("/api/training/stats/{source_tenant}/{target_tenant}")
async def get_training_stats(source_tenant: str, target_tenant: str):
    """Get training statistics for a tenant pair"""
    if not services.training_service:
        return {"error": "Training service not initialized"}

    return services.training_service.get_training_stats(source_tenant, target_tenant)


@app.post("/api/training/retrain/{source_tenant}/{target_tenant}")
async def force_retrain(source_tenant: str, target_tenant: str):
    """Force model retraining for a tenant pair"""
    if not services.training_service:
        return {"error": "Training service not initialized"}

    return await services.training_service.force_retrain(source_tenant, target_tenant)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "app.main:app",
        host="0.0.0.0",
        port=8000,
        reload=True,
        log_level="info"
    )
