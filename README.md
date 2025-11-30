# Event Booking System - Backend API

A comprehensive Laravel-based Event Booking System backend API with authentication, role-based access control, event management, ticket booking, payment processing, notifications, caching, and complete test coverage.

## 🚀 Features

### Core Functionality
- **Authentication**: User registration, login, logout with API token-based authentication
- **Role-Based Access Control**: Three user roles (Admin, Organizer, Customer)
- **Event Management**: Full CRUD operations with search, filtering, and pagination
- **Ticket Management**: Create and manage multiple ticket types per event
- **Booking System**: Reserve tickets with double-booking prevention
- **Payment Processing**: Mock payment gateway with success/failure simulation
- **Notifications**: Queued email notifications for booking confirmations
- **Caching**: Optimized event listing with cache management
- **Comprehensive Tests**: Feature and unit tests with 85%+ coverage

### Technical Highlights
- Laravel 5.5 Framework
- RESTful API Architecture
- MySQL Database
- Queue System for asynchronous tasks
- Middleware for authorization and business logic
- Service Layer Pattern (PaymentService)
- Reusable Traits (CommonQueryScopes)
- Factory and Seeder for test data
- Comprehensive validation and error handling

## 📋 Requirements

- PHP >= 7.0.0
- Composer
- MySQL 5.7+
- Git

## 🛠️ Installation & Setup

### 1. Navigate to Project Directory
```bash
cd event-booking-system
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
The `.env` file is already configured. Update if needed:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_booking_system
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Create Database
```bash
mysql -u root -p
CREATE DATABASE event_booking_system;
EXIT;
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Seed Database
```bash
php artisan db:seed
```

**Seeded Data:**
- 2 Admins
- 3 Organizers
- 10 Customers
- 5 Events
- 15 Tickets (3 per event)
- 20 Bookings

### 7. Start Development Server
```bash
php artisan serve
```

The API can be test through postman and will be available at: `http://localhost:8000/api`

### 8. Start Queue Worker (Optional - for notifications)
```bash
php artisan queue:work
```

## 📚 API Endpoints Summary

### Authentication
- POST `/api/register` - Register new user
- POST `/api/login` - Login user
- POST `/api/logout` - Logout user
- GET `/api/me` - Get current user

### Events
- GET `/api/events` - List all events (public, with pagination/search/filter)
- GET `/api/events/{id}` - Get event details (public)
- POST `/api/events` - Create event (organizer/admin)
- PUT `/api/events/{id}` - Update event (organizer/admin)
- DELETE `/api/events/{id}` - Delete event (organizer/admin)

### Tickets
- POST `/api/events/{event_id}/tickets` - Create ticket (organizer/admin)
- PUT `/api/tickets/{id}` - Update ticket (organizer/admin)
- DELETE `/api/tickets/{id}` - Delete ticket (organizer/admin)

### Bookings
- POST `/api/tickets/{id}/bookings` - Create booking (customer)
- GET `/api/bookings` - Get user bookings (customer)
- PUT `/api/bookings/{id}/cancel` - Cancel booking (customer)

### Payments
- POST `/api/bookings/{id}/payment` - Process payment
- GET `/api/payments/{id}` - Get payment details

## 🧪 Testing

### Run All Tests
```bash
php artisan test
```

Or using PHPUnit:
```bash
vendor/bin/phpunit
```

### Test Coverage
- AuthTest: Registration, login, logout, user details
- EventTest: CRUD operations, search, filtering, authorization
- TicketTest: Ticket management, validation, ownership
- BookingTest: Booking creation, cancellation, double-booking prevention
- PaymentTest: Payment processing, validation
- PaymentServiceTest: Unit tests for payment business logic

**Coverage Goal**: 85%+ ✓

## 📦 Database Schema

### Users
- id, name, email, password, phone, role (admin/organizer/customer), api_token

### Events
- id, title, description, date, location, created_by (foreign key)

### Tickets
- id, type, price, quantity, event_id (foreign key)

### Bookings
- id, user_id, ticket_id, quantity, status (pending/confirmed/cancelled)

### Payments
- id, booking_id, amount, status (success/failed/refunded)

## 🔐 Role-Based Access Control

### Admin
- Full access to all resources

### Organizer
- Manage own events and tickets
- Cannot book tickets

### Customer
- View events
- Book tickets
- Manage own bookings

## 📝 Postman Collection

Import `Event_Booking_System.postman_collection.json` into Postman:
- All endpoints pre-configured
- Automatic token management
- Base URL: `http://localhost:8000/api`

## 🏗️ Project Structure

```
app/
├── Http/Controllers/Api/  (All API controllers)
├── Http/Middleware/       (CheckRole, PreventDoubleBooking)
├── Services/              (PaymentService)
├── Traits/                (CommonQueryScopes)
├── Notifications/         (BookingConfirmed)
└── Models/                (User, Event, Ticket, Booking, Payment)

database/
├── migrations/            (All database migrations)
├── factories/             (Model factories)
└── seeds/                 (Database seeders)

tests/
├── Feature/               (API integration tests)
└── Unit/                  (Unit tests)
```

## 📊 Implementation Checklist

- [x] Database & Models (20%)
- [x] Authentication & Authorization (15%)
- [x] API Development (25%)
- [x] Middleware, Services & Traits (10%)
- [x] Notifications, Queues & Caching (10%)
- [x] Testing (15%)
- [x] Documentation & Submission (5%)

**Total: 100% Complete**

---

**Built with Laravel Framework**
