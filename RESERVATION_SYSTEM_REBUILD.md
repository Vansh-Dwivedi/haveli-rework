# 🔄 Reservation System - Complete Rebuild

## Overview
The reservation system has been completely rebuilt with a clean, modular architecture focusing on:
- ✅ Robust validation at every step
- ✅ Clear separation of concerns
- ✅ Comprehensive error messaging with AM/PM formatting
- ✅ Preserved email queue system (untouched)
- ✅ Enhanced logging for debugging

---

## 📋 Operating Hours (with AM/PM)

```
Monday:       8:00 AM - 5:00 PM
Tuesday:      8:00 AM - 10:00 PM
Wednesday:    8:00 AM - 10:00 PM
Thursday:     8:00 AM - 10:00 PM
Friday:       8:00 AM - 10:00 PM
Saturday:     9:00 AM - 11:00 PM
Sunday:       9:00 AM - 9:00 PM
```

---

## 🔐 Validation Pipeline

### 1. **Required Fields Check**
- Validates: name, phone, email, date, time, guests
- Returns: Clear message if any field is missing

### 2. **Email Validation**
- Uses PHP's `FILTER_VALIDATE_EMAIL`
- Rejects invalid formats

### 3. **Phone Validation**
- Extracts digits only
- Requires: 10-15 digits
- Accepts international formats

### 4. **Date/Time Format Validation**
- Expects: `YYYY-MM-DD` and `HH:MM` format
- Timezone: Europe/London (UK)
- Parses and validates DateTime objects

### 5. **Past Date Prevention**
- Ensures reservation is not in the past
- Uses UK timezone for comparison

### 6. **Advance Booking Requirement** ⏰
- **Minimum: 20 hours in advance**
- Comprehensive error message showing:
  - Current time (UK timezone)
  - Earliest available time
  - Selected time
  - All in user-friendly format (AM/PM)

### 7. **Opening Hours Validation** 🕐
- Checks if reservation falls within business hours
- **With comprehensive debugging:**
  - Day of week
  - Operating hours display (with AM/PM)
  - Selected time (with AM/PM)
  - Current time (UK)
  - Requested time (UK)
  - Opening/closing times (UK)
  - 24-hour format times
  - Comparison logic (< opening? > closing?)

### 8. **Guest Count Validation**
- Range: 1-20 guests
- Returns error if outside range

---

## 💾 Processing Steps

### Step 1: Database Insertion
- Sanitizes all inputs with `htmlspecialchars()`
- Uses prepared statements (SQL injection safe)
- Inserts into `reservations` table with status "Pending"
- Captures reservation_id for tracking

### Step 2: Comprehensive Logging
- Creates detailed log entry with:
  - Reservation ID
  - Timestamp
  - Day of week name
  - Customer details
  - Booking details
  - IP address & User Agent
  - Validation status
  - System notes
- Appends to `reservation_logs.json`
- Non-critical: won't fail if log write fails

### Step 3: Email Queue Trigger (SEPARATE SYSTEM)
- Creates email queue file: `email_queue_request_{id}_{timestamp}.json`
- Contains all reservation details
- Email sending system picks this up independently
- ✅ **UNTOUCHED - Original email system preserved**

### Step 4: Success Response
- HTTP 200 OK
- Friendly confirmation message
- Returns reservation_id
- Indicates confirmation email sent

---

## 📊 Error Response Format

All validation errors follow this pattern:
```json
{
  "success": false,
  "message": "Detailed user-friendly error message"
}
```

Examples:
```
❌ Selected time is outside opening hours.

📅 Day: Saturday (November 22, 2025)
🕐 Operating Hours: 9:00 AM - 11:00 PM
⏰ You selected: 3:15 PM

📊 Debug Information:
• Current Time (UK): 2025-11-18 14:30:00
• Requested Time (UK): 2025-11-22 15:15:00
• Opening Time (UK): 2025-11-22 09:00:00
• Closing Time (UK): 2025-11-22 23:00:00
• Day of Week (0-6): 6
• Time in 24h format: 15:15
• Comparison: reservationTime < opening? NO
• Comparison: reservationTime > closing? NO
```

---

## 📁 File Outputs

### Database Table: `reservations`
```sql
INSERT INTO reservations 
(customer_name, phone_number, email, num_guests, reservation_date, reservation_time, status, created_at)
VALUES (...)
```

### JSON Log: `reservation_logs.json`
```json
{
  "reservation_id": 123,
  "timestamp": "2025-11-18 14:30:45",
  "day_of_week": "Saturday",
  "customer_name": "John Doe",
  "phone": "+44 123 456 7890",
  "email": "john@example.com",
  "reservation_date": "2025-11-22",
  "reservation_time": "15:15",
  "num_guests": 4,
  "status": "Pending",
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "validation_passed": true,
  "system_notes": "Advance booking: 20+ hours required | Validated successfully"
}
```

### Email Queue: `email_queue_request_{id}_{timestamp}.json`
```json
{
  "reservation_id": 123,
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+44 123 456 7890",
  "reservation_date": "2025-11-22",
  "reservation_time": "15:15",
  "num_guests": 4,
  "timestamp": "2025-11-18 14:30:45",
  "email_type": "confirmation"
}
```

---

## 🔧 Configuration

All settings are at the top of `submit_reservations.php`:

```php
const TIMEZONE = 'Europe/London';
const MIN_ADVANCE_HOURS = 20;

const OPERATING_HOURS = [
    0 => ['opens' => '09:00', 'closes' => '21:00', 'display' => '9:00 AM - 9:00 PM'],     // Sunday
    1 => ['opens' => '08:00', 'closes' => '17:00', 'display' => '8:00 AM - 5:00 PM'],     // Monday
    // ... etc
];
```

**To modify:**
1. Change `MIN_ADVANCE_HOURS` for advance booking requirement
2. Update `OPERATING_HOURS` array for business hours (24-hour format)
3. Always include 'display' key with user-friendly format

---

## 🚀 Client-Side Changes

In `index.php`, the form submission has been simplified:
- ❌ Removed all client-side validation
- ✅ No phone format checks
- ✅ No date/time checks
- ✅ No opening hours checks
- ✅ Direct form submission to server

**All validation now happens server-side** (more secure!)

---

## ✨ Key Improvements

1. **Modular Functions** - Each validation is a separate, testable function
2. **Clear Error Messages** - Users see exactly what went wrong
3. **Comprehensive Debugging** - Full timestamps, timezone info, comparisons
4. **AM/PM Display** - All times shown in user-friendly format
5. **Preserved Email System** - Email queue completely untouched
6. **Better Logging** - Detailed records for troubleshooting
7. **SQL Injection Safe** - Prepared statements throughout
8. **XSS Safe** - HTML sanitization with `htmlspecialchars()`

---

## 🐛 Troubleshooting

### Problem: "Selected time is outside opening hours" for valid times

**Check:**
1. Verify `OPERATING_HOURS` configuration matches actual hours
2. Ensure timezone is correct: `'Europe/London'`
3. Check debug info in error message for actual times being compared
4. Verify server time is accurate

### Problem: "Reservations must be made at least 20 hours in advance"

**Check:**
1. Current time vs earliest available time in error message
2. May need to adjust `MIN_ADVANCE_HOURS` constant
3. Verify UK timezone offset is correct

### Problem: Email not being sent

**Check:**
1. Email queue file was created: `email_queue_request_*.json`
2. Separate email processing system is running
3. Queue file is in correct location
4. Email queue processor permissions are correct

---

## 📝 Version History

- **v1.0** - Complete system rebuild with modular validation, AM/PM formatting, comprehensive debugging
- Original functionality preserved: Database storage, Email queue system, Logging

---

**Last Updated:** November 18, 2025
**Status:** ✅ Production Ready
