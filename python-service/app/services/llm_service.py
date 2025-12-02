"""
LLM Service for schema extraction
MVP version using rule-based matching (LangChain/OpenAI for future enhancement)
"""
import os
import json
import logging
from typing import Dict, Any

logger = logging.getLogger(__name__)


class LLMService:
    """Service for LLM-based schema extraction"""

    def __init__(self):
        self.api_key = os.getenv("OPENAI_API_KEY")
        logger.info("Using rule-based schema extraction for MVP")
        self.mock_mode = True

    async def extract_schema(self, raw_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Extract and normalize schema from raw document data.

        Args:
            raw_data: Raw data from incoming document

        Returns:
            Dictionary containing extractedSchema and fieldMappings
        """
        return self._mock_extract_schema(raw_data)

    def _mock_extract_schema(self, raw_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Rule-based schema extraction for MVP.
        Performs basic field name matching.
        """
        logger.info("Using rule-based schema extraction")

        field_map = {
            'contract_no': 'contractNumber',
            'contract_number': 'contractNumber',
            'contractnr': 'contractNumber',
            'contract_num': 'contractNumber',
            'supplier_name': 'supplier',
            'vendor': 'supplier',
            'vendor_name': 'supplier',
            'product_name': 'product',
            'product_code': 'product',
            'mat_id': 'product',
            'material': 'product',
            'qty': 'quantity',
            'amount': 'quantity',
            'quantity_value': 'quantity',
            'uom': 'unit',
            'unit_of_measure': 'unit',
            'measure_unit': 'unit',
            'price': 'pricePerUnit',
            'unit_price': 'pricePerUnit',
            'price_per_unit': 'pricePerUnit',
            'currency_code': 'currency',
            'curr': 'currency',
            'delivery_date': 'deliveryDate',
            'ship_date': 'deliveryDate',
            'date_delivery': 'deliveryDate',
            'delivery_location': 'deliveryLocation',
            'ship_to': 'deliveryLocation',
            'location': 'deliveryLocation',
        }

        extracted_schema = {}
        field_mappings = {}

        for source_field, value in raw_data.items():
            source_lower = source_field.lower().replace(' ', '_')
            target_field = field_map.get(source_lower, source_field)

            extracted_schema[target_field] = value
            field_mappings[source_field] = target_field

        return {
            "extractedSchema": extracted_schema,
            "fieldMappings": field_mappings
        }
