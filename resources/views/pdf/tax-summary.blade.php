<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

  .header { background: #1e40af; color: #fff; padding: 24px 32px; }
  .header h1 { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
  .header p { font-size: 11px; margin-top: 4px; opacity: 0.85; }

  .meta { display: flex; justify-content: space-between; padding: 16px 32px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
  .meta-item { }
  .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
  .meta-value { font-size: 12px; font-weight: 600; color: #0f172a; margin-top: 2px; }

  .section { padding: 24px 32px; }
  .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #374151; border-bottom: 2px solid #1e40af; padding-bottom: 6px; margin-bottom: 16px; }

  table { width: 100%; border-collapse: collapse; }
  th { background: #f8fafc; text-align: left; padding: 8px 10px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: #64748b; border-bottom: 1px solid #e2e8f0; }
  td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  tr:last-child td { border-bottom: none; }
  .tr-over { background: #fef2f2; }

  .deduction-label { font-weight: 600; font-size: 11px; color: #0f172a; }
  .deduction-sub { font-size: 9px; color: #64748b; margin-top: 2px; }
  .amount { text-align: right; font-size: 11px; }
  .amount-claimable { font-weight: 700; color: #1e40af; }
  .amount-over { color: #dc2626; font-size: 9px; }
  .badge-over { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 1px 5px; font-size: 8px; font-weight: 600; }
  .badge-ok { background: #dcfce7; color: #166534; border-radius: 3px; padding: 1px 5px; font-size: 8px; font-weight: 600; }
  .badge-unlimited { background: #dbeafe; color: #1e40af; border-radius: 3px; padding: 1px 5px; font-size: 8px; font-weight: 600; }

  .total-row td { background: #eff6ff; font-weight: 700; border-top: 2px solid #1e40af; }
  .total-label { font-size: 13px; color: #0f172a; }
  .total-amount { font-size: 14px; color: #1e40af; text-align: right; }

  .disclaimer { margin: 0 32px 24px; background: #fafafa; border: 1px solid #e5e7eb; border-radius: 4px; padding: 12px 14px; }
  .disclaimer p { font-size: 9px; color: #6b7280; line-height: 1.6; }
  .disclaimer strong { color: #374151; }

  .footer { text-align: center; padding: 16px 32px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 9px; }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
  <h1>Tax Deduction Summary Report</h1>
  <p>Year of Assessment {{ $year }} &nbsp;|&nbsp; LHDN Malaysia</p>
</div>

<!-- Meta -->
<div class="meta">
  <div class="meta-item">
    <div class="meta-label">Taxpayer</div>
    <div class="meta-value">{{ $user->name }}</div>
  </div>
  <div class="meta-item">
    <div class="meta-label">Email</div>
    <div class="meta-value">{{ $user->email }}</div>
  </div>
  <div class="meta-item">
    <div class="meta-label">Year of Assessment</div>
    <div class="meta-value">{{ $year }}</div>
  </div>
  <div class="meta-item">
    <div class="meta-label">Generated</div>
    <div class="meta-value">{{ now()->format('d M Y, H:i') }}</div>
  </div>
  <div class="meta-item">
    <div class="meta-label">Total Claimable</div>
    <div class="meta-value" style="color:#1e40af;">MYR {{ number_format($totalClaimable, 2) }}</div>
  </div>
</div>

<!-- Breakdown -->
<div class="section">
  <div class="section-title">Deduction Breakdown by LHDN Category</div>

  <table>
    <thead>
      <tr>
        <th style="width:38%">LHDN Deduction Type</th>
        <th style="width:12%; text-align:right">Entries</th>
        <th style="width:17%; text-align:right">Total Spent (MYR)</th>
        <th style="width:17%; text-align:right">Annual Limit (MYR)</th>
        <th style="width:16%; text-align:right">Claimable (MYR)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($breakdown as $row)
      <tr class="{{ $row['over_limit'] ? 'tr-over' : '' }}">
        <td>
          <div class="deduction-label">{{ $row['deduction_label'] }}</div>
          @foreach($row['categories'] as $cat)
          <div class="deduction-sub">• {{ $cat['name'] }}: MYR {{ number_format($cat['total'], 2) }}</div>
          @endforeach
        </td>
        <td class="amount">{{ $row['entries_count'] }}</td>
        <td class="amount">{{ number_format($row['total_spent'], 2) }}</td>
        <td class="amount">
          @if($row['annual_limit'])
            {{ number_format($row['annual_limit'], 2) }}
          @else
            <span class="badge-unlimited">No Limit</span>
          @endif
        </td>
        <td class="amount">
          <div class="amount-claimable">{{ number_format($row['claimable'], 2) }}</div>
          @if($row['over_limit'])
            <div class="amount-over">
              <span class="badge-over">OVER LIMIT</span>
              +{{ number_format($row['total_spent'] - $row['annual_limit'], 2) }} not claimable
            </div>
          @else
            <span class="badge-ok">OK</span>
          @endif
        </td>
      </tr>
      @endforeach

      <!-- Total row -->
      <tr class="total-row">
        <td colspan="4" class="total-label">TOTAL CLAIMABLE DEDUCTIONS</td>
        <td class="total-amount">MYR {{ number_format($totalClaimable, 2) }}</td>
      </tr>
    </tbody>
  </table>
</div>

<!-- Disclaimer -->
<div class="disclaimer">
  <p>
    <strong>Important:</strong>
    This report is generated from expense records entered in the system and is intended as a reference document to assist with LHDN income tax filing.
    All figures are based on deductions tracked for Year of Assessment <strong>{{ $year }}</strong>.
    Amounts exceeding LHDN annual limits are shown but are <strong>not claimable</strong>.
    Please verify all figures against your original receipts and supporting documents before submission.
    For accurate tax computation, consult a licensed tax agent or refer to the official LHDN MyTax portal at
    <strong>mytax.hasil.gov.my</strong>.
  </p>
</div>

<!-- Footer -->
<div class="footer">
  Generated by Finance Tracker &nbsp;|&nbsp; {{ now()->format('d M Y') }} &nbsp;|&nbsp; For tax reference purposes only
</div>

</body>
</html>