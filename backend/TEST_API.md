# API Testing Guide

## Quick API Tests

### 1. Test Health Endpoint
```bash
curl http://localhost:8000/api/health
```

Expected Response:
```json
{
  "success": true,
  "message": "API is running"
}
```

### 2. Test Create Client (POST)
```bash
curl -X POST http://localhost:8000/api/clients \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"client_id\": \"CLT-0001\",
    \"organization\": \"Test Organization\",
    \"first_name\": \"John\",
    \"middle_name\": \"M\",
    \"last_name\": \"Doe\",
    \"email\": \"john.doe@example.com\",
    \"contact_number\": \"09123456789\",
    \"barangay\": \"Test Barangay\",
    \"municipality\": \"Test Municipality\",
    \"province\": \"Test Province\",
    \"password\": \"password123\",
    \"password_confirmation\": \"password123\"
  }"
```

### 3. Test Get All Clients (GET)
```bash
curl http://localhost:8000/api/clients
```

### 4. Test Get Single Client (GET)
```bash
curl http://localhost:8000/api/clients/1
```

### 5. Test Update Client (PUT)
```bash
curl -X PUT http://localhost:8000/api/clients/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"organization\": \"Updated Organization\",
    \"email\": \"john.updated@example.com\"
  }"
```

### 6. Test Delete Client (DELETE)
```bash
curl -X DELETE http://localhost:8000/api/clients/1
```

## Using PowerShell (Windows)

### 1. Test Health Endpoint
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/health" -Method Get
```

### 2. Test Create Client
```powershell
$body = @{
    client_id = "CLT-0001"
    organization = "Test Organization"
    first_name = "John"
    middle_name = "M"
    last_name = "Doe"
    email = "john.doe@example.com"
    contact_number = "09123456789"
    barangay = "Test Barangay"
    municipality = "Test Municipality"
    province = "Test Province"
    password = "password123"
    password_confirmation = "password123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/clients" -Method Post -Body $body -ContentType "application/json"
```

### 3. Test Get All Clients
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/clients" -Method Get
```

## Using Browser Tools

### Using Browser DevTools Console
```javascript
// Test Health Endpoint
fetch('http://localhost:8000/api/health')
  .then(res => res.json())
  .then(data => console.log(data));

// Test Create Client
fetch('http://localhost:8000/api/clients', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    client_id: "CLT-0001",
    organization: "Test Organization",
    first_name: "John",
    middle_name: "M",
    last_name: "Doe",
    email: "john.doe@example.com",
    contact_number: "09123456789",
    barangay: "Test Barangay",
    municipality: "Test Municipality",
    province: "Test Province",
    password: "password123",
    password_confirmation: "password123"
  })
})
  .then(res => res.json())
  .then(data => console.log(data));

// Test Get All Clients
fetch('http://localhost:8000/api/clients')
  .then(res => res.json())
  .then(data => console.log(data));
```

## Expected Response Formats

### Success Response (Create/Update)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "client_id": "CLT-0001",
    "organization": "Test Organization",
    "first_name": "John",
    "middle_name": "M",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "contact_number": "09123456789",
    "barangay": "Test Barangay",
    "municipality": "Test Municipality",
    "province": "Test Province",
    "created_at": "2026-09-15T14:23:02.000000Z",
    "updated_at": "2026-09-15T14:23:02.000000Z"
  },
  "message": "Client created successfully"
}
```

### Error Response (Validation Failed)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

### Error Response (Not Found)
```json
{
  "success": false,
  "message": "Client not found",
  "error": "No query results for model [App\\Models\\Client] 999"
}
```

## Testing with Postman

1. Import the following collection or create requests manually
2. Base URL: `http://localhost:8000/api`
3. Set Headers:
   - Content-Type: application/json
   - Accept: application/json

### Postman Collection Structure
```
SeedMIS API
├── Health Check (GET /health)
├── Clients
│   ├── Get All Clients (GET /clients)
│   ├── Get Single Client (GET /clients/:id)
│   ├── Create Client (POST /clients)
│   ├── Update Client (PUT /clients/:id)
│   └── Delete Client (DELETE /clients/:id)
```
