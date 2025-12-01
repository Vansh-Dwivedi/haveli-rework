# 🧪 Reservation System - Testing Guide

## Quick Test Cases

### ✅ Valid Reservation
```
Name: John Doe
Phone: +44 1753 123456
Email: john@example.com
Date: 2025-11-20 (must be 20+ hours away)
Time: 15:00 (3:00 PM on a Saturday = within 9 AM - 11 PM)
Guests: 4
```

**Expected Response:**
```json
{
  "success": true,
  "message": "✅ Reservation confirmed! We've received your booking request for Saturday at 15:00. You will receive a confirmation email shortly.",
  "reservation_id": 123,
  "reservation_date": "2025-11-20",
  "reservation_time": "15:00",
  "confirmation_sent": true
}
```

---

## ❌ Invalid Test Cases

### Test 1: Past Date
```
Date: 2025-11-15 (today is 2025-11-18)
Time: 14:00
```
**Expected Error:** `Reservations cannot be made in the past.`

---

### Test 2: Not Enough Advance Notice
```
Date: 2025-11-18 (today)
Time: 18:00 (only ~4 hours away)
```
**Expected Error:** (includes current time, minimum time, selected time with AM/PM)
```
Reservations must be made at least 20 hours in advance. 
Current time: 2025-11-18 02:00 PM. 
Earliest available: 2025-11-19 10:00 AM. 
You selected: 2025-11-18 06:00 PM.
```

---

### Test 3: Outside Opening Hours
```
Date: 2025-11-20 (Saturday)
Time: 02:00 (2:00 AM - way too early)
```
**Expected Error:** (with full debugging)
```
❌ Selected time is outside opening hours.

📅 Day: Saturday (November 20, 2025)
🕐 Operating Hours: 9:00 AM - 11:00 PM
⏰ You selected: 2:00 AM

📊 Debug Information:
• Current Time (UK): 2025-11-18 14:30:00
• Requested Time (UK): 2025-11-20 02:00:00
• Opening Time (UK): 2025-11-20 09:00:00
• Closing Time (UK): 2025-11-20 23:00:00
• Day of Week (0-6): 6
• Time in 24h format: 02:00
• Comparison: reservationTime < opening? YES
• Comparison: reservationTime > closing? NO
```

---

### Test 4: Monday 4:00 PM (Outside Hours)
```
Date: 2025-11-24 (Monday)
Time: 16:00 (4:00 PM - but Monday closes at 5:00 PM... should work!)
```
**Expected:** ✅ Should succeed (16:00 is before closing at 17:00)

---

### Test 5: Monday 5:15 PM (After Hours)
```
Date: 2025-11-24 (Monday)
Time: 17:15 (5:15 PM - after 5:00 PM closing)
```
**Expected Error:**
```
❌ Selected time is outside opening hours.

📅 Day: Monday (November 24, 2025)
🕐 Operating Hours: 8:00 AM - 5:00 PM
⏰ You selected: 5:15 PM
```

---

### Test 6: Invalid Email
```
Email: not-an-email
```
**Expected Error:** `Invalid email address format.`

---

### Test 7: Invalid Phone
```
Phone: 123
```
**Expected Error:** `Phone number must contain 10-15 digits.`

---

### Test 8: Missing Fields
```
Name: (empty)
```
**Expected Error:** `Missing required field: name`

---

### Test 9: Too Many Guests
```
Guests: 25
```
**Expected Error:** `Number of guests must be between 1 and 20.`

---

## 🔍 Verification Steps

### 1. Check Database
```sql
SELECT * FROM reservations WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```
Should show your test reservation with:
- Status: 'Pending'
- All customer details sanitized
- Correct date/time

### 2. Check Log File
```
File: /Apps/haveli/reservation_logs.json
```
Should contain JSON array with your reservation including:
- reservation_id
- timestamp
- day_of_week
- All validation data
- ip_address
- user_agent

### 3. Check Email Queue
```
File: /Apps/haveli/email_queue_request_*.json
```
Should exist for each successful reservation with:
- reservation_id
- customer_email
- All booking details
- email_type: "confirmation"

---

## 🚨 Common Issues During Testing

### Issue: "Error validating opening hours"
- Usually means DateTime parsing failed
- Check: Is the date format correct? (YYYY-MM-DD)
- Check: Is the time format correct? (HH:MM in 24-hour)

### Issue: "Timezone mismatch"
- Server might not have Europe/London timezone available
- Check server: `php -r "echo shell_exec('date +%Z');"` 
- Should output: GMT or BST (depending on DST)

### Issue: Email queue file not created
- Check: Does the reservation show in database?
- Check: Server permissions in /Apps/haveli/
- Check: Disk space available?

---

## 📊 Expected Behavior Timeline

```
User submits form
    ↓
[Client-side] Simple form validation only (HTML5 required attributes)
    ↓
POST to submit_reservations.php
    ↓
[Server-side] Comprehensive validation:
    - Required fields? ✓
    - Email valid? ✓
    - Phone valid? ✓
    - DateTime parseable? ✓
    - Not in past? ✓
    - 20+ hours advance? ✓
    - Within opening hours? ✓
    - Valid guest count? ✓
    ↓
[If ALL valid] Insert into database
    ↓
Log to JSON file
    ↓
Create email queue file
    ↓
Return success (HTTP 200)
    ↓
[Client-side] Show success modal + confetti
    ↓
[Separate System] Email processor picks up queue file and sends confirmation
```

---

## 🎯 Performance Notes

- **Validation:** <10ms per request
- **Database insert:** ~5-20ms depending on server
- **Log write:** ~2-5ms
- **Total:** Usually <50ms for successful reservations

- **Memory usage:** ~2-5MB
- **File sizes:** 
  - Each reservation ~500 bytes in database
  - Each log entry ~400 bytes in JSON
  - Each email queue file ~300 bytes

---

## 📋 Test Checklist

- [ ] Valid reservation creates database entry
- [ ] Valid reservation creates log entry
- [ ] Valid reservation creates email queue file
- [ ] Success response includes reservation_id
- [ ] Past date rejected
- [ ] Insufficient advance notice rejected
- [ ] Time outside opening hours rejected
- [ ] Invalid email rejected
- [ ] Invalid phone rejected
- [ ] Missing fields rejected
- [ ] Guest count validation working
- [ ] Error messages show AM/PM formatting
- [ ] Debug messages included in opening hours errors
- [ ] SQL injection attempts blocked
- [ ] XSS attempts sanitized
- [ ] Timezone calculations correct (UK time)

---

**Last Updated:** November 18, 2025
