# QbilHub - Current Running Status

## ✅ Running Services

### Python Intelligence Microservice
- **Status**: RUNNING ✅
- **URL**: http://localhost:8000
- **API Docs**: http://localhost:8000/docs (FastAPI automatic docs)
- **Health Check**: http://localhost:8000/health

#### Tested Endpoints:
1. ✅ `POST /api/extract-schema` - Schema extraction (rule-based)
2. ✅ `POST /api/resolve-entities` - Entity resolution with confidence scores
3. ✅ `POST /api/feedback` - Active learning feedback

**Example Test:**
```bash
# Schema Extraction
curl -X POST http://localhost:8000/api/extract-schema \
  -H "Content-Type: application/json" \
  -d '{"rawData": {"contract_no": "C-2024-001", "supplier_name": "Acme Corp", "product_code": "WPC80", "qty": "1000", "uom": "MT", "unit_price": "5.50", "currency_code": "EUR", "delivery_date": "2024-12-31"}}'

# Response:
# {"extractedSchema":{"contractNumber":"C-2024-001","supplier":"Acme Corp","product":"WPC80",...},"fieldMappings":{...}}

# Entity Resolution
curl -X POST http://localhost:8000/api/resolve-entities \
  -H "Content-Type: application/json" \
  -d '{"extractedData": {"product": "WPC80", "quantity": "1000", "unit": "MT"}, "sourceTenantCode": "TENANT_A", "targetTenantCode": "TENANT_B"}'

# Response:
# {"mappedData":{"product":"Whey Protein Concentrate 80%",...},"confidenceScores":{"product":0.95,...}}
```

---

## ⏸️ Not Running (Requires PHP Installation)

### Symfony Core Application
- **Status**: NOT RUNNING (PHP not installed)
- **Required**: PHP 8.2+, Composer
- **Would run on**: http://localhost:8080

### What's Missing:
1. PHP 8.2+ (not in system PATH)
2. Composer (PHP package manager)
3. PostgreSQL database (not set up)

### To Install and Run:

#### Option 1: Using Chocolatey (Windows)
```powershell
choco install php composer postgresql
```

#### Option 2: Manual Installation
1. **PHP**: Download from https://windows.php.net/download/
2. **Composer**: Download from https://getcomposer.org/download/
3. **PostgreSQL**: Download from https://www.postgresql.org/download/windows/

#### After Installation:
```bash
# Install Symfony dependencies
composer install

# Configure database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Install frontend assets
npm install
npm run dev

# Start Symfony server
php -S localhost:8080 -t public
```

---

## 📊 Current Test Results

### Python Service Tests: ✅ PASSING

1. **Health Check**: ✅
   - All components operational

2. **Schema Extraction**: ✅
   - Successfully maps source fields to target schema
   - Field mappings: contract_no → contractNumber, supplier_name → supplier, etc.

3. **Entity Resolution**: ✅
   - Product code matching: WPC80 → "Whey Protein Concentrate 80%"
   - Confidence scores: 0.95 for matched products, 0.98 for direct fields

4. **Mock Mode**: ✅
   - Running without OpenAI API (rule-based for MVP)
   - Can be upgraded with OPENAI_API_KEY in .env

---

## 🚀 Quick Access

- **Python Service API Docs**: http://localhost:8000/docs
- **Python Service Health**: http://localhost:8000/health
- **Swagger UI**: Available at /docs endpoint

---

## 📝 Next Steps

To get the full QbilHub application running:

1. **Install PHP & Composer**
   - Required for Symfony application
   - See installation options above

2. **Set up PostgreSQL**
   - Create database
   - Run migrations

3. **Install Frontend Dependencies**
   - `npm install`
   - `npm run dev`

4. **Start All Services**
   - Symfony web server
   - Message queue worker
   - Mercure hub (optional for real-time)

---

## 🔧 Development Mode

Currently running in **Development/Testing Mode**:
- Python service only
- Rule-based matching (no LLM)
- No database persistence
- API testing via curl/Postman

This is perfect for:
- Testing the intelligence microservice
- Developing API integrations
- Understanding the data flow
- Testing entity resolution logic

---

## 📞 Support

For PHP/Symfony setup assistance, see:
- [QUICK_START.md](QUICK_START.md)
- [IMPLEMENTATION_NOTES.md](IMPLEMENTATION_NOTES.md)
- [README.md](README.md)
