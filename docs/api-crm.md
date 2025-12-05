## CRM Endpoints

Defined in `routes/crm-routes.php`.

### Auth (guest)
- POST `/api/crm/check-credentials`
- POST `/api/crm/login`
- POST `/api/crm/register`

### Public/Utility
- GET `/api/crm/fetch-categories-ids`
- GET `/api/crm/fetch-services-ids`
- GET `/api/crm/fetch-authorized-brands`
- GET `/api/fetch-authorized-brands`
- GET `/api/crm/fetch-branches`
- GET `/api/crm/fetch-states`
- GET `/api/crm/fetch-cities`
- GET `/api/crm/fetch-countries`
- MATCH GET|POST `/api/whatsapp/callback`
- POST `/api/crm/complaints/create`
- Resource `/api/routes_meta`

### Authenticated (Sanctum)
- PUT `/api/crm/complaints/cancel/{id}`
- Resource `/api/crm/staff`
- Resource `/api/crm/branches`
- Resource `/api/crm/authorized-brands`
- Resource `/api/crm/categories`
- POST `/api/complaints/send-message-to-customer/{to}`
- Resource `/api/crm/complaints`
- PUT `/api/complaints/schedule`
- GET `/api/crm/fetch-workers`
- GET `/api/crm/fetch-branches-ids`
- GET `/api/crm/dashboard-chart-data`
- Resource `/api/crm/attendance`
- GET `/api/crm/dashboard-status-data`
- GET `/api/crm/dashboard-complaints-by-brand`
- GET `/api/crm/dashboard-get-complaints`
- GET `/api/crm/complaint-history/{id}`
- Resource `/api/crm/inventory`
- GET `/api/crm/daily-attendance-stats`
- POST `/api/crm/attendance/mark-present/{id}`
- POST `/api/crm/attendance/mark-absent/{id}`
- GET `/api/crm/attendance/by-user/{id}`
- GET `/api/crm/complaints/technician-reached-on-site/{id}`
- apiResource `/api/crm/customer-reviews`
- GET `/api/crm/complaint/customer-reviews/{complaintId}`
- apiResource `/api/crm/cso-remarks`
- GET `/api/crm/cso-remarks/{complaintId}`

Example create complaint:
```bash
curl -X POST "$API_BASE/api/crm/complaints" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"customer_id":1,"category_id":2,"description":"Issue details"}'
```

