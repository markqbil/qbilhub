# QbilHub - B2B Document Exchange Platform

A centralized B2B document exchange and data integration platform for the commodity trading sector that uses AI to map disparate product codes between trading partners.

## Project Overview

QbilHub replaces email-based workflows with a structured "Hub Inbox" and leverages AI to automate the mapping of product codes, supplier names, and contract terms between different trading partners.

## Architecture

### Hybrid Architecture
The system consists of two main components:

1. **Core Application (Symfony/PHP)** - "The Controller"
   - System of record
   - UI rendering
   - Business logic
   - User authentication
   - Database: PostgreSQL with JSONB

2. **Intelligence Microservice (Python/FastAPI)** - "The Brain"
   - Schema extraction using LLMs
   - Entity resolution using dedupe library
   - Active learning from user corrections

### Technology Stack

#### Backend (Symfony)
- PHP 8.2+
- Symfony 6.4
- API Platform
- Doctrine ORM
- PostgreSQL 15+
- Symfony Messenger (async processing)
- Symfony Mercure (real-time updates)

#### Frontend
- Twig templates
- Vue.js 3
- Axios for API calls
- Webpack Encore

#### Intelligence Service (Python)
- Python 3.11+
- FastAPI
- dedupe (probabilistic record linkage)
- LangChain + OpenAI (schema extraction)

## Features

### 1. Hub Inbox
- Real-time notification badge for new documents
- Filterable view (My Inbox / All Accessible / Specific Colleague)
- Entity-aware document management
- Status tracking (New → Extracting → Resolving → Mapping → Processed)

### 2. Multi-Tenancy & Connectivity
- Global directory of tenants (subsidiaries)
- Opt-in activation model
- Relation mapping (internal IDs to external tenant codes)
- Intelligent routing (default to Hub instead of email)

### 3. Delegation System
- Many-to-many user delegations
- Shared mailbox functionality
- Audit trail of who actioned documents

### 4. Split-View Mapping Interface
- Left panel: PDF viewer or JSON tree of source document
- Right panel: Standard contract entry form
- AI pre-filled fields with confidence scores
- Color-coded confidence indicators (green >90%, yellow <90%)
- Active learning: corrections sent back to retrain models

## Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- PostgreSQL 15+
- Python 3.11+
- RabbitMQ or Redis (for message queue)

### Setup Steps

#### 1. Clone and Install PHP Dependencies
```bash
cd QbilHub
composer install
```

#### 2. Configure Environment
```bash
cp .env .env.local
# Edit .env.local with your database credentials and API keys
```

#### 3. Create Database
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

#### 4. Install Frontend Dependencies
```bash
npm install
npm run build
```

#### 5. Setup Python Service
```bash
cd python-service
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
cp .env.example .env
# Edit .env with your OpenAI API key
```

#### 6. Start Services

**Symfony Application:**
```bash
symfony server:start
```

**Python Intelligence Service:**
```bash
cd python-service
uvicorn app.main:app --reload
```

**Message Queue Worker:**
```bash
php bin/console messenger:consume async -vv
```

**Mercure Hub (for real-time updates):**
```bash
# Download and run Mercure from https://mercure.rocks/
./mercure run
```

## Database Schema

### Core Entities

- **Tenant**: Trading partners/subsidiaries
- **User**: System users with tenant association
- **UserDelegation**: Many-to-many delegation relationships
- **TenantRelationMapping**: Maps internal relations to external tenant codes
- **ReceivedDocument**: Incoming documents with JSONB raw data
- **PurchaseContract**: Standardized contract records

## API Endpoints

### Symfony (Core Application)
- `GET /hub/inbox` - Hub inbox page
- `GET /hub/inbox/documents` - Fetch inbox documents
- `GET /hub/inbox/unread-count` - Get unread count
- `POST /hub/inbox/document/{id}/mark-read` - Mark document as read
- `GET /hub/mapping/document/{id}` - Mapping interface
- `GET /hub/mapping/document/{id}/data` - Get document data
- `POST /hub/mapping/document/{id}/save` - Save mapped contract
- `POST /hub/mapping/document/{id}/feedback` - Submit field correction

### Python Intelligence Service
- `POST /api/extract-schema` - Extract schema from raw data
- `POST /api/resolve-entities` - Resolve entities between tenants
- `POST /api/feedback` - Submit active learning feedback

## Data Flow

1. **Document Received** → Stored in `received_documents` with JSONB raw data
2. **Schema Extraction** → Python service uses LLM to map fields
3. **Entity Resolution** → dedupe library matches product codes
4. **User Mapping** → Split-view interface with pre-filled fields
5. **Correction Feedback** → User corrections retrain the model
6. **Contract Created** → Standardized purchase contract saved

## Active Learning Strategy

The system improves over time through active learning:

1. User corrects an AI-mapped field
2. Correction sent to Python service via message queue
3. Training data accumulated in JSONL files
4. Model retrained after threshold (e.g., 10 corrections)
5. Future predictions improve for that tenant pair

## Security Considerations

- Row-level security (tenants can't see each other's data)
- User authentication via Symfony Security
- API endpoints protected with IsGranted attributes
- JSONB storage allows flexible schema without exposing structure
- Message queue for async processing prevents timeout issues

## Development Guidelines

### Symfony Best Practices
- Use strict types (`declare(strict_types=1)`)
- Dependency Injection via constructor
- Attributes for routing and ORM mapping
- Repository pattern for database queries

### API Platform
- Use API Resources for standard CRUD
- Custom controllers for complex operations
- DTOs for request/response validation

### Vue.js Components
- Modular, reusable components
- Props for configuration
- Axios for API communication
- Event-driven architecture

### Error Handling
- Never fail silently
- Queue jobs if service is down
- User-friendly error messages
- Comprehensive logging

## Contributing

This is a commercial MVP project. For questions or support, contact the development team.

## License

Proprietary - All rights reserved

## Roadmap

- [ ] Enhanced product matching algorithms
- [ ] Multi-language support
- [ ] Mobile application
- [ ] Advanced analytics dashboard
- [ ] Integration with ERP systems
- [ ] Automated contract generation
- [ ] Blockchain-based audit trail
