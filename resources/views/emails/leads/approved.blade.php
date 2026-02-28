<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Demo account ready</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; line-height: 1.5; color: #0f172a;">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 24px 12px;">
            <tr>
                <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 640px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                        <tr>
                            <td style="background-color: #2563eb; padding: 18px 24px;">
                                <div style="font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: #dbeafe;">Crewly</div>
                                <div style="margin-top: 6px; font-size: 20px; font-weight: 700; line-height: 1.25; color: #ffffff;">Your demo account is ready</div>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 24px;">
                                <p style="margin: 0 0 16px 0;">
                                    Hi {{ $user->name }},
                                </p>

                                <p style="margin: 0 0 16px 0;">
                                    Thanks for requesting a demo. We’ve created a demo company for you in Crewly and preloaded it with sample employees, incidents, notes, and memos so you can explore outputs immediately.
                                </p>

                                <h3 style="margin: 0 0 10px 0; font-size: 16px;">Login details</h3>
                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Company</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $company->name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Email</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Temporary password</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $passwordPlain }}</td>
                                    </tr>
                                </table>

                                <div style="margin-top: 16px;">
                                    <a href="{{ $loginUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 16px; border-radius: 8px; font-weight: 600;">Open Crewly</a>
                                </div>

                                <p style="margin: 10px 0 0 0; font-size: 12px; color: #64748b;">
                                    If the button doesn’t work, use this link: <a href="{{ $loginUrl }}" style="color: #0f172a; text-decoration: underline;">{{ $loginUrl }}</a>
                                </p>

                                <p style="margin: 16px 0 0 0; color: #475569;">
                                    For security, you may be asked to change your password on first login.
                                </p>

                                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;" />

                                <p style="margin: 0; color: #64748b; font-size: 12px;">
                                    If you didn’t request this demo, you can ignore this email.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
