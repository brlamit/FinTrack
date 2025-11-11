<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Keep {{ $group->name }} engaged</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);">
        <tr>
            <td style="background: linear-gradient(135deg, #1e3a8a, #22d3ee); padding: 28px 32px;">
                <h1 style="margin: 0; font-size: 22px; color: #ffffff;">Time to spark new activity in {{ $group->name }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 28px 32px;">
                <p style="margin-top: 0; font-size: 15px; line-height: 1.6;">
                    Hi {{ optional($group->owner)->name ?? 'there' }},
                </p>
                @if($leaderTotal > 0)
                    <p style="font-size: 15px; line-height: 1.6;">
                        Your group <strong>{{ $group->name }}</strong> has shared <strong>${{ number_format($groupTotal, 2) }}</strong> in transactions so far, which is about <strong>{{ $percentOfLeader }}%</strong> of this week's leader, {{ $leaderGroup->name }} (currently at ${{ number_format($leaderTotal, 2) }}).
                    </p>
                    <p style="font-size: 15px; line-height: 1.6;">
                        A quick prompt can go a long way—try sharing a new transaction or nudging members to submit their latest receipts. Keeping activity above 50% of the leading group helps everyone stay accountable and engaged.
                    </p>
                @else
                    <p style="font-size: 15px; line-height: 1.6;">
                        No group has logged shared activity yet this cycle, so {{ $group->name }} is currently tied for the lead. Kick things off with a quick transaction or ask members to add their latest receipts so everyone gets into the rhythm early.
                    </p>
                @endif
                <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 24px 0;">
                    <tr>
                        <td>
                            <a href="{{ route('admin.groups.show', $group) }}" style="display: inline-block; padding: 12px 22px; background-color: #1d4ed8; color: #ffffff; text-decoration: none; border-radius: 999px; font-weight: bold;">
                                Review group activity
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="font-size: 14px; line-height: 1.6; color: #475569;">
                    Thanks for helping your community stay on top of their shared finances.<br>
                    — The FinTrack Team
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
