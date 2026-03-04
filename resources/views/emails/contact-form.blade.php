<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission - Oweru Hub</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #C89128;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #C89128;
        }
        .content {
            margin-bottom: 30px;
        }
        .field {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        .field-value {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #C89128;
        }
        .message {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #C89128;
            white-space: pre-wrap;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Oweru Hub</div>
            <h1>New Contact Form Submission</h1>
        </div>

        <div class="content">
            <div class="field">
                <div class="field-label">From:</div>
                <div class="field-value">
                    {{ $contact['first_name'] }} {{ $contact['last_name'] }}
                    &lt;{{ $contact['email'] }}&gt;
                </div>
            </div>

            <div class="field">
                <div class="field-label">Subject:</div>
                <div class="field-value">{{ $contact['subject'] }}</div>
            </div>

            <div class="field">
                <div class="field-label">Message:</div>
                <div class="message">{{ $contact['message'] }}</div>
            </div>

            <div class="field">
                <div class="field-label">Submitted:</div>
                <div class="field-value">{{ $contact['created_at'] }}</div>
            </div>
        </div>

        <div class="footer">
            <p>This message was sent from the Oweru Hub contact form.</p>
            <p>&copy; {{ date('Y') }} Oweru Hub. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
