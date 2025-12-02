# 🚀 QbilHub - Ready to Start!

## ✅ Setup Complete!

All database tables created successfully:
- ✅ tenants
- ✅ users
- ✅ received_documents
- ✅ delegation_rules
- ✅ active_learning_feedback
- ✅ messenger_messages
- ...and more!

---

## 🎯 Starting the Application

You have **3 options** to start QbilHub:

### Option 1: Automated Script (Recommended)

```powershell
cd C:\Users\MarkEllis\Documents\QbilHub
.\start-qbilhub.ps1
```

This opens 2 windows automatically:
1. **Python Intelligence Service** (port 8000)
2. **Symfony Web Server** (port 8080)

**Note:** Messages are processed synchronously (no background worker needed).

### Option 2: Manual Start (2 separate terminals)

**Terminal 1 - Python Service:**
```powershell
cd C:\Users\MarkEllis\Documents\QbilHub\python-service
.\venv\Scripts\Activate.ps1
uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload
```

**Terminal 2 - Symfony Server:**
```powershell
cd C:\Users\MarkEllis\Documents\QbilHub
php -S localhost:8080 -t public
```

**Note:** Message queue worker is not needed - messages process immediately.

### Option 3: PHP Built-in Server Only (Quick Test)

```powershell
cd C:\Users\MarkEllis\Documents\QbilHub
php -S localhost:8080 -t public
```

**Note:** Without Python service, AI features won't work (schema extraction, entity resolution).

---

## 🌐 Accessing QbilHub

Once started, access the application at:

**Main Application:**
- http://localhost:8080

**API Documentation:**
- http://localhost:8080/api

**Python Service (AI):**
- http://localhost:8000/docs (Interactive API docs)
- http://localhost:8000/health (Health check)

**Admin/Debug:**
- http://localhost:8080/_profiler (Symfony Profiler - dev only)

---

## 🧪 Testing the Application

### 1. Health Checks

```powershell
# Test Symfony
Invoke-WebRequest -Uri "http://localhost:8080" -Method GET

# Test Python Service
Invoke-WebRequest -Uri "http://localhost:8000/health" -Method GET
```

### 2. Create Test User

```powershell
cd C:\Users\MarkEllis\Documents\QbilHub
php bin/console app:create-user test@example.com password123 ROLE_USER
```

### 3. Test API Endpoints

**Via Browser:**
- Visit: http://localhost:8000/docs
- Try the interactive API documentation

**Via PowerShell:**
```powershell
# Test schema extraction
$body = @{
    raw_data = @{
        contract_no = "CTR-2024-001"
        supplier_name = "ABC Dairy"
        product_code = "WPC 80"
        quantity = "1000"
    }
    tenant_id = 1
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/api/extract-schema" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body
```

---

## 📊 Available Features

### MVP Features (Implemented):

1. **Hub Inbox** ✅
   - Receive documents from multiple tenants
   - View incoming contracts in unified inbox
   - Status tracking (pending, mapped, delegated, etc.)

2. **Split-View Mapping** ✅
   - View PDF/document on left
   - Edit mapped fields on right
   - Confidence scores displayed
   - Color-coded fields (high/medium/low confidence)

3. **AI Schema Extraction** ✅
   - Automatic field detection
   - Rule-based mapping
   - Confidence scoring
   - Product code normalization

4. **Entity Resolution** ✅
   - Match similar product codes
   - Fuzzy matching (WPC 80 → Whey Protein Concentrate 80%)
   - Supplier name normalization

5. **Active Learning** ✅
   - Track user corrections
   - Feedback loop to Python service
   - Improve extraction over time

6. **Delegation System** ✅
   - Assign documents to processors
   - Rule-based auto-delegation
   - Track delegation history

7. **Multi-Tenancy** ✅
   - Row-level security
   - Tenant isolation
   - Separate data per organization

8. **Real-time Updates** ✅
   - Server-Sent Events (SSE) via Mercure
   - Live notifications
   - Auto-refresh inbox

9. **Message Queue** ✅
   - Async processing
   - Background jobs
   - Doctrine transport

---

## 🔧 Troubleshooting

### Port Already in Use

**Error:** "Address already in use"

**Solution:**
```powershell
# Find process using port 8080
netstat -ano | findstr :8080

# Kill the process (replace PID with actual process ID)
taskkill /PID <PID> /F
```

### Python Service Won't Start

**Check Python version:**
```powershell
python --version  # Should be 3.10+
```

**Reinstall dependencies:**
```powershell
cd python-service
.\venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

### Database Connection Issues

**Check PostgreSQL is running:**
```powershell
# Via PowerShell
Get-Service postgresql*
```

**Test connection:**
```powershell
php bin/console dbal:run-sql "SELECT 1"
```

### Clear Cache

```powershell
php bin/console cache:clear
```

---

## 📁 Important Files

**Configuration:**
- [.env](.env) - Environment variables
- [.env.local](.env.local) - Local overrides (ignored by git)
- [config/packages/doctrine.yaml](config/packages/doctrine.yaml) - Database config

**Application:**
- [src/Entity/](src/Entity/) - Database entities
- [src/Controller/](src/Controller/) - API endpoints
- [python-service/app/](python-service/app/) - AI service

**Frontend:**
- [assets/js/components/](assets/js/components/) - Vue.js components
- [templates/](templates/) - Twig templates

---

## 🎯 Next Steps

1. **Create test tenants and users**
   ```powershell
   php bin/console app:create-tenant "Test Tenant" "TEST001"
   php bin/console app:create-user admin@test.com password123 ROLE_ADMIN
   ```

2. **Upload test documents** via the web interface

3. **Test the mapping workflow**
   - Upload a contract
   - View in Hub Inbox
   - Use split-view to map fields
   - Submit corrections for active learning

4. **Configure delegation rules** in the admin panel

5. **Monitor message queue**
   ```powershell
   php bin/console messenger:stats
   ```

---

## 🔐 Security Notes

1. **Change default secrets** in .env:
   - `APP_SECRET` - Random 32-character string
   - `MERCURE_JWT_SECRET` - Random JWT secret

2. **PostgreSQL password** - Currently: `Thomas@2020`
   - Consider changing for production

3. **Re-enable firewall/antivirus** after installation

4. **Don't commit** `.env.local` to git (already in .gitignore)

---

## 📚 Additional Commands

**Create new entity:**
```powershell
php bin/console make:entity
```

**Generate migration:**
```powershell
php bin/console make:migration
```

**Run migrations:**
```powershell
php bin/console doctrine:migrations:migrate
```

**List all routes:**
```powershell
php bin/console debug:router
```

**List all services:**
```powershell
php bin/console debug:container
```

**View logs:**
```powershell
Get-Content var/log/dev.log -Tail 50 -Wait
```

---

## 🎉 You're Ready!

Everything is set up and ready to go. Just run:

```powershell
.\start-qbilhub.ps1
```

And visit: **http://localhost:8080**

---

**Questions or issues?** Check the logs in `var/log/` and Python service output.

**Happy coding!** 🚀
