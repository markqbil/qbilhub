# Qbil Trade API Integration

This document explains how to use the Qbil Trade API integration in QbilHub to enable seamless contract flow between Qbil Trade users.

## Overview

The integration enables:
1. **Import Contracts** - Pull purchase contracts from Qbil Trade into QbilHub
2. **Process with AI** - Use QbilHub's AI to extract and map contract data
3. **Export Contracts** - Push processed contracts back to Qbil Trade as sales orders

This creates a complete contract flow where:
- **Supplier** sends purchase contract → **QbilHub** → **Buyer** receives as sales contract

## Setup

### 1. Get Your API Token

Contact your Qbil Trade organization admin or email support@qbilsoftware.com with:
- Your name
- Email address
- Intended use: "QbilHub Integration"

They will provide you with a Bearer token.

### 2. Configure QbilHub

Add your API token to `.env` or `.env.local`:

```bash
QBIL_TRADE_API_TOKEN=your_actual_token_here
QBIL_TRADE_ENABLE_RATE_LIMITING=true
```

### 3. Run Database Migration

```bash
php bin/console doctrine:migrations:migrate
```

This adds the `external_id` and `metadata` fields needed for the integration.

## Usage

### Import Contracts from Qbil Trade

Pull contracts from Qbil Trade into your Hub Inbox:

```bash
# Import up to 10 contracts
php bin/console app:import-qbil-contracts

# Import 50 contracts
php bin/console app:import-qbil-contracts --limit=50

# Import only active contracts
php bin/console app:import-qbil-contracts --status=active

# Import to specific tenant
php bin/console app:import-qbil-contracts --tenant-code=QBIL001
```

**What happens:**
- Fetches contracts from Qbil Trade API
- Creates ReceivedDocument entries in Hub Inbox
- Sets status to `new` for processing
- Stores Qbil Trade contract ID in `external_id`

### Process Contracts

Use QbilHub's existing workflow:

1. View contracts in Hub Inbox
2. AI extracts schema automatically
3. Review and map fields
4. Status changes to `mapped`

### Export Processed Contracts

Send mapped contracts back to Qbil Trade:

```bash
# Export up to 10 mapped contracts
php bin/console app:export-qbil-contracts

# Export specific document
php bin/console app:export-qbil-contracts --document-id=123

# Flip direction (purchase → sales)
php bin/console app:export-qbil-contracts --flip-direction

# Preview without sending (dry run)
php bin/console app:export-qbil-contracts --dry-run
```

**Direction Flipping:**

The `--flip-direction` flag automatically converts purchase contracts to sales contracts by swapping buyer/seller:

- **Original (Purchase)**: Buyer A orders from Seller B
- **Flipped (Sales)**: Seller B ships to Buyer A

This is perfect for the receiving party's perspective.

### Automation

Set up automated sync with cron jobs or scheduled tasks:

```bash
# Every hour: Import new contracts
0 * * * * cd /path/to/qbilhub && php bin/console app:import-qbil-contracts

# Every 30 minutes: Export processed contracts
*/30 * * * * cd /path/to/qbilhub && php bin/console app:export-qbil-contracts --flip-direction
```

## API Features

### Rate Limiting

The client automatically handles Qbil Trade's rate limits:

- Monitors `X-RateLimit-Remaining` header
- Implements exponential backoff retry logic
- Waits for rate limit reset if needed
- Logs warnings when approaching limits

### Error Handling

The integration handles:

- **401 Unauthorized** - Invalid API token
- **403 Forbidden** - Insufficient permissions
- **429 Rate Limit** - Automatic retry with backoff
- **422 Validation** - Shows detailed validation errors
- **500 Server Errors** - Retries up to 3 times

### Supported Endpoints

The `QbilTradeApiClient` service provides access to:

```php
// User & Configuration
$client->getMe()

// Contracts
$client->listContracts(['status' => 'active'])
$client->getContract($contractId)

// Orders
$client->listOrders()
$client->getOrder($orderId)
$client->createOrder($orderData)
$client->updateOrder($orderId, $orderData)

// Addresses
$client->listAddresses()
$client->getAddress($addressId)

// Delivery Conditions
$client->listDeliveryConditions()
```

## Data Mapping

### Import Mapping

When importing, contracts are mapped to ReceivedDocument:

| Qbil Trade Field | QbilHub Field |
|------------------|---------------|
| `id` | `externalId` |
| `contract_number` | `rawData.contract_number` |
| `type` | `rawData.contract_type` |
| `buyer.name` | `rawData.buyer` |
| `seller.name` | `rawData.seller` |
| `total_amount` | `rawData.total_amount` |
| `items[]` | `rawData.items` |

### Export Mapping

When exporting, ReceivedDocument data becomes Qbil Trade orders:

| QbilHub Field | Qbil Trade Field |
|---------------|------------------|
| `extractedSchema.contract_number` | `external_reference` |
| `extractedSchema.buyer` | `buyer` (or `seller` if flipped) |
| `extractedSchema.seller` | `seller` (or `buyer` if flipped) |
| `extractedSchema.items` | `items[]` |
| `extractedSchema.delivery_date` | `delivery_date` |

## Complete Workflow Example

### Scenario
Supplier "ABC Dairy" sends a purchase contract to Buyer "XYZ Foods"

### Step 1: Import

```bash
php bin/console app:import-qbil-contracts --limit=1
```

Output:
```
Importing Contracts from Qbil Trade
=====================================

Verifying API Connection
 [OK] Connected to Qbil Trade as: ABC Dairy

Using tenant: Qbil Trade (QBILTRADE)

Fetching Contracts
Found 1 contracts to import

Importing Contracts
 1/1 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

Import Summary
 [OK] Successfully imported: 1 contracts
```

### Step 2: Process in QbilHub

1. Open Hub Inbox at http://localhost:8080/hub/inbox
2. See contract from ABC Dairy
3. Click "View" to open mapping interface
4. AI extracts fields automatically
5. Review and confirm mapping
6. Status changes to `mapped`

### Step 3: Export

```bash
php bin/console app:export-qbil-contracts --flip-direction
```

Output:
```
Exporting Contracts to Qbil Trade
===================================

Verifying API Connection
 [OK] Connected to Qbil Trade as: XYZ Foods

Found 1 contracts ready for export

Exporting Contracts
 1/1 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

Export Summary
 [OK] Successfully exported: 1 contracts
```

Result: XYZ Foods now has a sales order in their Qbil Trade account!

## Troubleshooting

### Authentication Errors

```
[ERROR] Qbil Trade API Error: Invalid authentication credentials
```

**Solution**: Check your `QBIL_TRADE_API_TOKEN` in `.env` file

### Rate Limit Errors

```
[WARNING] Rate limit hit, waiting 60 seconds
```

**Solution**: This is normal. The client will automatically retry. Consider:
- Reducing `--limit` values
- Spacing out cron jobs
- Using `QBIL_TRADE_ENABLE_RATE_LIMITING=true`

### No Contracts Found

```
[WARNING] No contracts found matching the criteria
```

**Solution**:
- Check filters (--status, --limit)
- Verify contracts exist in Qbil Trade
- Try without filters: `php bin/console app:import-qbil-contracts`

### Validation Errors on Export

```
[ERROR] Failed to export: 1 contracts
Document ID | Error
123        | Missing required field: buyer
```

**Solution**: Ensure mapped contract has all required fields:
- buyer or seller
- items (with product_code, quantity, unit_price)
- Optional but recommended: delivery_date, currency

## Security Best Practices

1. **Never commit** `.env` with real tokens to git
2. **Use** `.env.local` for local development tokens
3. **Store** production tokens in environment variables
4. **Rotate** API tokens periodically
5. **Monitor** API usage and rate limits
6. **Limit** permissions to only what's needed

## Support

- **Qbil Trade API**: https://developers.qbiltrade.com/
- **Support Email**: support@qbilsoftware.com
- **QbilHub Issues**: https://github.com/markqbil/qbilhub/issues

## API Reference

Full Qbil Trade API documentation: https://developers.qbiltrade.com/qbil.yaml
