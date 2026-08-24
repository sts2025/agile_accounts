<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Prudential Returns</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #ddd; text-align: left; }
        .text-end { text-align: right; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        h3 { font-size: 14px; margin-top: 18px; margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Prudential Returns</h1>
        <p>As of {{ $asOf->format('F d, Y') }}</p>
    </div>

    <h3>Regulatory Ratios</h3>
    <table>
        <thead><tr><th>Ratio</th><th class="text-end">Value</th></tr></thead>
        <tbody>
            <tr><td>Portfolio at Risk (30 days)</td><td class="text-end">{{ $ratios['par_30'] === null ? 'N/A' : number_format($ratios['par_30'], 2) . '%' }}</td></tr>
            <tr><td>Portfolio at Risk (90 days)</td><td class="text-end">{{ $ratios['par_90'] === null ? 'N/A' : number_format($ratios['par_90'], 2) . '%' }}</td></tr>
            <tr><td>Reserve Adequacy</td><td class="text-end">{{ $ratios['reserve_adequacy'] === null ? 'N/A' : number_format($ratios['reserve_adequacy'], 2) . '%' }}</td></tr>
            <tr><td>NPL Coverage Ratio</td><td class="text-end">{{ $ratios['npl_coverage'] === null ? 'N/A' : number_format($ratios['npl_coverage'], 2) . '%' }}</td></tr>
            <tr><td>Liquidity Ratio</td><td class="text-end">{{ $ratios['liquidity'] === null ? 'N/A' : number_format($ratios['liquidity'], 2) . '%' }}</td></tr>
            <tr><td>Loans-to-Deposits Ratio</td><td class="text-end">{{ $ratios['loans_to_deposits'] === null ? 'N/A' : number_format($ratios['loans_to_deposits'], 2) . '%' }}</td></tr>
            <tr><td>Equity-to-Assets Ratio</td><td class="text-end">{{ $ratios['equity_to_assets'] === null ? 'N/A' : number_format($ratios['equity_to_assets'], 2) . '%' }}</td></tr>
        </tbody>
    </table>

    <h3>Underlying Figures</h3>
    <table>
        <tbody>
            <tr><td>Cash on Hand</td><td class="text-end">{{ $cashOnHand === null ? 'N/A' : number_format($cashOnHand, 2) }}</td></tr>
            <tr><td>Cash at Bank</td><td class="text-end">{{ $cashAtBank === null ? 'N/A' : number_format($cashAtBank, 2) }}</td></tr>
            <tr><td>Gross Loan Portfolio</td><td class="text-end">{{ $loanPortfolio === null ? 'N/A' : number_format($loanPortfolio, 2) }}</td></tr>
            <tr><td>Loan Loss Reserve</td><td class="text-end">{{ $loanLossReserve === null ? 'N/A' : number_format($loanLossReserve, 2) }}</td></tr>
            <tr><td>Net Loan Portfolio</td><td class="text-end">{{ $netLoanPortfolio === null ? 'N/A' : number_format($netLoanPortfolio, 2) }}</td></tr>
            <tr><td>Member Savings</td><td class="text-end">{{ $memberSavings === null ? 'N/A' : number_format($memberSavings, 2) }}</td></tr>
            <tr><td>Member Fixed Deposits</td><td class="text-end">{{ $memberFixedDeposits === null ? 'N/A' : number_format($memberFixedDeposits, 2) }}</td></tr>
            <tr><td>Total Deposits</td><td class="text-end">{{ $totalDeposits === null ? 'N/A' : number_format($totalDeposits, 2) }}</td></tr>
            <tr><td>Total Assets</td><td class="text-end">{{ number_format($totalAssets, 2) }}</td></tr>
            <tr><td>Total Liabilities</td><td class="text-end">{{ number_format($totalLiabilities, 2) }}</td></tr>
            <tr><td>Total Equity</td><td class="text-end">{{ number_format($totalEquity, 2) }}</td></tr>
            <tr><td>30+ Days Portfolio at Risk</td><td class="text-end">{{ number_format($par30Outstanding, 2) }}</td></tr>
            <tr><td>90+ Days Portfolio at Risk</td><td class="text-end">{{ number_format($par90Outstanding, 2) }}</td></tr>
            <tr><td>Required Provision (current classification)</td><td class="text-end">{{ number_format($totalRequiredProvision, 2) }}</td></tr>
        </tbody>
    </table>
</body>
</html>
