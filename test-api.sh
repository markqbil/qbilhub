#!/bin/bash
# QbilHub Python Service API Test Script

echo "========================================="
echo "QbilHub Intelligence Service API Tests"
echo "========================================="
echo ""

# Test 1: Health Check
echo "1. Testing Health Check..."
curl -s http://localhost:8000/health | json_pp
echo ""
echo ""

# Test 2: Schema Extraction
echo "2. Testing Schema Extraction..."
echo "   Input: Contract from Tenant A with various field names"
curl -s -X POST http://localhost:8000/api/extract-schema \
  -H "Content-Type: application/json" \
  -d '{
    "rawData": {
      "contract_no": "C-2024-001",
      "supplier_name": "Acme Dairy Corp",
      "product_code": "WPC80",
      "qty": "1000",
      "uom": "MT",
      "unit_price": "5.50",
      "currency_code": "EUR",
      "delivery_date": "2024-12-31",
      "ship_to": "Amsterdam, Netherlands"
    }
  }' | json_pp
echo ""
echo ""

# Test 3: Entity Resolution
echo "3. Testing Entity Resolution (Product Matching)..."
echo "   Testing: WPC80 → Whey Protein Concentrate 80%"
curl -s -X POST http://localhost:8000/api/resolve-entities \
  -H "Content-Type: application/json" \
  -d '{
    "extractedData": {
      "contractNumber": "C-2024-001",
      "supplier": "Acme Dairy Corp",
      "product": "WPC80",
      "quantity": "1000",
      "unit": "MT",
      "pricePerUnit": "5.50",
      "currency": "EUR",
      "deliveryDate": "2024-12-31"
    },
    "sourceTenantCode": "TENANT_A",
    "targetTenantCode": "TENANT_B"
  }' | json_pp
echo ""
echo ""

# Test 4: Different Product Code
echo "4. Testing Entity Resolution with different product..."
echo "   Testing: SMP → Skimmed Milk Powder"
curl -s -X POST http://localhost:8000/api/resolve-entities \
  -H "Content-Type: application/json" \
  -d '{
    "extractedData": {
      "product": "SMP",
      "quantity": "500",
      "unit": "MT"
    },
    "sourceTenantCode": "TENANT_C",
    "targetTenantCode": "TENANT_D"
  }' | json_pp
echo ""
echo ""

# Test 5: Active Learning Feedback
echo "5. Testing Active Learning Feedback..."
echo "   Submitting user correction"
curl -s -X POST http://localhost:8000/api/feedback \
  -H "Content-Type: application/json" \
  -d '{
    "sourceTenantCode": "TENANT_A",
    "targetTenantCode": "TENANT_B",
    "sourceField": "product_code",
    "sourceValue": "WPC80",
    "targetField": "product",
    "correctedValue": "Whey Protein Concentrate 80% Premium Grade"
  }' | json_pp
echo ""
echo ""

echo "========================================="
echo "All tests completed!"
echo "========================================="
