<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Contact Message</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .label { font-size: 12px; text-transform: uppercase; color: #888; font-weight: bold; margin-bottom: 4px; }
        .value { font-size: 15px; margin-bottom: 20px; }
        .message-body { background: #f9f9f9; border-left: 3px solid #333; padding: 12px 16px; white-space: pre-wrap; }
        .footer { margin-top: 32px; font-size: 12px; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <h2>New message from {{ $businessName }}</h2>

        <div class="label">From</div>
        <div class="value">{{ $senderEmail }}</div>

        <div class="label">Subject</div>
        <div class="value">{{ $subject }}</div>

        <div class="label">Message</div>
        <div class="message-body">{{ $messageBody }}</div>

        <div class="footer">
            Sent via the contact form on your InstantSite website.
            Reply directly to this email to respond to {{ $senderEmail }}.
        </div>
    </div>
</body>
</html>
