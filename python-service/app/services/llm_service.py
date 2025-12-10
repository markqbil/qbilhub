"""
LLM Service for schema extraction using OpenAI/LangChain
Provides intelligent field mapping from diverse document formats
"""
import os
import json
import logging
from typing import Dict, Any, Optional
from langchain_openai import ChatOpenAI
from langchain.prompts import ChatPromptTemplate
from langchain.output_parsers import PydanticOutputParser
from pydantic import BaseModel, Field

logger = logging.getLogger(__name__)


class SchemaExtractionResult(BaseModel):
    """Pydantic model for LLM output parsing"""
    extractedSchema: Dict[str, Any] = Field(
        description="Normalized schema with standard field names"
    )
    fieldMappings: Dict[str, str] = Field(
        description="Mapping from source field names to target field names"
    )
    confidence: Dict[str, float] = Field(
        description="Confidence score (0-1) for each field mapping"
    )


class LLMService:
    """Service for LLM-based schema extraction using OpenAI"""

    # Standard target schema fields for commodity trading contracts
    TARGET_SCHEMA = {
        "contractNumber": "Unique identifier for the contract",
        "supplier": "Name of the supplier/vendor company",
        "product": "Product name or code being traded",
        "quantity": "Numeric quantity being ordered",
        "unit": "Unit of measure (kg, MT, lb, etc.)",
        "pricePerUnit": "Price per unit of measure",
        "currency": "Currency code (EUR, USD, GBP, etc.)",
        "deliveryDate": "Expected delivery date",
        "deliveryLocation": "Delivery address or location"
    }

    # Rule-based fallback mappings
    FIELD_MAP = {
        'contract_no': 'contractNumber',
        'contract_number': 'contractNumber',
        'contractnr': 'contractNumber',
        'contract_num': 'contractNumber',
        'contract_id': 'contractNumber',
        'po_number': 'contractNumber',
        'order_number': 'contractNumber',
        'supplier_name': 'supplier',
        'vendor': 'supplier',
        'vendor_name': 'supplier',
        'seller': 'supplier',
        'product_name': 'product',
        'product_code': 'product',
        'mat_id': 'product',
        'material': 'product',
        'material_code': 'product',
        'item': 'product',
        'article': 'product',
        'qty': 'quantity',
        'amount': 'quantity',
        'quantity_value': 'quantity',
        'order_qty': 'quantity',
        'uom': 'unit',
        'unit_of_measure': 'unit',
        'measure_unit': 'unit',
        'price': 'pricePerUnit',
        'unit_price': 'pricePerUnit',
        'price_per_unit': 'pricePerUnit',
        'rate': 'pricePerUnit',
        'currency_code': 'currency',
        'curr': 'currency',
        'ccy': 'currency',
        'delivery_date': 'deliveryDate',
        'ship_date': 'deliveryDate',
        'date_delivery': 'deliveryDate',
        'eta': 'deliveryDate',
        'expected_date': 'deliveryDate',
        'delivery_location': 'deliveryLocation',
        'ship_to': 'deliveryLocation',
        'location': 'deliveryLocation',
        'destination': 'deliveryLocation',
        'delivery_address': 'deliveryLocation',
    }

    def __init__(self):
        self.api_key = os.getenv("OPENAI_API_KEY")
        self.model_name = os.getenv("OPENAI_MODEL", "gpt-4o-mini")
        self.use_llm = bool(self.api_key)

        if self.use_llm:
            logger.info(f"LLM Service initialized with model: {self.model_name}")
            self._init_llm()
        else:
            logger.warning("OPENAI_API_KEY not set - using rule-based extraction")

    def _init_llm(self):
        """Initialize LangChain components"""
        self.llm = ChatOpenAI(
            model=self.model_name,
            temperature=0.1,  # Low temperature for consistent extraction
            api_key=self.api_key
        )

        self.output_parser = PydanticOutputParser(pydantic_object=SchemaExtractionResult)

        self.prompt = ChatPromptTemplate.from_messages([
            ("system", """You are a document schema extraction specialist for B2B commodity trading.
Your task is to map fields from incoming documents to a standard contract schema.

Target schema fields and their meanings:
{target_schema}

Rules:
1. Map each source field to the most appropriate target field
2. If a source field doesn't match any target field, keep it with its original name
3. Preserve all values exactly as they appear
4. Provide a confidence score (0-1) for each mapping based on how certain you are
5. For dates, try to normalize to ISO format (YYYY-MM-DD) if possible

{format_instructions}"""),
            ("human", """Extract and map the schema from this document data:

{raw_data}

Return the extracted schema with normalized field names, the field mappings, and confidence scores.""")
        ])

    async def extract_schema(self, raw_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Extract and normalize schema from raw document data.

        Args:
            raw_data: Raw data from incoming document

        Returns:
            Dictionary containing extractedSchema, fieldMappings, and confidence
        """
        if self.use_llm:
            try:
                return await self._llm_extract_schema(raw_data)
            except Exception as e:
                logger.error(f"LLM extraction failed, falling back to rules: {e}")
                return self._rule_based_extract_schema(raw_data)
        else:
            return self._rule_based_extract_schema(raw_data)

    async def _llm_extract_schema(self, raw_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Use LLM to intelligently extract and map schema.
        """
        logger.info("Using LLM-based schema extraction")

        # Format target schema for prompt
        target_schema_str = "\n".join(
            f"- {field}: {desc}"
            for field, desc in self.TARGET_SCHEMA.items()
        )

        # Create the prompt
        messages = self.prompt.format_messages(
            target_schema=target_schema_str,
            format_instructions=self.output_parser.get_format_instructions(),
            raw_data=json.dumps(raw_data, indent=2)
        )

        # Call the LLM
        response = await self.llm.ainvoke(messages)

        # Parse the response
        result = self.output_parser.parse(response.content)

        logger.info(f"LLM extracted {len(result.extractedSchema)} fields")

        return {
            "extractedSchema": result.extractedSchema,
            "fieldMappings": result.fieldMappings,
            "confidence": result.confidence
        }

    def _rule_based_extract_schema(self, raw_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Rule-based schema extraction fallback.
        Uses predefined field mappings for common field names.
        """
        logger.info("Using rule-based schema extraction")

        extracted_schema = {}
        field_mappings = {}
        confidence = {}

        for source_field, value in raw_data.items():
            # Normalize source field name for matching
            source_lower = source_field.lower().replace(' ', '_').replace('-', '_')

            # Try to find a mapping
            target_field = self.FIELD_MAP.get(source_lower)

            if target_field:
                extracted_schema[target_field] = value
                field_mappings[source_field] = target_field
                confidence[target_field] = 0.85  # Good confidence for rule matches
            else:
                # Check if source field already matches a target field name
                if source_lower in [t.lower() for t in self.TARGET_SCHEMA.keys()]:
                    # Find the correctly cased target field
                    for target in self.TARGET_SCHEMA.keys():
                        if target.lower() == source_lower:
                            extracted_schema[target] = value
                            field_mappings[source_field] = target
                            confidence[target] = 0.95
                            break
                else:
                    # Keep original field name
                    extracted_schema[source_field] = value
                    field_mappings[source_field] = source_field
                    confidence[source_field] = 0.5  # Low confidence for unmapped

        return {
            "extractedSchema": extracted_schema,
            "fieldMappings": field_mappings,
            "confidence": confidence
        }

    async def analyze_document_structure(self, raw_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Analyze the structure of a document to understand its format.
        Useful for learning new document formats from trading partners.
        """
        if not self.use_llm:
            return {"analysis": "LLM not available", "detected_fields": list(raw_data.keys())}

        analysis_prompt = ChatPromptTemplate.from_messages([
            ("system", """You are a document structure analyst for B2B commodity trading.
Analyze the given document and describe:
1. The document type (purchase order, contract, invoice, etc.)
2. Key fields and their purposes
3. Any non-standard or partner-specific field naming conventions
4. Suggestions for field mapping improvements"""),
            ("human", "Analyze this document structure:\n\n{document}")
        ])

        messages = analysis_prompt.format_messages(
            document=json.dumps(raw_data, indent=2)
        )

        response = await self.llm.ainvoke(messages)

        return {
            "analysis": response.content,
            "detected_fields": list(raw_data.keys())
        }
