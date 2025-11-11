<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>We miss your latest update</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);">
        <tr>
            <td style="background: linear-gradient(135deg, #1e3a8a, #22d3ee); padding: 28px 32px;">
                <h1 style="margin: 0; font-size: 22px; color: #ffffff;">We miss your latest update</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 28px 32px;">
                <p style="margin-top: 0; font-size: 15px; line-height: 1.6;">
                    Hi {{ $user->name ?? 'there' }},
                </p>
                @if($latestActivityAt)
                    <p style="font-size: 15px; line-height: 1.6;">
                        It looks like your last FinTrack transaction was on <strong>{{ $latestActivityAt->timezone(config('app.timezone'))->format('F j, Y') }}</strong>. Recording your latest income and expenses keeps your dashboard trends accurate and the insights flowing.
                    </p>
                @else
                    <p style="font-size: 15px; line-height: 1.6;">
                        We haven’t seen your first FinTrack transaction yet. Taking a moment to add your latest income or expense kickstarts personalized insights and makes future budgeting easier.
                    </p>
                @endif
                <p style="font-size: 15px; line-height: 1.6;">
                    Jump back in and log anything you’ve spent or earned since {{ $inactiveSince->timezone(config('app.timezone'))->format('F j, Y') }} so your reports stay current.
                </p>
                <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 24px 0;">
                    <tr>
                        <td>
                            <a href="{{ rtrim(config('app.url'), '/') . '/login' }}" style="display: inline-block; padding: 12px 22px; background-color: #1d4ed8; color: #ffffff; text-decoration: none; border-radius: 999px; font-weight: bold;">
                                Log a transaction
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="font-size: 14px; line-height: 1.6; color: #475569;">
                    Need help or have feedback? Reply to this email and the FinTrack team will be in touch.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
