# New Features & Improvements Implemented

## ✅ Completed Features

### 1. Floor Management Improvement
- **Change**: Floors are now only required for PG (Paying Guest) properties, not for apartments/flats
- **Updated**: Property types schema - `has_floors` set to `false` for Flat/Apartment and Villa
- **Location**: `database/schema_mongodb.php`

### 2. Direct Floor Number Input for Rooms
- **Change**: When adding a room, you can directly specify floor number instead of creating floors separately
- **Behavior**: 
  - For PG properties (which have floors), floor is automatically created if it doesn't exist
  - Floor number is stored directly in room document
- **Location**: `api/properties/rooms.php`

### 3. Complaints/Tasks/Requests System
**Features:**
- Tenants can create complaints with photos (base64 or file upload)
- Owners/Managers can reply and update status
- Tenants can reply to complaints
- Status tracking: open, in_progress, resolved, closed
- Priority levels: low, medium, high, urgent
- Categories: general, maintenance, complaint, request

**API Endpoints:**
- `POST /api/complaints/create.php` - Create complaint
- `GET /api/complaints/list.php` - List complaints (filtered by role)
- `GET /api/complaints/get.php` - Get complaint details with replies
- `POST /api/complaints/reply.php` - Reply to complaint

**Collections:**
- `complaints` - Main complaints collection
- `complaint_replies` - Replies to complaints

### 4. Property Listing for Rental
**Features:**
- Owners can list empty properties for rental
- Specify rental amount
- Preferred tenant type: family, bachelor, or any
- Automatic check to ensure property has no active renters
- Property status updated to "listed_for_rent"

**API Endpoints:**
- `POST /api/properties/list-for-rental.php` - List property for rental

**Collection:**
- `property_listings` - Rental listings

### 5. Rent Payment Management
**Features:**
- **Tenant Side:**
  - Register rent payment with amount, mode, date, and comment
  - Payment status: pending → waiting for owner approval
  
- **Owner Side:**
  - View all payments for their properties
  - Approve/reject/receive payments
  - Directly mark payments as received (without tenant registration)
  - Add comments when updating status

- **Due Rent Calculation:**
  - Automatic calculation based on monthly rent and payment history
  - Shows total paid, expected total, and due amount

**API Endpoints:**
- `POST /api/rent-payments/create.php` - Tenant registers payment
- `POST /api/rent-payments/create-direct.php` - Owner marks payment as received
- `GET /api/rent-payments/list.php` - List payments (filtered by role)
- `POST /api/rent-payments/update-status.php` - Update payment status (approve/reject/receive)
- `GET /api/rent-payments/due-rent.php` - Calculate due rent

**Collection:**
- `rent_payments` - Rent payment records

**Payment Statuses:**
- `pending` - Tenant registered, waiting for approval
- `approved` - Owner approved the payment
- `rejected` - Owner rejected the payment
- `received` - Payment marked as received

**Payment Modes:**
- cash, bank_transfer, cheque, online, other

## Database Schema Updates

### New Collections Added:
1. **complaints** - Complaint/task/request records
2. **complaint_replies** - Replies to complaints
3. **property_listings** - Property rental listings
4. **rent_payments** - Rent payment records

### Updated Collections:
- **property_types** - Updated `has_floors` for Flat/Apartment and Villa to `false`
- **rooms** - Added `floor_number` field for direct floor reference
- **properties** - Status now supports `listed_for_rent`

## Usage Examples

### Create Complaint (Tenant)
```json
POST /api/complaints/create.php
{
  "property_id": "...",
  "title": "Leaky faucet",
  "description": "Kitchen faucet is leaking",
  "photo": "data:image/jpeg;base64,...",
  "priority": "medium",
  "category": "maintenance"
}
```

### Reply to Complaint (Owner)
```json
POST /api/complaints/reply.php
{
  "complaint_id": "...",
  "message": "We'll send a plumber tomorrow",
  "status": "in_progress"
}
```

### List Property for Rental
```json
POST /api/properties/list-for-rental.php
{
  "property_id": "...",
  "rental_amount": 15000,
  "preferred_tenant_type": "family",
  "description": "2BHK apartment in prime location"
}
```

### Register Rent Payment (Tenant)
```json
POST /api/rent-payments/create.php
{
  "property_id": "...",
  "amount": 15000,
  "payment_date": "2025-01-15",
  "payment_mode": "bank_transfer",
  "comment": "Rent for January 2025"
}
```

### Mark Payment as Received (Owner)
```json
POST /api/rent-payments/create-direct.php
{
  "renter_id": "...",
  "property_id": "...",
  "amount": 15000,
  "payment_date": "2025-01-15",
  "payment_mode": "cash"
}
```

### Update Payment Status (Owner)
```json
POST /api/rent-payments/update-status.php
{
  "payment_id": "...",
  "status": "received",
  "comment": "Payment received in cash"
}
```

## Next Steps

1. **Run the installer** to update the database schema with new collections
2. **Update frontend** to use the new APIs
3. **Add file upload handling** for complaint photos (if using file uploads)
4. **Create uploads directory**: `mkdir -p uploads/complaints` and set proper permissions

## Notes

- All APIs include proper authentication and authorization checks
- Owners can see all data for their properties
- Tenants can only see their own data
- File uploads are stored in `uploads/complaints/` directory
- Base64 images are also supported for complaint photos
- Due rent calculation is based on monthly rent and payment history

