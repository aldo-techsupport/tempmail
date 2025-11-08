<?php
/**
 * Test Various Email Formats
 * Menguji berbagai format email dari berbagai sumber web
 */

require_once 'config.php';
require_once 'functions.php';

$test_email = 'testall' . EMAIL_DOMAIN;

echo "Mengirim berbagai format email ke: $test_email\n\n";

// 1. Email dengan HTML fragments (seperti Atlantic.Net)
$email_fragments = 'Please Verify Your Email Address<br />
<br />
<br />
Thank you for signing up with Atlantic.Net!<br />
To complete your signup, please verify your email address by clicking on the button below:<br />
<br />
<a href="https://example.com/verify">Verify Your Email</a><br />
<br />
If you have any questions or need help, our Support Department is available 24/7 by phone and email.<br />
<br />
Sincerely,<br />
Atlantic.Net Cloud<br />
cloudsupport@atlantic.net<br />
US: 888-618-DATA (3282)<br />
Intl: +1-321-206-3734';

if (saveEmail($test_email, 'no-reply@atlantic.net', 'Test: HTML Fragments', $email_fragments)) {
    echo "✓ Test 1: Email dengan HTML fragments (Atlantic.Net style)\n";
}

// 2. Email HTML lengkap dengan DOCTYPE
$email_full_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px; }
        .button { display: inline-block; padding: 12px 30px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Our Service</h1>
        </div>
        <p>Dear User,</p>
        <p>Thank you for registering. Please click the button below to verify your account:</p>
        <a href="https://example.com/verify" class="button">Verify Account</a>
        <p>Best regards,<br>The Team</p>
    </div>
</body>
</html>';

if (saveEmail($test_email, 'noreply@service.com', 'Test: Full HTML Email', $email_full_html)) {
    echo "✓ Test 2: Email HTML lengkap dengan DOCTYPE\n";
}

// 3. Email dengan inline styles (Gmail/Outlook style)
$email_inline = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;">
        <h1 style="margin: 0;">Password Reset Request</h1>
    </div>
    <div style="padding: 30px; background: white;">
        <p style="font-size: 16px; line-height: 1.6;">Hello,</p>
        <p style="font-size: 16px; line-height: 1.6;">We received a request to reset your password. Click the button below to proceed:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="https://example.com/reset" style="display: inline-block; padding: 15px 40px; background: #ff6b6b; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Reset Password</a>
        </div>
        <p style="font-size: 14px; color: #666;">If you didn\'t request this, please ignore this email.</p>
    </div>
    <div style="background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;">
        <p>© 2025 Your Company. All rights reserved.</p>
    </div>
</div>';

if (saveEmail($test_email, 'security@company.com', 'Test: Inline Styles Email', $email_inline)) {
    echo "✓ Test 3: Email dengan inline styles (Gmail/Outlook style)\n";
}

// 4. Email table-based (traditional email client)
$email_table = '<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa;">
    <tr>
        <td align="center" style="padding: 40px 0;">
            <table width="600" cellpadding="0" cellspacing="0" style="background-color: white;">
                <tr>
                    <td style="background: #007bff; color: white; padding: 30px; text-align: center;">
                        <h1 style="margin: 0;">Order Confirmation</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px;">
                        <p>Dear Customer,</p>
                        <p>Your order has been confirmed!</p>
                        <table width="100%" cellpadding="10" style="border: 1px solid #ddd; margin: 20px 0;">
                            <tr style="background: #f8f9fa;">
                                <td><strong>Order ID:</strong></td>
                                <td>#12345</td>
                            </tr>
                            <tr>
                                <td><strong>Total:</strong></td>
                                <td>$99.99</td>
                            </tr>
                        </table>
                        <p>Thank you for your purchase!</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>';

if (saveEmail($test_email, 'orders@shop.com', 'Test: Table-Based Email', $email_table)) {
    echo "✓ Test 4: Email table-based (traditional email client)\n";
}

// 5. Plain text dengan line breaks
$email_plain = "Hello User,

This is a plain text email with line breaks.

Features:
- Simple text format
- No HTML tags
- Easy to read
- Multiple paragraphs

Best regards,
The Team

---
Company Name
support@company.com
+1-234-567-8900";

if (saveEmail($test_email, 'info@company.com', 'Test: Plain Text Email', $email_plain)) {
    echo "✓ Test 5: Plain text dengan line breaks\n";
}

// 6. Email dengan mixed content (text + HTML fragments)
$email_mixed = 'Hello,

Your verification code is: <strong>123456</strong>

Please enter this code in the app to continue.

<div style="background: #f0f0f0; padding: 15px; margin: 20px 0; border-left: 4px solid #4CAF50;">
    <strong>Important:</strong> This code will expire in 10 minutes.
</div>

If you didn\'t request this code, please ignore this email.

Thanks,
Security Team';

if (saveEmail($test_email, 'security@app.com', 'Test: Mixed Content Email', $email_mixed)) {
    echo "✓ Test 6: Email dengan mixed content (text + HTML fragments)\n";
}

// 7. Email dengan banyak <br /> tags (seperti yang bermasalah)
$email_many_br = 'Welcome to Our Platform<br /><br />Thank you for signing up!<br /><br />Your account has been created successfully.<br /><br />Next steps:<br />1. Verify your email<br />2. Complete your profile<br />3. Start using our service<br /><br />Need help? Contact us at support@platform.com<br /><br />Best regards,<br />The Platform Team';

if (saveEmail($test_email, 'welcome@platform.com', 'Test: Many BR Tags', $email_many_br)) {
    echo "✓ Test 7: Email dengan banyak <br /> tags\n";
}

echo "\n========================================\n";
echo "Semua test email berhasil dikirim!\n";
echo "========================================\n";
echo "Buka browser dan akses: $test_email\n";
echo "\nAnda akan melihat 7 email dengan format berbeda:\n";
echo "1. HTML fragments (Atlantic.Net style)\n";
echo "2. Full HTML dengan DOCTYPE\n";
echo "3. Inline styles (Gmail/Outlook style)\n";
echo "4. Table-based (traditional)\n";
echo "5. Plain text\n";
echo "6. Mixed content\n";
echo "7. Many BR tags\n";
echo "\nSemua email harus ditampilkan dengan benar!\n";
?>
