<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cost Analysis Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.5;
            margin: 20px;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #0c5fea;
        }
        .header h1 {
            color: #0c5fea;
            font-size: 26px;
            margin-bottom: 4px;
            font-weight: bold;
        }
        .header p {
            color: #666;
            font-size: 11px;
            margin: 0;
        }
        .metrics-section {
            margin-bottom: 22px;
            page-break-inside: avoid;
        }
        .metrics-section h2 {
            color: #0c1f3f;
            font-size: 15px;
            border-bottom: 2px solid #e1e7ee;
            padding-bottom: 8px;
            margin-bottom: 12px;
            font-weight: bold;
        }
        .metrics-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .metric-item {
            display: table-cell;
            width: 25%;
            padding: 12px;
            border: 1px solid #e1e7ee;
            vertical-align: middle;
            background-color: #fafbfc;
        }
        .metric-item:nth-child(even) {
            background-color: #f3f5f8;
        }
        .metric-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 4px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-value {
            font-size: 17px;
            color: #0c1f3f;
            font-weight: bold;
            line-height: 1.3;
        }
        .category-table,
        .project-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            page-break-inside: avoid;
        }
        .category-table th,
        .project-table th {
            background-color: #f0f3f8;
            border: 1px solid #d1d7e0;
            padding: 9px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #0c1f3f;
            line-height: 1.3;
        }
        .category-table td,
        .project-table td {
            border: 1px solid #e1e7ee;
            padding: 8px;
            font-size: 10px;
            line-height: 1.4;
        }
        .category-table tr:nth-child(odd),
        .project-table tr:nth-child(odd) {
            background-color: #ffffff;
        }
        .category-table tr:nth-child(even),
        .project-table tr:nth-child(even) {
            background-color: #f8f9fb;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            min-width: 75px;
            line-height: 1.4;
        }
        .status-on-track {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-at-risk {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-over-budget {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #e1e7ee;
            font-size: 9px;
            color: #999;
            text-align: center;
            line-height: 1.4;
        }
        .page-break {
            page-break-after: always;
        }
        /* Print optimization */
        @media print {
            body {
                margin: 10mm;
                padding: 0;
            }
            .metrics-section {
                page-break-inside: avoid;
            }
            .category-table,
            .project-table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Cost Analysis Report</h1>
        <p>Generated on {{ $generatedAt }}</p>
    </div>

    <div class="metrics-section">
        <h2>Key Metrics Summary</h2>
        <div class="metrics-grid">
            <div class="metric-item">
                <div class="metric-label">Total Budget</div>
                <div class="metric-value">GHS {{ number_format($totalBudget, 2) }}</div>
            </div>
            <div class="metric-item">
                <div class="metric-label">Total Cost</div>
                <div class="metric-value">GHS {{ number_format($totalCost, 2) }}</div>
            </div>
            <div class="metric-item">
                <div class="metric-label">Variance</div>
                <div class="metric-value">GHS {{ number_format($variance, 2) }}</div>
            </div>
            <div class="metric-item">
                <div class="metric-label">Utilization</div>
                <div class="metric-value">{{ number_format($utilization, 2) }}%</div>
            </div>
        </div>
    </div>

    @if($categories->count() > 0)
    <div class="metrics-section">
        <h2>Cost Breakdown by Category</h2>
        <table class="category-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Budget</th>
                    <th>Actual Cost</th>
                    <th>Variance</th>
                    <th>Ticket Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $cat)
                <tr>
                    <td>{{ $cat['category'] }}</td>
                    <td>GHS {{ number_format($cat['budget'], 2) }}</td>
                    <td>GHS {{ number_format($cat['actual'], 2) }}</td>
                    <td>GHS {{ number_format($cat['budget'] - $cat['actual'], 2) }}</td>
                    <td>{{ $cat['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="metrics-section">
        <h2>Project Summary</h2>
        <table class="project-table">
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Budget</th>
                    <th>Actual Cost</th>
                    <th>Utilization</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($properties as $prop)
                <tr>
                    <td>{{ $prop['property'] }}</td>
                    <td>GHS {{ number_format($prop['budget'], 2) }}</td>
                    <td>GHS {{ number_format($prop['actual'], 2) }}</td>
                    <td>{{ number_format($prop['utilization'], 2) }}%</td>
                    <td>
                        <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $prop['status'])) }}">
                            {{ $prop['status'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>This report was automatically generated. For questions, please contact your administrator.</p>
    </div>
</body>
</html>
