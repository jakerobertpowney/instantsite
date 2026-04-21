<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Message</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #0f172a; -webkit-font-smoothing: antialiased;">

    <!-- Outer wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; padding: 40px 16px;">
        <tr>
            <td align="center">

                <!-- Email card -->
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 580px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.07); overflow: hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 40px 28px; border-bottom: 1px solid #f1f5f9;">
                            <!-- Logo: "3●2●1" styled text -->
                            <table cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td>
                                        <span style="font-size: 22px; font-weight: 700; letter-spacing: -0.5px; color: #0f172a;">3</span><!--
                                        --><span style="font-size: 10px; color: #1e66f5; vertical-align: middle; margin: 0 1px;">&#9679;</span><!--
                                        --><span style="font-size: 22px; font-weight: 700; letter-spacing: -0.5px; color: #0f172a;">2</span><!--
                                        --><span style="font-size: 10px; color: #1e66f5; vertical-align: middle; margin: 0 1px;">&#9679;</span><!--
                                        --><span style="font-size: 22px; font-weight: 700; letter-spacing: -0.5px; color: #0f172a;">1</span><!--
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px 40px;">

                            <!-- Title -->
                            <h1 style="margin: 0 0 6px; font-size: 20px; font-weight: 700; color: #0f172a; line-height: 1.3;">New message from {{ $businessName }}</h1>
                            <p style="margin: 0 0 28px; font-size: 14px; color: #64748b;">Someone reached out via your 321Sites contact form.</p>

                            <!-- From -->
                            <div style="margin-bottom: 20px;">
                                <p style="margin: 0 0 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8;">From</p>
                                <p style="margin: 0; font-size: 15px; color: #0f172a;">{{ $senderEmail }}</p>
                            </div>

                            <!-- Subject -->
                            <div style="margin-bottom: 20px;">
                                <p style="margin: 0 0 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8;">Subject</p>
                                <p style="margin: 0; font-size: 15px; color: #0f172a;">{{ $mailSubject }}</p>
                            </div>

                            @if ($preferredContactTime)
                            <!-- Preferred contact time -->
                            <div style="margin-bottom: 20px;">
                                <p style="margin: 0 0 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8;">Preferred date &amp; time</p>
                                <p style="margin: 0; font-size: 15px; color: #0f172a;">{{ $preferredContactTime }}</p>
                            </div>
                            @endif

                            <!-- Message -->
                            <div style="margin-bottom: 8px;">
                                <p style="margin: 0 0 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8;">Message</p>
                                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #1e66f5; border-radius: 8px; padding: 16px 20px;">
                                    <p style="margin: 0; font-size: 15px; color: #0f172a; line-height: 1.65; white-space: pre-wrap;">{{ $messageBody }}</p>
                                </div>
                            </div>

                            <!-- Reply CTA -->
                            <table cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 28px;">
                                <tr>
                                    <td style="border-radius: 999px; background-color: #1e66f5;">
                                        <a href="mailto:{{ $senderEmail }}" style="display: inline-block; padding: 12px 22px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 999px; line-height: 1;">
                                            Reply to {{ $senderEmail }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px 28px; border-top: 1px solid #f1f5f9;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.6;">
                                Sent via the contact form on your <a href="#" style="color: #1e66f5; text-decoration: none;">321Sites</a> website.
                                Reply directly to this email to respond to {{ $senderEmail }}.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Email card -->

            </td>
        </tr>
    </table>

</body>
</html>
