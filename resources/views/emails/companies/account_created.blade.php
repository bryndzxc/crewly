<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Your Crewly account</title>
    </head>
    <body style="font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; line-height: 1.5; color: #0f172a;">
        <h2 style="margin: 0 0 12px 0;">Welcome to Crewly</h2>

        <p style="margin: 0 0 16px 0;">
            Hi {{ $user->name }},
        </p>

        <p style="margin: 0 0 16px 0;">
            An account has been created for you in <strong>Crewly</strong> under <strong>{{ $company->name }}</strong>.
        </p>

        <h3 style="margin: 0 0 8px 0; font-size: 16px;">Login details</h3>
        <table cellpadding="0" cellspacing="0" style="width: 100%; max-width: 720px; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Company</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $company->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Email</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $user->email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Temporary password</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $passwordPlain }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; width: 180px; color: #475569;">Login URL</td>
                <td style="padding: 8px 0; font-weight: 600;">
                    <a href="{{ $loginUrl }}" style="color: #0f172a; text-decoration: underline;">{{ $loginUrl }}</a>
                </td>
            </tr>
        </table>

        <p style="margin: 16px 0 0 0; color: #475569;">
            For security, you will be asked to change your password on first login.
        </p>

        <p style="margin-top: 16px; color: #64748b; font-size: 12px;">
            If you did not expect this email, you can ignore it.
        </p>
    </body>
</html>
