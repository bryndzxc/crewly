<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>New Lead</title>
    </head>
    <body style="font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; line-height: 1.5; color: #0f172a;">
        <h2 style="margin: 0 0 12px 0;">New demo request received</h2>

        <table cellpadding="0" cellspacing="0" style="width: 100%; max-width: 720px; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Full name</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $lead->full_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Company</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $lead->company_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Email</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $lead->email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Phone</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $lead->phone ?: '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Company size</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $lead->company_size ?: '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Source page</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $lead->source_page ?: '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569; vertical-align: top;">Message</td>
                <td style="padding: 8px 0;">{!! nl2br(e($lead->message ?: '—')) !!}</td>
            </tr>
        </table>

        <p style="margin-top: 16px; color: #64748b; font-size: 12px;">
            Submitted {{ optional($lead->created_at)->format('Y-m-d H:i:s') }}
        </p>
    </body>
</html>
