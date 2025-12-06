<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balance Sheet</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
        th { text-align: left; }
        .right { text-align: right; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Balance Sheet</h2>
        <div>Period: {{ $period['start_date'] }} — {{ $period['end_date'] }}</div>
        @if(!empty($pdf_error))
            <div style="margin-top:8px; color:#b00; font-size:12px;">
                <strong>Notice:</strong> PDF generation failed: {{ $pdf_error }}
                <div style="font-size:11px; color:#666;">You can still view this report in the browser and print/save as PDF from the browser.</div>
            </div>
        @endif
    </div>
    {{-- Bank-statement style summary: Opening | Debit | Credit | Closing --}}
    @php
        $currency = $currency ?? env('APP_CURRENCY', 'USD');
        $fmt = function($v) use ($currency) {
            // show currency code then formatted number
            return $currency . ' ' . number_format((float) $v, 2);
        };

        // Prefer controller-provided opening_balance / per_account when available
        $opening_balance = $opening_balance ?? 0.0;
        $perAccountList = $per_account ?? null;
        $debits = 0.0; $credits = 0.0;

        if (is_array($perAccountList) && count($perAccountList) > 0) {
            foreach ($perAccountList as $item) {
                $debits += (float) ($item['debit'] ?? 0);
                $credits += (float) ($item['credit'] ?? 0);
            }
        } else {
            // Fallback: compute from by_category if provided (older controller behavior)
            $perCategory = [];
            foreach (($by_category ?? []) as $row) {
                $cat = $row['category'] ?? 'Uncategorized';
                $type = strtolower($row['type'] ?? 'expense');
                $amt = (float) ($row['total'] ?? 0);
                if (!isset($perCategory[$cat])) {
                    $perCategory[$cat] = ['debit' => 0.0, 'credit' => 0.0];
                }
                if ($type === 'income' || $type === 'credit') {
                    $perCategory[$cat]['credit'] += $amt;
                    $credits += $amt;
                } else {
                    $perCategory[$cat]['debit'] += $amt;
                    $debits += $amt;
                }
            }
            // convert to list for rendering
            $perAccountList = [];
            foreach ($perCategory as $cat => $vals) {
                $perAccountList[] = ['category' => $cat, 'opening' => 0.0, 'debit' => $vals['debit'], 'credit' => $vals['credit'], 'closing' => $vals['credit'] - $vals['debit']];
            }
        }

        $closing_balance = $opening_balance + $credits - $debits;
    @endphp

    <div class="section">
        <table>
            <tr>
                <th>Opening Balance</th>
                <td class="right">{{ $fmt($opening_balance) }}</td>
            </tr>
            <tr>
                <th>Total Debits</th>
                <td class="right text-danger">-{{ $fmt($debits) }}</td>
            </tr>
            <tr>
                <th>Total Credits</th>
                <td class="right text-success">{{ $fmt($credits) }}</td>
            </tr>
            <tr>
                <th class="total">Closing Balance</th>
                <td class="right total">{{ $fmt($closing_balance) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h4>Statement (by category)</h4>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="right">Debit</th>
                    <th class="right">Credit</th>
                    <th class="right">Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($perAccountList as $item)
                    @php
                        $debit = $item['debit'] ?? 0.0;
                        $credit = $item['credit'] ?? 0.0;
                        $net = $credit - $debit;
                    @endphp
                    <tr>
                        <td>{{ $item['category'] }}</td>
                        <td class="right">{{ $fmt($debit) }}</td>
                        <td class="right">{{ $fmt($credit) }}</td>
                        <td class="right">{{ $fmt($net) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="total">Totals</th>
                    <th class="right total">{{ $fmt($debits) }}</th>
                    <th class="right total">{{ $fmt($credits) }}</th>
                    <th class="right total">{{ $fmt($credits - $debits) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section" style="margin-top:20px;">
        <h4>Transactions</h4>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Details</th>
                    <th class="right">Expense</th>
                    <th class="right">Income</th>
                    <th class="right">Balance</th>
                </tr>
            </thead>
            <tbody>
                {{-- Opening balance row --}}
                <tr>
                    <td></td>
                    <td>Opening balance</td>
                    <td class="right">&nbsp;</td>
                    <td class="right">&nbsp;</td>
                    <td class="right">{{ $fmt($opening_balance) }}</td>
                </tr>
                {{-- Transaction rows --}}

                @foreach(($transactions ?? []) as $tx)
                    <tr>
                        <td>{{ Carbon\Carbon::parse($tx['date'])->format('Y-m-d')
                        }}</td>
                        <td>{{ $tx['description'] }}</td>
                        <td class="right text-danger">@if($tx['withdrawal'] > 0)-{{ $fmt($tx['withdrawal']) }}@else&nbsp;@endif</td>
                        <td class="right text-success">@if($tx['deposit'] > 0){{ $fmt($tx['deposit']) }}@else&nbsp;@endif</td>
                        <td class="right">{{ $fmt($tx['balance']) }}</td>
                        
                    </tr>
                    
                @endforeach

            </tbody>
            <tfoot>
                <tr>
                    <th class="total">Totals</th>
                    <th class="right total">&nbsp;</th>
                    <th class="right total">{{ $fmt($debits) }}</th>
                    <th class="right total">{{ $fmt($credits) }}</th>
                    <th class="right total">{{ $fmt($credits - $debits) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="margin-top: 30px; font-size: 11px; color: #666;">
        Generated by FinTrack
    </div>
</body>
</html>
