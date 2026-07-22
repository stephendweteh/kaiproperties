@extends('layouts.app', ['title' => 'Cost Analysis'])

@section('content')
    <style>
        .cost-analysis-container {
            display: grid;
            gap: 1.5rem;
        }

        .cost-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.2rem;
        }

        .metric-card {
            border-radius: 14px;
            padding: 1.2rem;
            background: linear-gradient(180deg, #ffffff 0%, #f5f8fb 100%);
            border: 1px solid #e1e7ee;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(12, 95, 234, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .metric-card-content {
            position: relative;
            z-index: 1;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.8rem;
            font-size: 1.5rem;
        }

        .metric-label {
            font-size: 0.84rem;
            color: #667085;
            margin-bottom: 0.4rem;
            font-weight: 500;
        }

        .metric-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0c1f3f;
            margin-bottom: 0.6rem;
            letter-spacing: -0.5px;
        }

        .metric-change {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .metric-change.positive {
            color: #10b981;
        }

        .metric-change.negative {
            color: #ef4444;
        }

        .metric-change.neutral {
            color: #667085;
        }

        .metric-change svg {
            width: 16px;
            height: 16px;
        }

        .chart-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .card-section {
            border-radius: 14px;
            padding: 1.5rem;
            background: linear-gradient(180deg, #ffffff 0%, #f5f8fb 100%);
            border: 1px solid #e1e7ee;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0c1f3f;
            margin-bottom: 1.5rem;
        }

        .pie-chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 250px;
            position: relative;
        }

        .pie-chart-svg {
            width: 200px;
            height: 200px;
        }

        .chart-legend {
            display: grid;
            gap: 0.8rem;
            margin-top: 1.5rem;
        }

        .legend-item {
            display: grid;
            grid-template-columns: 16px 1fr auto;
            gap: 0.8rem;
            align-items: center;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            flex-shrink: 0;
        }

        .legend-label {
            font-size: 0.9rem;
            color: #0c1f3f;
            font-weight: 500;
        }

        .legend-value {
            font-size: 0.85rem;
            color: #667085;
            font-weight: 600;
        }

        .projects-table {
            width: 100%;
            border-collapse: collapse;
        }

        .projects-table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e1e7ee;
        }

        .projects-table th {
            padding: 0.9rem;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 700;
            color: #667085;
            letter-spacing: 0.4px;
        }

        .projects-table td {
            padding: 0.9rem;
            border-bottom: 1px solid #e1e7ee;
            font-size: 0.9rem;
            color: #0c1f3f;
        }

        .projects-table tbody tr:last-child td {
            border-bottom: none;
        }

        .projects-table tbody tr:hover {
            background: #f9fafb;
        }

        .project-name {
            font-weight: 600;
            color: #0c1f3f;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.on-track {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.at-risk {
            background: #fed7aa;
            color: #92400e;
        }

        .status-badge.over-budget {
            background: #fee2e2;
            color: #991b1b;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.4rem 0;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #0c5fea 0%, #3b82f6 100%);
            transition: width 0.3s ease;
        }

        .progress-fill.at-risk {
            background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
        }

        .progress-fill.over-budget {
            background: linear-gradient(90deg, #ef4444 0%, #f87171 100%);
        }

        .currency {
            color: #667085;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .cost-metrics-grid {
                grid-template-columns: 1fr 1fr;
            }

            .chart-section {
                grid-template-columns: 1fr;
            }

            .projects-table {
                font-size: 0.8rem;
            }

            .projects-table th,
            .projects-table td {
                padding: 0.6rem;
            }
        }
    </style>

    <div class="content-header">
        <h1>Cost Analysis</h1>
        <p>Analyze and generate financial reports on the entire system</p>
    </div>

    <div style="margin-bottom: 1.5rem; display: flex; gap: 0.8rem; flex-wrap: wrap;">
        <a href="{{ route('cost-analysis.export', ['format' => 'pdf']) }}" download class="btn btn-primary" style="background: #ef4444; color: white; padding: 0.6rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                <path d="M12 2v20m9-9H3"></path>
            </svg>
            Export to PDF
        </a>
        <a href="{{ route('cost-analysis.export', ['format' => 'xlsx']) }}" download class="btn btn-primary" style="background: #0c5fea; color: white; padding: 0.6rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                <path d="M12 2v20m9-9H3"></path>
            </svg>
            Export to Excel
        </a>
        <a href="{{ route('cost-analysis.export', ['format' => 'csv']) }}" download class="btn btn-secondary" style="background: #6b7280; color: white; padding: 0.6rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                <path d="M12 2v20m9-9H3"></path>
            </svg>
            Export to CSV
        </a>
    </div>

    <div class="cost-analysis-container">
        <!-- Key Metrics -->
        <div class="cost-metrics-grid">
            <div class="metric-card">
                <div class="metric-card-content">
                    <div class="metric-icon" style="background: rgba(12, 95, 234, 0.1); color: #0c5fea;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                    <div class="metric-label">Total Budget</div>
                    <div class="metric-value">GHS {{ number_format($totalBudget, 0) }}</div>
                    <div class="metric-change {{ $lastMonthData['budget_change'] >= 0 ? 'positive' : 'negative' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            @if($lastMonthData['budget_change'] >= 0)
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 17"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            @else
                                <polyline points="23 18 13.5 8.5 8.5 13.5 1 7"></polyline>
                                <polyline points="17 18 23 18 23 12"></polyline>
                            @endif
                        </svg>
                        <span>{{ abs($lastMonthData['budget_change']) }}% vs last month</span>
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-content">
                    <div class="metric-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1"></circle>
                            <path d="M12 1v6m0 6v6"></path>
                            <path d="M4.22 4.22l4.24 4.24m-4.24 5.08l4.24 4.24"></path>
                            <path d="M1 12h6m6 0h6"></path>
                            <path d="M4.22 19.78l4.24-4.24m5.08 4.24l4.24-4.24"></path>
                            <path d="M12 20v4"></path>
                        </svg>
                    </div>
                    <div class="metric-label">Total Cost</div>
                    <div class="metric-value">GHS {{ number_format($totalCost, 0) }}</div>
                    <div class="metric-change {{ $lastMonthData['cost_change'] >= 0 ? 'positive' : 'negative' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            @if($lastMonthData['cost_change'] >= 0)
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 17"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            @else
                                <polyline points="23 18 13.5 8.5 8.5 13.5 1 7"></polyline>
                                <polyline points="17 18 23 18 23 12"></polyline>
                            @endif
                        </svg>
                        <span>{{ abs($lastMonthData['cost_change']) }}% vs last month</span>
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-content">
                    <div class="metric-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                            <polyline points="13 2 13 9 20 9"></polyline>
                        </svg>
                    </div>
                    <div class="metric-label">Cost Variance</div>
                    <div class="metric-value">GHS {{ number_format($costVariance, 0) }}</div>
                    <div class="metric-change {{ $lastMonthData['variance_change'] >= 0 ? 'positive' : 'negative' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            @if($lastMonthData['variance_change'] >= 0)
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 17"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            @else
                                <polyline points="23 18 13.5 8.5 8.5 13.5 1 7"></polyline>
                                <polyline points="17 18 23 18 23 12"></polyline>
                            @endif
                        </svg>
                        <span>{{ abs($lastMonthData['variance_change']) }}% vs last month</span>
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-content">
                    <div class="metric-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1"></circle>
                            <circle cx="12" cy="5" r="1"></circle>
                            <circle cx="12" cy="19" r="1"></circle>
                            <circle cx="5" cy="12" r="1"></circle>
                            <circle cx="19" cy="12" r="1"></circle>
                        </svg>
                    </div>
                    <div class="metric-label">Budget Utilization</div>
                    <div class="metric-value">{{ number_format($budgetUtilization, 1) }}%</div>
                    <div class="metric-change {{ $lastMonthData['utilization_change'] >= 0 ? 'positive' : 'negative' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            @if($lastMonthData['utilization_change'] >= 0)
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 17"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            @else
                                <polyline points="23 18 13.5 8.5 8.5 13.5 1 7"></polyline>
                                <polyline points="17 18 23 18 23 12"></polyline>
                            @endif
                        </svg>
                        <span>{{ abs($lastMonthData['utilization_change']) }}% vs last month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="chart-section">
            <!-- Cost Breakdown Pie Chart -->
            @if($costBreakdown->count() > 0)
                <div class="card-section">
                    <div class="section-title">Cost Breakdown</div>
                    <div class="pie-chart-container">
                        <svg class="pie-chart-svg" viewBox="0 0 100 100">
                            @php
                                $angleOffset = 0;
                            @endphp
                            @foreach($costBreakdown as $item)
                                @php
                                    $sliceAngle = ($item['percentage'] / 100) * 360;
                                    $startAngle = $angleOffset;
                                    $endAngle = $angleOffset + $sliceAngle;
                                    $startRad = deg2rad($startAngle);
                                    $endRad = deg2rad($endAngle);
                                    
                                    $x1 = 50 + 40 * cos($startRad);
                                    $y1 = 50 + 40 * sin($startRad);
                                    $x2 = 50 + 40 * cos($endRad);
                                    $y2 = 50 + 40 * sin($endRad);
                                    
                                    $largeArc = $sliceAngle > 180 ? 1 : 0;
                                    $color = $item['color'] ?? '#8b5cf6';
                                    $angleOffset = $endAngle;
                                @endphp
                                <path d="M 50 50 L {{ $x1 }} {{ $y1 }} A 40 40 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} Z" fill="{{ $color }}" stroke="white" stroke-width="2"/>
                            @endforeach
                        </svg>
                    </div>
                    <div class="chart-legend">
                        @foreach($costBreakdown as $item)
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: {{ $item['color'] ?? '#8b5cf6' }};"></div>
                                <div class="legend-label">{{ $item['category'] }}</div>
                                <div class="legend-value">{{ number_format($item['percentage'], 1) }}% • GHS {{ number_format($item['amount'], 0) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Budget vs Actual -->
            <div class="card-section">
                <div class="section-title">Budget vs Actual Cost</div>
                <div style="display: grid; gap: 1.5rem;">
                    <div>
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; margin-bottom: 0.6rem;">
                            <span style="color: #667085; font-weight: 500;">Budgeted Amount</span>
                            <span style="color: #0c1f3f; font-weight: 700;">GHS {{ number_format($totalBudget, 0) }}</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 100%;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; margin-bottom: 0.6rem;">
                            <span style="color: #667085; font-weight: 500;">Actual Spending</span>
                            <span style="color: #0c1f3f; font-weight: 700;">GHS {{ number_format($totalCost, 0) }}</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ min($budgetUtilization, 100) }}%;"></div>
                        </div>
                    </div>
                    <div style="padding-top: 0.5rem; border-top: 1px solid #e1e7ee;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem;">
                            <span style="color: #667085; font-weight: 500;">Remaining Budget</span>
                            <span style="color: #10b981; font-weight: 700;">GHS {{ number_format(max(0, $totalBudget - $totalCost), 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="card-section">
            <div class="section-title">Project Cost Summary</div>
            @if($projects && count($projects) > 0)
                <div style="overflow-x: auto;">
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Budget</th>
                                <th>Actual Cost</th>
                                <th>Variance</th>
                                <th>Utilization</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                                <tr>
                                    <td class="project-name">{{ $project['name'] }}</td>
                                    <td><span class="currency">GHS</span> {{ number_format($project['budget'], 0) }}</td>
                                    <td><span class="currency">GHS</span> {{ number_format($project['actual_cost'], 0) }}</td>
                                    <td>
                                        <span style="color: {{ $project['variance'] >= 0 ? '#10b981' : '#ef4444' }}; font-weight: 600;">
                                            <span class="currency">GHS</span> {{ number_format($project['variance'], 0) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: grid; grid-template-columns: 1fr 50px; gap: 0.6rem; align-items: center;">
                                            <div class="progress-bar">
                                                <div class="progress-fill {{ $project['utilization'] > 100 ? 'over-budget' : ($project['utilization'] > 80 ? 'at-risk' : '') }}"
                                                     style="width: {{ min($project['utilization'], 100) }}%;"></div>
                                            </div>
                                            <span style="font-weight: 600; color: #0c1f3f;">{{ number_format($project['utilization'], 1) }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ str_replace('_', '-', $project['status']) }}">
                                            {{ str_replace('_', ' ', $project['status']) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 2rem; color: #667085;">
                    <p>No projects available</p>
                </div>
            @endif
        </div>
    </div>
@endsection
