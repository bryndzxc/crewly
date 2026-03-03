<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Invoice summary</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; line-height: 1.5; color: #0f172a;">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 24px 12px;">
            <tr>
                <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 640px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                        <tr>
                            <td style="background-color: #0f172a; padding: 18px 24px;">
                                <div style="font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: #cbd5e1;">Crewly</div>
                                <div style="margin-top: 6px; font-size: 20px; font-weight: 700; line-height: 1.25; color: #ffffff;">Invoice summary</div>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 24px;">
                                <p style="margin: 0 0 16px 0;">Hello,</p>

                                <p style="margin: 0 0 16px 0;">Here’s your Crewly billing summary for <strong>{{ $company->name }}</strong>.</p>

                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Plan</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ strtoupper((string) ($company->plan_name ?? '')) ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Amount due</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">
                                            @if(isset($amountDue) && is_numeric($amountDue) && (int) $amountDue > 0)
                                                ₱{{ number_format((int) $amountDue, 0) }} / month
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Billing period</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">
                                            @php
                                                $startLabel = is_array($billingPeriod ?? null) ? (string) ($billingPeriod['start_label'] ?? '') : '';
                                                $endLabel = is_array($billingPeriod ?? null) ? (string) ($billingPeriod['end_label'] ?? '') : '';
                                            @endphp
                                            @if($startLabel !== '' && $endLabel !== '')
                                                {{ $startLabel }} – {{ $endLabel }}
                                            @elseif($endLabel !== '')
                                                Until {{ $endLabel }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Max employees</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ (int) ($company->max_employees ?? 0) ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Subscription status</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ str_replace('_', ' ', (string) ($company->subscription_status ?? '')) ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Next billing date</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $company->next_billing_at?->format('Y-m-d') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; width: 180px; background-color: #f8fafc; color: #475569;">Last payment</td>
                                        <td style="padding: 10px 14px; font-weight: 600;">{{ $company->last_payment_at?->format('Y-m-d') ?? '—' }}</td>
                                    </tr>
                                </table>

                                @php
                                    $notes = trim((string) ($company->billing_notes ?? ''));

                                    if ($notes === '') {
                                        $accountName = trim((string) config('crewly.billing.account_name', ''));
                                        $gcash = trim((string) config('crewly.billing.gcash_number', ''));
                                        $maya = trim((string) config('crewly.billing.maya_number', ''));
                                        $bankNote = trim((string) config('crewly.billing.bank_note', ''));

                                        $lines = ['Payment options:'];

                                        if ($gcash !== '') {
                                            $lines[] = '- GCash: '.$gcash.($accountName !== '' ? ' (Name: '.$accountName.')' : '');
                                        }
                                        if ($maya !== '') {
                                            $lines[] = '- Maya: '.$maya.($accountName !== '' ? ' (Name: '.$accountName.')' : '');
                                        }
                                        if ($bankNote !== '') {
                                            $lines[] = '- '.$bankNote;
                                        }

                                        $notes = trim(implode("\n", $lines));
                                    }
                                @endphp

                                @if($notes !== '')
                                    <p style="margin: 16px 0 0 0; color: #475569;"><strong>Payment instructions / notes</strong></p>

                                    @php
                                        $planLabel = strtoupper((string) ($company->plan_name ?? '')) ?: '';
                                        $startLabel = is_array($billingPeriod ?? null) ? (string) ($billingPeriod['start_label'] ?? '') : '';
                                        $endLabel = is_array($billingPeriod ?? null) ? (string) ($billingPeriod['end_label'] ?? '') : '';
                                        $amountLine = (isset($amountDue) && is_numeric($amountDue) && (int) $amountDue > 0)
                                            ? '₱'.number_format((int) $amountDue, 0).' / month'
                                            : '';
                                        $periodLine = ($startLabel !== '' && $endLabel !== '')
                                            ? $startLabel.' – '.$endLabel
                                            : ($endLabel !== '' ? 'Until '.$endLabel : '');
                                    @endphp

                                    @php
                                        $paymentHeaderLines = [];
                                        if ($planLabel !== '') {
                                            $paymentHeaderLines[] = 'Plan: Founder Access — '.$planLabel;
                                        }
                                        if ($amountLine !== '') {
                                            $paymentHeaderLines[] = 'Amount due: '.$amountLine;
                                        }
                                        if ($periodLine !== '') {
                                            $paymentHeaderLines[] = 'Billing period: '.$periodLine;
                                        }
                                        $paymentHeaderText = trim(implode("\n", $paymentHeaderLines));
                                    @endphp

                                    @if($paymentHeaderText !== '')
                                        <p style="margin: 8px 0 0 0; color: #475569; white-space: pre-wrap;">{{ $paymentHeaderText }}</p>
                                    @endif

                                    <p style="margin: 8px 0 0 0; color: #475569; white-space: pre-wrap;">{{ $notes }}</p>
                                @endif

                                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;" />

                                <p style="margin: 0; color: #64748b; font-size: 12px;"></p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
