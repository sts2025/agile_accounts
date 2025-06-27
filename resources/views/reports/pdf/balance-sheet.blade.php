<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Balance Sheet</title>
    <style>/* ... Use the same styles as the other PDF reports ... */</style>
</head>
<body>
    <div class="header">
        <h1>Agile Accounts</h1>
        <h2>Balance Sheet</h2>
        <p>As of {{ $asOfDate->format('F d, Y') }}</p>
    </div>
    <hr>
    <div style="font-size: 16px; font-weight: bold; margin-top: 20px;">
        <p>Total Assets: UGX {{ number_format($totalAssets, 2) }}</p>
        <p>Total Liabilities & Equity: UGX {{ number_format($totalLiabilitiesAndEquity, 2) }}</p>
    </div>
</body>
</html>