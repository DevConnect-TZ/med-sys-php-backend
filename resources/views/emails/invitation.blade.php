<!-- resources/views/emails/invitation.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            color: #333333;
        }
        .greeting {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 20px;
            color: #1f2937;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #4b5563;
        }
        .role-badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            margin: 15px 0;
            text-transform: capitalize;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .expiration {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
            color: #92400e;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .footer-link {
            color: #2563eb;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 25px 0;
        }
        .security-note {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
            color: #1e40af;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Hospital Management System</h1>
            <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">Welcome to the Team</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Welcome</div>

            <div class="message">
                <p>You've been invited to join the <strong>Hospital Management System</strong> as a <strong>{{ ucfirst(str_replace('_', ' ', $role)) }}</strong>.</p>

                <p>Click the button below to complete your registration and set up your account:</p>
            </div>

            <div style="text-align: center;">
                <span class="role-badge">{{ ucfirst(str_replace('_', ' ', $role)) }}</span>
            </div>

            <div class="button-container">
                <a href="{{ $invitationLink }}" class="cta-button">Complete Your Registration</a>
            </div>

            <div class="message" style="margin-top: 25px;">
                <p>Or copy and paste this link in your browser:</p>
                <p style="word-break: break-all; background-color: #f3f4f6; padding: 10px; border-radius: 4px; font-size: 13px; color: #374151;">{{ $invitationLink }}</p>
            </div>

            <div class="expiration">
                <strong>Important:</strong> This invitation link will expire on <strong>{{ $expiresAt->format('F j, Y \a\t g:i A') }}</strong>. Please complete your registration before it expires.
            </div>

            <div class="security-note">
                <strong>Security Note:</strong> Never share this link with anyone else. This is a personal invitation for {{ $email }}. If you did not request this invitation, please ignore this email.
            </div>

            <div class="divider"></div>

            <div class="message" style="font-size: 14px; color: #6b7280;">
                <p><strong>What to expect:</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Fill in your name and create a secure password</li>
                    <li>Verify your email address</li>
                    <li>Start using the Hospital Management System</li>
                </ul>
            </div>

            <div class="message" style="font-size: 14px; color: #6b7280;">
                <p><strong>Questions?</strong></p>
                <p>If you have any questions about your invitation or need help setting up your account, please contact your administrator or reply to this email.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 10px 0;">
                © 2026 Hospital Management System. All rights reserved.
            </p>
            <p style="margin: 0;">
                This is an automated message. Please do not reply to this email with sensitive information.
            </p>
        </div>
    </div>
</body>
</html>
