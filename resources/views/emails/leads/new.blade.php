<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>New Lead</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; line-height: 1.5; color: #0f172a;">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 24px 12px;">
            <tr>
                <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 640px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                        <tr>
                            <td style="background-color: #2563eb; padding: 18px 24px;">
                                <div style="font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: #dbeafe;">Crewly</div>
                                <div style="margin-top: 6px; font-size: 20px; font-weight: 700; line-height: 1.25; color: #ffffff;">New demo request received</div>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 24px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Full name</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $lead->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Company</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $lead->company_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Email</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $lead->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Phone</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $lead->phone ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Company size</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $lead->company_size ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Source page</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $lead->source_page ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569; vertical-align: top;">Message</td>
                                        <td style="padding: 10px 14px;">{!! nl2br(e($lead->message ?: '—')) !!}</td>
                                    </tr>
                                </table>

                                <p style="margin: 14px 0 0 0; color: #64748b; font-size: 12px;">
                                    Submitted {{ optional($lead->created_at)->format('Y-m-d H:i:s') }}
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
