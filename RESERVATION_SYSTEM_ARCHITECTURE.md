# 🏗️ Reservation System Architecture

## 📐 System Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                           USER INTERFACE                            │
│                         (index.php - Form)                          │
│                                                                     │
│  [Name] [Phone] [Email] [Date] [Time] [Guests]  [Submit Button]   │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             │ POST (No Client-Side Validation)
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    SUBMIT_RESERVATIONS.PHP                          │
│                   (Server-Side Processing)                          │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                  CONFIGURATION LAYER                         │  │
│  │  • TIMEZONE: Europe/London                                  │  │
│  │  • MIN_ADVANCE_HOURS: 20                                    │  │
│  │  • OPERATING_HOURS: Array of 7 days with AM/PM display     │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                             │                                       │
│  ┌──────────────────────────▼──────────────────────────────────┐  │
│  │           VALIDATION PIPELINE (8 Steps)                     │  │
│  │                                                              │  │
│  │  1️⃣  validateRequiredFields()                              │  │
│  │      └─ Checks: name, phone, email, date, time, guests    │  │
│  │                                                              │  │
│  │  2️⃣  validateEmail()                                       │  │
│  │      └─ Uses FILTER_VALIDATE_EMAIL                         │  │
│  │                                                              │  │
│  │  3️⃣  validatePhone()                                       │  │
│  │      └─ Extracts digits, checks 10-15 range              │  │
│  │                                                              │  │
│  │  4️⃣  validateDateTime()                                    │  │
│  │      └─ Parses Y-m-d H:i format, UK timezone             │  │
│  │                                                              │  │
│  │  5️⃣  validateNotInPast()                                   │  │
│  │      └─ Checks against current UK time                     │  │
│  │                                                              │  │
│  │  6️⃣  validateAdvanceBooking() ⭐ NEW                       │  │
│  │      └─ Requires 20+ hours in advance                      │  │
│  │      └─ Returns: Current time, Min time, Selected time     │  │
│  │                                                              │  │
│  │  7️⃣  validateOpeningHours() ⭐ ENHANCED                    │  │
│  │      └─ Checks within business hours (with AM/PM)          │  │
│  │      └─ Returns: Comprehensive debug info                  │  │
│  │                                                              │  │
│  │  8️⃣  Guest count (1-20)                                   │  │
│  │      └─ Range validation                                   │  │
│  │                                                              │  │
│  └──────────────────────────┬───────────────────────────────────┘  │
│                             │                                       │
│                    ⚠️ Validation Failed?                            │
│                    ├─ NO: Continue to next step                     │
│                    └─ YES: Return 400 Error with message           │
│                             │                                       │
│  ┌──────────────────────────▼──────────────────────────────────┐  │
│  │           PROCESSING PIPELINE (4 Steps)                     │  │
│  │                                                              │  │
│  │  STEP 1: Database Insertion                                │  │
│  │  ├─ Sanitize with htmlspecialchars()                       │  │
│  │  ├─ Use prepared statements (SQL injection safe)           │  │
│  │  ├─ Insert into reservations table                         │  │
│  │  ├─ Status: 'Pending'                                      │  │
│  │  └─ Capture reservation_id                                 │  │
│  │                                                              │  │
│  │  STEP 2: Comprehensive Logging                             │  │
│  │  ├─ Create log entry with 12+ fields                       │  │
│  │  ├─ Include: ID, timestamp, day, customer, booking, etc.   │  │
│  │  ├─ Append to reservation_logs.json                        │  │
│  │  └─ Non-critical: won't fail if write fails               │  │
│  │                                                              │  │
│  │  STEP 3: Email Queue Trigger 📧 UNTOUCHED                  │  │
│  │  ├─ Create: email_queue_request_{id}_{timestamp}.json      │  │
│  │  ├─ Contains: All reservation details + email_type         │  │
│  │  ├─ Picked up by: Separate email processing system         │  │
│  │  └─ Status: Email system is independent                    │  │
│  │                                                              │  │
│  │  STEP 4: Success Response                                  │  │
│  │  ├─ HTTP 200 OK                                            │  │
│  │  ├─ Return: reservation_id, dates, confirmation message    │  │
│  │  └─ Include: Friendly confirmation text                    │  │
│  │                                                              │  │
│  └──────────────────────────┬───────────────────────────────────┘  │
│                             │                                       │
│  ⚠️ Error During Processing?                                       │
│  ├─ YES: Return 500 with user-friendly message                     │
│  └─ NO: Continue to success response                               │
│                                                                     │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                    Return JSON Response
                    {
                      "success": true/false,
                      "message": "...",
                      "reservation_id": 123,
                      ...
                    }
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    CLIENT-SIDE (index.php)                          │
│                                                                     │
│  If success:                                                        │
│  ├─ Show confetti animation 🎉                                    │
│  ├─ Show success modal                                             │
│  └─ Reset form                                                      │
│                                                                     │
│  If error:                                                          │
│  ├─ Show toast notification                                        │
│  └─ Display error message (with AM/PM times if applicable)         │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Data Flow & Storage

```
DATABASE                     FILE SYSTEM                  EMAIL SYSTEM
─────────                    ───────────                  ────────────

reservations                 reservation_logs.json        (Independent)
├─ ID                        ├─ Array of entries         │
├─ customer_name             ├─ reservation_id           │
├─ phone_number              ├─ timestamp                │
├─ email                     ├─ day_of_week              │ email_queue_
├─ num_guests                ├─ customer info            │ request_*.json
├─ reservation_date          ├─ IP address               │ ├─ ID
├─ reservation_time          ├─ User-Agent               │ ├─ customer_email
├─ status: 'Pending'         └─ validation details       │ ├─ details
└─ created_at                                            │ └─ timestamp
                                                          │
                             When file created:           │
                             Separate email system        │
                             picks it up and sends ──────┘
                             confirmation email
```

---

## ⏰ Validation Decision Tree

```
                            FORM SUBMISSION
                                  │
                                  ▼
                    All fields present?
                    ├─ NO → Error: Missing field
                    └─ YES ▼
                         Email valid format?
                         ├─ NO → Error: Invalid email
                         └─ YES ▼
                              Phone 10-15 digits?
                              ├─ NO → Error: Invalid phone
                              └─ YES ▼
                                   DateTime parseable?
                                   ├─ NO → Error: Invalid format
                                   └─ YES ▼
                                        In the past?
                                        ├─ YES → Error: Past reservation
                                        └─ NO ▼
                                             20+ hours advance? ⭐
                                             ├─ NO → Error: Not enough advance
                                             │        (shows times with AM/PM)
                                             └─ YES ▼
                                                  Within opening hours? ⭐
                                                  ├─ NO → Error: Outside hours
                                                  │        (shows comprehensive debug)
                                                  └─ YES ▼
                                                       Guest count 1-20?
                                                       ├─ NO → Error: Invalid count
                                                       └─ YES ▼
                                                            ✅ ALL VALID
                                                            PROCESS RESERVATION
```

---

## 🕐 Operating Hours Configuration

```
const OPERATING_HOURS = [
    ↓
┌─────────────────────────────────────────────────────┐
│  Day Index  │  Day Name      │  Display              │
├─────────────┼────────────────┼─────────────────────┤
│      0      │  Sunday        │ 9:00 AM - 9:00 PM    │
│      1      │  Monday        │ 8:00 AM - 5:00 PM    │
│      2      │  Tuesday       │ 8:00 AM - 10:00 PM   │
│      3      │  Wednesday     │ 8:00 AM - 10:00 PM   │
│      4      │  Thursday      │ 8:00 AM - 10:00 PM   │
│      5      │  Friday        │ 8:00 AM - 10:00 PM   │
│      6      │  Saturday      │ 9:00 AM - 11:00 PM   │
└─────────────────────────────────────────────────────┘
    ↓
Each entry has:
├─ 'opens': 24-hour format (e.g., '09:00')
├─ 'closes': 24-hour format (e.g., '21:00')
└─ 'display': User-friendly with AM/PM (e.g., '9:00 AM - 9:00 PM')
```

---

## 🔒 Security Layers

```
INPUT VALIDATION                 DATA PROCESSING           OUTPUT SANITIZATION
─────────────────                ───────────────           ──────────────────

User Input ▶─┬─ Type Check      ▶─┬─ Prepared Stmt       ▶─┬─ Error Response
             ├─ Format Check     ├─ HTML Sanitize        ├─ No sys details
             ├─ Range Check      ├─ Timezone Safe        ├─ User-friendly
             └─ Existence Check  └─ IP Logging           └─ JSON format
                        ▼                       ▼                      ▼
            ✅ SQL Injection    ✅ XSS            ✅ Info Leakage
            Safe               Safe              Prevented
```

---

## 📊 Response Format Flow

```
┌─────────────────────────────────────────────────────────┐
│           validateReservation()                         │
│                                                         │
│  Returns: ['valid' => true/false, ...]                │
│                                                         │
└────┬────────────────────────────────────────────────────┘
     │
     ├─ YES (valid: false)
     │  │
     │  └─► HTTP 400
     │      {
     │        "success": false,
     │        "message": "Detailed error message"
     │      }
     │
     └─ NO (valid: true)
        │
        └─► Database Insert & Log
            │
            ├─ Success
            │  └─► HTTP 200
            │      {
            │        "success": true,
            │        "message": "✅ Reservation confirmed...",
            │        "reservation_id": 123,
            │        "reservation_date": "2025-11-20",
            │        "reservation_time": "15:00",
            │        "confirmation_sent": true
            │      }
            │
            └─ Error
               └─► HTTP 500
                   {
                     "success": false,
                     "message": "An error occurred...",
                     "error_detail": "Technical details",
                     ...
                   }
```

---

## 🔄 Complete Lifecycle

```
USER BOOKS RESERVATION
│
├─► Form submitted from index.php
│
├─► POST to submit_reservations.php
│
├─► Comprehensive validation (8 steps)
│   ├─ Check fields
│   ├─ Check email
│   ├─ Check phone
│   ├─ Parse datetime
│   ├─ Check not past
│   ├─ Check 20+ hours advance ⭐
│   ├─ Check opening hours ⭐
│   └─ Check guest count
│
├─► Validation failed?
│   ├─ YES: Return error (400) → User sees error toast
│   └─ NO: Continue
│
├─► Database: Insert reservation
│   └─ Status: 'Pending'
│
├─► File: Write to reservation_logs.json
│   └─ Comprehensive log entry created
│
├─► File: Create email_queue_request_{id}_{ts}.json
│   └─ Email system picks it up
│
├─► Response: Send success (200)
│   └─ Include reservation_id
│
├─► Client: Show success modal + confetti
│
└─► Email system: Sends confirmation email (separate)
```

---

## 🎯 Validation Results Matrix

```
Input Scenario              │ Result         │ Message Format
───────────────────────────┼────────────────┼──────────────────
Valid reservation          │ ✅ Success     │ JSON with ID
Missing field              │ ❌ Error       │ Field name
Invalid email format       │ ❌ Error       │ Format issue
Invalid phone (too short)  │ ❌ Error       │ Digit count
Invalid datetime format    │ ❌ Error       │ Format issue
Past date selected         │ ❌ Error       │ Past notice
Less than 20 hours         │ ❌ Error       │ Current/Min/Selected
Outside opening hours      │ ❌ Error       │ Full debug info
Guest count invalid        │ ❌ Error       │ Range (1-20)
Database error             │ ❌ Error       │ User-friendly only
```

---

## 🚀 File Organization

```
/Apps/haveli/
│
├─ index.php
│  ├─ Reservation form (lines ~2580-2615)
│  ├─ Form submission handler (lines ~4425-4470)
│  └─ No client-side validation ✓
│
├─ submit_reservations.php ⭐ MAIN FILE
│  ├─ Configuration (lines 17-35)
│  ├─ Validation functions (lines 38-240)
│  ├─ Main orchestrator (lines 243-262)
│  └─ Processing & error handling (lines 265-387)
│
├─ db_config.php
│  └─ Database connection
│
├─ reservation_logs.json
│  └─ Appended log file (created on first reservation)
│
├─ email_queue_request_*.json (created per reservation)
│  └─ Picked up by email system
│
└─ Documentation
   ├─ RESERVATION_SYSTEM_REBUILD.md
   ├─ RESERVATION_TESTING_GUIDE.md
   ├─ RESERVATION_QUICK_REFERENCE.md
   ├─ RESERVATION_BEFORE_AFTER.md
   └─ RESERVATION_SYSTEM_ARCHITECTURE.md (this file)
```

---

**Last Updated:** November 18, 2025
**Status:** ✅ Complete Architecture Documentation
