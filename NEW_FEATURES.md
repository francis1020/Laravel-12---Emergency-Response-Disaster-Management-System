# Emergency Response System - New Features Documentation

This document describes all the new features and functions added to the Emergency Response System.

## 📸 1. Photo/Media Upload System

### Overview
Users and responders can now upload photos, videos, and documents to emergency reports for better context and documentation.

### Features
- Upload multiple files per report (images, videos, documents)
- Automatic thumbnail generation for images
- File type validation and size limits (10MB max)
- Secure file storage in `storage/app/public/emergency-reports/{reportId}/`
- Support for: JPEG, PNG, GIF, MP4, AVI, MOV, PDF, DOC, DOCX

### API Endpoints
- `POST /reports/{reportId}/media` - Upload media files
- `GET /reports/{reportId}/media` - Get all media for a report
- `DELETE /media/{mediaId}` - Delete a media file

### Database
- **Table**: `emergency_report_media`
- **Model**: `App\Models\EmergencyReportMedia`

### Usage Example
```javascript
const formData = new FormData();
formData.append('files[]', file1);
formData.append('files[]', file2);

fetch('/reports/123/media', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': token
    }
});
```

---

## 👥 2. Multi-Responder Assignment System

### Overview
Multiple responders can now be assigned to a single emergency report, with role-based assignments (primary, secondary, support).

### Features
- Assign multiple responders to one report
- Role-based assignments: primary, secondary, support
- Individual status tracking per responder (assigned, en_route, on_scene, completed, cancelled)
- Automatic timestamp tracking for each status change
- Response time calculation per responder

### API Endpoints
- `POST /reports/{reportId}/responders/assign` - Assign responders
- `GET /reports/{reportId}/responders` - Get all assigned responders
- `PATCH /responder-assignments/{assignmentId}/status` - Update responder status
- `DELETE /responder-assignments/{assignmentId}` - Remove responder

### Database
- **Table**: `emergency_report_responders`
- **Model**: `App\Models\EmergencyReportResponder`

### Usage Example
```javascript
// Assign multiple responders
POST /reports/123/responders/assign
{
    "responder_ids": [1, 2, 3],
    "roles": ["primary", "secondary", "support"]
}

// Update responder status
PATCH /responder-assignments/456/status
{
    "status": "on_scene",
    "notes": "Arrived at location"
}
```

---

## 🎯 3. Priority Queue System

### Overview
Automatic priority scoring system that ranks emergency reports based on severity, type, age, and assignment status.

### Features
- Automatic priority score calculation
- Factors considered:
  - Severity level (low: 10, moderate: 30, high: 60, critical: 100)
  - Emergency type (medical: +20, fire: +25, etc.)
  - Report age (older reports get higher priority)
  - Unassigned reports get +15 bonus
- Priority score stored in database for sorting/filtering

### Implementation
- Calculated automatically when report is created
- Stored in `priority_score` field
- Can be used for sorting: `EmergencyReport::orderBy('priority_score', 'desc')`

### Database
- **Field**: `priority_score` (integer) in `emergency_reports` table

---

## 🚗 4. Resource Management System

### Overview
Track and manage emergency response resources including vehicles, equipment, personnel, and facilities.

### Features
- Resource types: vehicle, equipment, personnel, facility
- Status tracking: available, in_use, maintenance, unavailable
- Location tracking (latitude/longitude)
- Assignment to users and reports
- Metadata field for flexible additional data
- Resource utilization analytics

### API Endpoints
- `GET /resources` - List all resources (with filters)
- `POST /resources` - Create new resource (Admin only)
- `PATCH /resources/{id}` - Update resource
- `POST /resources/{resourceId}/assign` - Assign resource to report
- `POST /resources/{resourceId}/release` - Release resource
- `DELETE /resources/{id}` - Delete resource (Admin only)

### Database
- **Table**: `resources`
- **Model**: `App\Models\Resource`

### Usage Example
```javascript
// Create a resource
POST /resources
{
    "name": "Ambulance #1",
    "type": "vehicle",
    "identifier": "AMB-001",
    "status": "available"
}

// Assign to report
POST /resources/1/assign
{
    "report_id": 123
}
```

---

## 💬 5. Real-Time Chat/Messaging System

### Overview
Real-time messaging system for communication between reporters, responders, and admins during emergency response.

### Features
- Real-time message broadcasting via WebSockets
- Message types: text, system, status_update
- Read/unread status tracking
- File attachments support
- Role-based access control
- Unread message count API

### API Endpoints
- `POST /reports/{reportId}/messages` - Send a message
- `GET /reports/{reportId}/messages` - Get all messages
- `PATCH /messages/{messageId}/read` - Mark message as read
- `GET /messages/unread-count` - Get unread count for current user

### Database
- **Table**: `messages`
- **Model**: `App\Models\Message`
- **Event**: `App\Events\MessageSent`

### Broadcasting
- **Channel**: `emergency-report.{reportId}`
- **Event**: `message.sent`

### Usage Example
```javascript
// Send message
POST /reports/123/messages
{
    "message": "ETA: 5 minutes",
    "type": "text"
}

// Listen for messages
Echo.channel('emergency-report.123')
    .listen('.message.sent', (e) => {
        console.log('New message:', e.message);
    });
```

---

## 🔍 6. Duplicate Report Detection

### Overview
Automatically detects and flags duplicate emergency reports to prevent redundant responses.

### Features
- Automatic duplicate detection on report creation
- Checks for reports within 100 meters and 5 minutes
- Links duplicate reports to original
- Uses Haversine formula for distance calculation
- Prevents duplicate responses

### Implementation
- Detected in `EmergencyReportController::store()`
- Stores `is_duplicate` flag and `original_report_id`
- Returns warning message if duplicate detected

### Database
- **Fields**: `is_duplicate` (boolean), `original_report_id` (foreign key)

---

## 📊 7. Enhanced Analytics Dashboard

### Overview
Comprehensive analytics system providing detailed insights into emergency response operations.

### Features
- **Overview Statistics**: Total reports, by status, average priority, duplicates
- **Reports by Type**: Distribution of emergency types
- **Reports by Severity**: Severity level breakdown
- **Reports by Status**: Status distribution
- **Response Time Statistics**: Average, min, max, median response times
- **Responder Performance**: Top responders by assignment count
- **Resource Utilization**: Resource availability and usage rates
- **Trends**: Daily report counts over time
- **Geographic Distribution**: Heat map data of report locations

### API Endpoint
- `GET /analytics?period=30` - Get analytics (period in days, default 30)

### Usage Example
```javascript
// Get analytics for last 30 days
GET /analytics?period=30

// Response includes:
{
    "overview": { ... },
    "by_type": [ ... ],
    "response_times": { ... },
    "trends": [ ... ],
    ...
}
```

---

## 📱 8. SMS Notification Service

### Overview
SMS notification service for sending alerts to emergency contacts and responders.

### Features
- Send SMS to emergency contacts
- Send SMS to assigned responders
- Placeholder implementation ready for integration
- Supports Twilio, Nexmo/Vonage, AWS SNS, or custom gateway

### Service
- **Class**: `App\Services\SmsService`
- **Methods**:
  - `send($to, $message)` - Send SMS
  - `sendToEmergencyContact($report)` - Send to reporter
  - `sendToResponder($responder, $report)` - Send to responder

### Integration
To integrate with Twilio, add to `config/services.php`:
```php
'twilio' => [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_FROM'),
],
```

### Usage Example
```php
use App\Services\SmsService;

$smsService = new SmsService();
$smsService->sendToEmergencyContact($report);
$smsService->sendToResponder($responder, $report);
```

---

## 📍 9. Geofencing Alerts

### Overview
Location-based alert system that notifies when responders enter or exit the geofence area around an emergency.

### Features
- Automatic geofence detection (default 500m radius)
- Entry/exit alerts
- Real-time broadcasting via WebSockets
- Distance calculation using Haversine formula
- Configurable radius per report

### Service
- **Class**: `App\Services\GeofencingService`
- **Event**: `App\Events\GeofenceAlert`

### Broadcasting
- **Channel**: `geofence-alerts`
- **Event**: `geofence.alert`

### Usage Example
```php
use App\Services\GeofencingService;

$geofencing = new GeofencingService();
$result = $geofencing->checkGeofenceStatus($responderId, $reportId, $lat, $lng);

if ($result) {
    // Responder entered/exited geofence
    // $result['action'] = 'entered' or 'exited'
    // $result['distance'] = distance in km
}
```

---

## 🗄️ Database Migrations

All new features require database migrations. Run:

```bash
php artisan migrate
```

### New Tables Created:
1. `emergency_report_media` - Media files
2. `emergency_report_responders` - Multi-responder assignments
3. `resources` - Resource management
4. `messages` - Chat/messaging

### Modified Tables:
- `emergency_reports` - Added `priority_score`, `is_duplicate`, `original_report_id`

---

## 🔐 Security & Permissions

### Access Control
- **Media Upload**: Report owner, admins, assigned responders
- **Multi-Responder Assignment**: Admins, primary responder
- **Resource Management**: Admins (create/delete), assigned users (update)
- **Messages**: Report owner, assigned responders, admins
- **Analytics**: All authenticated users (filtered by role)

### Validation
- File uploads: Type and size validation
- Location data: Coordinate validation
- User permissions: Role-based checks on all endpoints

---

## 🚀 Getting Started

1. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Configure Storage**:
   ```bash
   php artisan storage:link
   ```

3. **Set Up Broadcasting** (for real-time features):
   - Configure Pusher or Laravel Echo Server
   - Update `.env` with broadcasting credentials

4. **Optional: Configure SMS Service**:
   - Add SMS provider credentials to `.env`
   - Update `SmsService` with your provider

---

## 📝 API Documentation Summary

### New Routes Added:

```
POST   /reports/{reportId}/media
GET    /reports/{reportId}/media
DELETE /media/{mediaId}

POST   /reports/{reportId}/responders/assign
GET    /reports/{reportId}/responders
PATCH  /responder-assignments/{assignmentId}/status
DELETE /responder-assignments/{assignmentId}

POST   /reports/{reportId}/messages
GET    /reports/{reportId}/messages
PATCH  /messages/{messageId}/read
GET    /messages/unread-count

GET    /resources
POST   /resources
PATCH  /resources/{id}
POST   /resources/{resourceId}/assign
POST   /resources/{resourceId}/release
DELETE /resources/{id}

GET    /analytics
```

---

## 🎯 Next Steps & Enhancements

1. **Frontend Implementation**: Create views for all new features
2. **Real-time UI**: Implement WebSocket listeners for chat and alerts
3. **Mobile App API**: Create API endpoints for mobile applications
4. **Advanced Analytics**: Add charts and visualizations
5. **Notification Preferences**: User settings for SMS/email preferences
6. **Report Templates**: Pre-defined report templates
7. **Automated Dispatch**: AI-based responder assignment
8. **Integration APIs**: Third-party service integrations

---

## 📞 Support

For questions or issues with the new features, refer to:
- Model files in `app/Models/`
- Controller files in `app/Http/Controllers/`
- Service classes in `app/Services/`
- Event classes in `app/Events/`

All code follows Laravel best practices and includes comprehensive error handling.

