@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    <style>
        .dashboard-stack {
            display: grid;
            gap: 1rem;
        }

        .dashboard-grid-wide {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1rem;
        }

        .dashboard-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.8rem;
        }

        .dashboard-stat-card {
            border-radius: 14px;
            padding: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f5f8fb 100%);
            border: 1px solid #e1e7ee;
        }

        .dashboard-stat-card .metric {
            margin-top: 0.35rem;
        }

        .dashboard-panel-title {
            margin: 0 0 0.85rem;
        }

        .dashboard-bars {
            display: grid;
            gap: 0.8rem;
        }

        .dashboard-bar-row {
            display: grid;
            grid-template-columns: minmax(110px, 160px) 1fr auto;
            gap: 0.75rem;
            align-items: center;
        }

        .dashboard-bar-track {
            height: 12px;
            border-radius: 999px;
            background: #e7edf3;
            overflow: hidden;
        }

        .dashboard-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #1f7ae0 0%, #49b0ff 100%);
        }

        .dashboard-split {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.8rem;
        }

        .dashboard-split-card {
            padding: 0.95rem;
            border-radius: 12px;
            border: 1px solid #e1e7ee;
            background: #fbfcfe;
        }

        .dashboard-split-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #102a43;
        }

        .dashboard-mini-note {
            font-size: 0.84rem;
            color: #627d98;
        }

        .dashboard-pie-layout {
            display: grid;
            grid-template-columns: minmax(220px, 300px) 1fr;
            gap: 1rem;
            align-items: center;
        }

        .dashboard-pie-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .dashboard-pie-chart {
            width: 240px;
            height: 240px;
            border-radius: 50%;
            position: relative;
            box-shadow: inset 0 0 0 1px rgba(16, 42, 67, 0.08);
        }

        .dashboard-pie-chart::after {
            content: '';
            position: absolute;
            inset: 50%;
            transform: translate(-50%, -50%);
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 0 0 1px rgba(16, 42, 67, 0.06);
        }

        .dashboard-pie-center {
            position: absolute;
            inset: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            text-align: center;
        }

        .dashboard-pie-total {
            font-size: 1.4rem;
            font-weight: 700;
            color: #102a43;
        }

        .dashboard-pie-label {
            font-size: 0.72rem;
            color: #627d98;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .dashboard-pie-legend {
            display: grid;
            gap: 0.7rem;
        }

        .dashboard-pie-legend-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 0.65rem;
            align-items: center;
            padding-bottom: 0.55rem;
            border-bottom: 1px solid #eef2f6;
        }

        .dashboard-pie-swatch {
            width: 12px;
            height: 12px;
            border-radius: 999px;
        }

        @media (max-width: 820px) {
            .dashboard-pie-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <h2>Operations Dashboard</h2>
    <p class="muted">Track current maintenance requests across all properties.</p>

    <div class="dashboard-stack">
        <section class="dashboard-stat-grid">
            <article class="dashboard-stat-card">
                <div class="muted">Total Tickets</div>
                <div class="metric">{{ $metrics['total'] }}</div>
            </article>
            <article class="dashboard-stat-card">
                <div class="muted">New</div>
                <div class="metric">{{ $metrics['new'] }}</div>
            </article>
            <article class="dashboard-stat-card">
                <div class="muted">In Progress</div>
                <div class="metric">{{ $metrics['in_progress'] }}</div>
            </article>
            <article class="dashboard-stat-card">
                <div class="muted">Overdue</div>
                <div class="metric">{{ $metrics['overdue'] }}</div>
            </article>
            <article class="dashboard-stat-card">
                <div class="muted">Completed</div>
                <div class="metric">{{ $metrics['completed'] }}</div>
            </article>
            <article class="dashboard-stat-card">
                <div class="muted">Closed</div>
                <div class="metric">{{ $metrics['closed'] }}</div>
            </article>
        </section>

        <section class="dashboard-grid-wide">
            <article class="card">
                <h3 class="dashboard-panel-title">Customer Statistics</h3>
                <div class="dashboard-split">
                    <div class="dashboard-split-card">
                        <div class="muted">Total Customers</div>
                        <div class="dashboard-split-value">{{ $customerMetrics['total'] }}</div>
                        <div class="dashboard-mini-note">All registered customers in the system.</div>
                    </div>
                    <div class="dashboard-split-card">
                        <div class="muted">Active Customers</div>
                        <div class="dashboard-split-value">{{ $customerMetrics['active'] }}</div>
                        <div class="dashboard-mini-note">Customers currently marked active.</div>
                    </div>
                    <div class="dashboard-split-card">
                        <div class="muted">With Properties</div>
                        <div class="dashboard-split-value">{{ $customerMetrics['with_properties'] }}</div>
                        <div class="dashboard-mini-note">Customers already linked to at least one property.</div>
                    </div>
                    <div class="dashboard-split-card">
                        <div class="muted">Without Properties</div>
                        <div class="dashboard-split-value">{{ $customerMetrics['without_properties'] }}</div>
                        <div class="dashboard-mini-note">Customers waiting for property assignment.</div>
                    </div>
                </div>
            </article>

            <article class="card">
                <h3 class="dashboard-panel-title">Property Statistics</h3>
                <div class="dashboard-split">
                    <div class="dashboard-split-card">
                        <div class="muted">Total Properties</div>
                        <div class="dashboard-split-value">{{ $propertyMetrics['total'] }}</div>
                        <div class="dashboard-mini-note">All properties currently tracked.</div>
                    </div>
                    <div class="dashboard-split-card">
                        <div class="muted">Active Properties</div>
                        <div class="dashboard-split-value">{{ $propertyMetrics['active'] }}</div>
                        <div class="dashboard-mini-note">Properties available for ticket activity.</div>
                    </div>
                    <div class="dashboard-split-card">
                        <div class="muted">Assigned Properties</div>
                        <div class="dashboard-split-value">{{ $propertyMetrics['assigned'] }}</div>
                        <div class="dashboard-mini-note">Properties linked to a customer.</div>
                    </div>
                    <div class="dashboard-split-card">
                        <div class="muted">Unassigned Properties</div>
                        <div class="dashboard-split-value">{{ $propertyMetrics['unassigned'] }}</div>
                        <div class="dashboard-mini-note">Properties that still need a customer.</div>
                    </div>
                </div>
            </article>
        </section>

        <section class="dashboard-grid-wide">
            <article class="card">
                <h3 class="dashboard-panel-title">Ticket Status Distribution</h3>
                <div class="dashboard-bars">
                    @foreach($ticketStatusBreakdown as $entry)
                        <div class="dashboard-bar-row">
                            <div>{{ $entry['label'] }}</div>
                            <div class="dashboard-bar-track">
                                <div class="dashboard-bar-fill" style="width: {{ max($entry['percentage'], $entry['value'] > 0 ? 6 : 0) }}%;"></div>
                            </div>
                            <div>{{ $entry['value'] }} ({{ $entry['percentage'] }}%)</div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="card">
                <h3 class="dashboard-panel-title">Customer Property Distribution</h3>
                <div class="dashboard-bars">
                    @php
                        $highestCustomerPropertyCount = max((int) ($topCustomers->max('properties_count') ?? 0), 1);
                    @endphp
                    @forelse($topCustomers as $customer)
                        <div class="dashboard-bar-row">
                            <div>{{ $customer->name }}</div>
                            <div class="dashboard-bar-track">
                                <div class="dashboard-bar-fill" style="width: {{ $customer->properties_count > 0 ? max((int) round(($customer->properties_count / $highestCustomerPropertyCount) * 100), 8) : 0 }}%; background: linear-gradient(90deg, #16a34a 0%, #6dd3a0 100%);"></div>
                            </div>
                            <div>{{ $customer->properties_count }}</div>
                        </div>
                    @empty
                        <p class="muted" style="margin:0;">No customers available yet.</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>

    <section class="card" style="margin-top: 1rem;">
        <h3>Status Colors</h3>
        <div class="status-legend">
            <span class="status-pill status-logged">Logged/New</span>
            <span class="status-pill status-in_progress">In Progress</span>
            <span class="status-pill status-overdue">Overdue</span>
            <span class="status-pill status-completed">Completed</span>
            <span class="status-pill status-closed">Closed</span>
        </div>
    </section>

    <section class="card" style="margin-top: 1rem;">
        <h3>Technician Workload</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Technician</th>
                    <th>Assigned Tickets</th>
                </tr>
                </thead>
                <tbody>
                @forelse($workload as $entry)
                    <tr>
                        <td>{{ $entry->name }}</td>
                        <td>{{ $entry->tickets_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No assignments yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card" style="margin-top: 1rem;">
        <h3>Property Statistics</h3>
        @php
            $propertyChartPalette = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#ea580c', '#4f46e5'];
            $propertyTicketTotal = max((int) $propertyStats->sum('tickets_count'), 0);
            $pieSegments = [];
            $offset = 0;

            foreach ($propertyStats as $index => $entry) {
                $value = (int) $entry->tickets_count;

                if ($value <= 0 || $propertyTicketTotal === 0) {
                    continue;
                }

                $degrees = ($value / $propertyTicketTotal) * 360;
                $color = $propertyChartPalette[$index % count($propertyChartPalette)];
                $pieSegments[] = [
                    'name' => $entry->name,
                    'value' => $value,
                    'color' => $color,
                    'percentage' => (int) round(($value / $propertyTicketTotal) * 100),
                    'start' => $offset,
                    'end' => $offset + $degrees,
                ];
                $offset += $degrees;
            }

            $pieBackground = count($pieSegments) > 0
                ? collect($pieSegments)
                    ->map(fn ($segment) => $segment['color'].' '.$segment['start'].'deg '.$segment['end'].'deg')
                    ->implode(', ')
                : '#e7edf3';
        @endphp

        @if($propertyStats->isEmpty())
            <p class="muted" style="margin:0;">No properties found.</p>
        @else
            <div class="dashboard-pie-layout">
                <div class="dashboard-pie-wrap">
                    <div class="dashboard-pie-chart" style="background: conic-gradient({{ $pieBackground }});">
                        <div class="dashboard-pie-center">
                            <div class="dashboard-pie-total">{{ $propertyTicketTotal }}</div>
                            <div class="dashboard-pie-label">Tickets</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-pie-legend">
                    @forelse($pieSegments as $segment)
                        <div class="dashboard-pie-legend-item">
                            <span class="dashboard-pie-swatch" style="background: {{ $segment['color'] }};"></span>
                            <div>
                                <div>{{ $segment['name'] }}</div>
                                <div class="dashboard-mini-note">{{ $segment['percentage'] }}% of property tickets</div>
                            </div>
                            <div>{{ $segment['value'] }}</div>
                        </div>
                    @empty
                        <p class="muted" style="margin:0;">No ticket activity recorded for properties yet.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </section>
@endsection
