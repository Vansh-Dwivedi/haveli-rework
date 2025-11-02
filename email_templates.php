<?php
/**
 * Email template functions for Haveli Restaurant
 * Contains templates for confirmation emails and request received notifications
 */

function getRequestReceivedTemplate($customer_name, $reservation_date, $reservation_time, $num_guests) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Haveli Restaurant - Reservation Request Received</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'>
        <div style='max-width: 500px; margin: 0 auto; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1);'>
            
            <!-- Compact Header -->
            <div style='background: linear-gradient(45deg, #4facfe, #00f2fe, #667eea, #764ba2); padding: 25px 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px; font-weight: 700; text-shadow: 0 2px 8px rgba(0,0,0,0.3);'>
                    🏛️ HAVELI RESTAURANT
                </h1>
                <p style='color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-size: 14px;'>
                    📥 Reservation Request Received
                </p>
            </div>
            
            <!-- Compact Content -->
            <div style='padding: 25px 20px;'>
                <!-- Welcome Message -->
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='background: linear-gradient(45deg, #4facfe, #00f2fe); color: white; padding: 12px 20px; border-radius: 25px; font-size: 16px; font-weight: 600; display: inline-block;'>
                        👋 Hello {$customer_name}!
                    </div>
                </div>
                
                <!-- Request Status -->
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='background: linear-gradient(45deg, #ff9f43, #feca57); color: white; padding: 15px 20px; border-radius: 15px; box-shadow: 0 8px 25px rgba(255, 159, 67, 0.3);'>
                        <h2 style='margin: 0; font-size: 18px; font-weight: 600;'>📥 Request Received!</h2>
                        <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>We'll review and confirm shortly</p>
                    </div>
                </div>
                
                <!-- Reservation Details - Compact Grid -->
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2px; border-radius: 12px; margin: 20px 0;'>
                    <div style='background: white; padding: 20px; border-radius: 10px;'>
                        <h3 style='color: #333; margin: 0 0 15px 0; font-size: 18px; text-align: center; background: linear-gradient(45deg, #4facfe, #00f2fe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>
                            📋 Your Request Details
                        </h3>
                        
                        <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;'>
                            <div style='padding: 10px; background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); border-radius: 8px; text-align: center;'>
                                <div style='font-size: 18px; margin-bottom: 3px;'>📅</div>
                                <div style='font-size: 14px; font-weight: 600; color: #333;'>{$reservation_date}</div>
                            </div>
                            
                            <div style='padding: 10px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-radius: 8px; text-align: center;'>
                                <div style='font-size: 18px; margin-bottom: 3px;'>⏰</div>
                                <div style='font-size: 14px; font-weight: 600; color: #333;'>{$reservation_time}</div>
                            </div>
                        </div>
                        
                        <div style='padding: 10px; background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%); border-radius: 8px; text-align: center;'>
                            <div style='font-size: 18px; margin-bottom: 3px;'>👥</div>
                            <div style='font-size: 14px; font-weight: 600; color: #333;'>{$num_guests} Guests</div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Update -->
                <div style='background: linear-gradient(45deg, #ff9f43 0%, #feca57 100%); color: white; padding: 15px; border-radius: 10px; text-align: center; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px;'>
                        ⏳ <strong>We'll confirm within 6 hours</strong><br>
                        Urgent? Call <strong>📞 01753297560</strong>
                    </p>
                </div>
                
                <!-- Compact Footer -->
                <div style='text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;'>
                    <p style='color: #666; font-size: 14px; margin: 0;'>
                        Thank you for your request! 🙏<br>
                        <span style='color: #999; font-size: 12px;'>You'll receive confirmation soon - The Haveli Team</span>
                    </p>
                </div>
            </div>
            
            <!-- Bottom bar -->
            <div style='height: 4px; background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #feca57);'></div>
        </div>
    </body>
    </html>
    ";
}

function getConfirmationEmailTemplate($customer_name, $reservation_date, $reservation_time, $num_guests) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Booking Confirmed - Haveli Restaurant</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'>
        <div style='max-width: 500px; margin: 0 auto; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1);'>
            
            <!-- Celebration Header -->
            <div style='background: linear-gradient(45deg, #00b894, #00cec9, #74b9ff, #a29bfe); padding: 30px 20px; text-align: center; position: relative; overflow: hidden;'>
                <div style='position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url(\"data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.2'%3E%3Ccircle cx='20' cy='20' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\"); opacity: 0.5;'></div>
                <div style='position: relative; z-index: 1;'>
                    <h1 style='color: white; margin: 0; font-size: 28px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.3);'>
                        🎉 CONGRATULATIONS!
                    </h1>
                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px; font-weight: 500;'>
                        Your booking is confirmed!
                    </p>
                </div>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px 25px;'>
                <!-- Welcome Message -->
                <div style='text-align: center; margin-bottom: 25px;'>
                    <div style='background: linear-gradient(45deg, #00b894, #00cec9); color: white; padding: 15px 25px; border-radius: 30px; font-size: 18px; font-weight: 600; display: inline-block; box-shadow: 0 8px 25px rgba(0, 184, 148, 0.3);'>
                        🏛️ Welcome to Haveli, {$customer_name}!
                    </div>
                </div>
                
                <!-- Confirmed Status -->
                <div style='text-align: center; margin-bottom: 25px;'>
                    <div style='background: linear-gradient(45deg, #00b894, #00cec9); padding: 2px; border-radius: 15px;'>
                        <div style='background: white; padding: 20px; border-radius: 13px;'>
                            <h2 style='color: #333; margin: 0 0 10px 0; font-size: 20px; background: linear-gradient(45deg, #00b894, #00cec9); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>
                                ✅ Booking Confirmed!
                            </h2>
                            <p style='color: #666; font-size: 14px; margin: 0; line-height: 1.5;'>
                                Your table is reserved and ready! We can't wait to welcome you for an unforgettable dining experience.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Reservation Details -->
                <div style='background: linear-gradient(135deg, #74b9ff 0%, #a29bfe 100%); padding: 2px; border-radius: 12px; margin: 20px 0;'>
                    <div style='background: white; padding: 20px; border-radius: 10px;'>
                        <h3 style='color: #333; margin: 0 0 15px 0; font-size: 18px; text-align: center; background: linear-gradient(45deg, #74b9ff, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>
                            📋 Your Confirmed Booking
                        </h3>
                        
                        <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;'>
                            <div style='padding: 12px; background: linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%); border-radius: 8px; text-align: center;'>
                                <div style='font-size: 20px; margin-bottom: 5px;'>📅</div>
                                <div style='font-size: 14px; font-weight: 600; color: #333;'>{$reservation_date}</div>
                            </div>
                            
                            <div style='padding: 12px; background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); border-radius: 8px; text-align: center;'>
                                <div style='font-size: 20px; margin-bottom: 5px; color: white;'>⏰</div>
                                <div style='font-size: 14px; font-weight: 600; color: white;'>{$reservation_time}</div>
                            </div>
                        </div>
                        
                        <div style='padding: 12px; background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%); border-radius: 8px; text-align: center;'>
                            <div style='font-size: 20px; margin-bottom: 5px; color: white;'>👥</div>
                            <div style='font-size: 14px; font-weight: 600; color: white;'>{$num_guests} Guests</div>
                        </div>
                    </div>
                </div>
                
                <!-- Important Info -->
                <div style='background: linear-gradient(45deg, #e17055 0%, #fd79a8 100%); color: white; padding: 18px; border-radius: 10px; text-align: center; margin: 20px 0;'>
                    <h3 style='margin: 0 0 8px 0; font-size: 16px;'>📍 Important Details</h3>
                    <p style='margin: 0; font-size: 13px; line-height: 1.4;'>
                        Please arrive 10 minutes early<br>
                        📞 Need to modify? Call us: <strong>01753297560</strong><br>
                        🍽️ Get ready for an amazing culinary journey!
                    </p>
                </div>
                
                <!-- Footer -->
                <div style='text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #666; font-size: 14px; margin: 0 0 5px 0;'>
                        🙏 <strong>Thank you for choosing Haveli Restaurant!</strong>
                    </p>
                    <p style='color: #999; font-size: 12px; margin: 0;'>
                        See you soon! - The Haveli Team ✨
                    </p>
                </div>
            </div>
            
            <!-- Bottom celebration bar -->
            <div style='height: 6px; background: linear-gradient(45deg, #00b894, #00cec9, #74b9ff, #a29bfe);'></div>
        </div>
    </body>
    </html>";
}

function getRejectionEmailTemplate($customer_name, $reservation_date, $reservation_time, $num_guests, $reason = '') {
    $reason_text = $reason ? "<br><strong>Reason:</strong> {$reason}" : '';
    
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Booking Update - Haveli Restaurant</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'>
        <div style='max-width: 500px; margin: 0 auto; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1);'>
            
            <!-- Header -->
            <div style='background: linear-gradient(45deg, #fd79a8, #fdcb6e); padding: 25px 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px; font-weight: 700; text-shadow: 0 2px 8px rgba(0,0,0,0.3);'>
                    🏛️ HAVELI RESTAURANT
                </h1>
                <p style='color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-size: 14px;'>
                    Booking Update
                </p>
            </div>
            
            <!-- Content -->
            <div style='padding: 25px 20px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #e74c3c; margin: 0; font-size: 18px;'>
                        😔 We're Sorry, {$customer_name}
                    </h2>
                    <p style='color: #666; margin: 10px 0 0 0; font-size: 14px;'>
                        Unfortunately, we cannot confirm your booking for {$reservation_date} at {$reservation_time}{$reason_text}
                    </p>
                </div>
                
                <div style='background: #e74c3c; color: white; padding: 15px; border-radius: 10px; text-align: center; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px;'>
                        📞 Please call us at <strong>01753297560</strong><br>
                        We'd love to find you an alternative time!
                    </p>
                </div>
                
                <div style='text-align: center; margin-top: 20px;'>
                    <p style='color: #666; font-size: 14px; margin: 0;'>
                        Thank you for choosing Haveli 🙏<br>
                        <span style='color: #999; font-size: 12px;'>The Haveli Restaurant Team</span>
                    </p>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
?>