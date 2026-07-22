import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../core/constants/app_colors.dart';
import '../../core/providers/cost_analysis_provider.dart';
import '../../core/models/cost_analysis_model.dart';

class CostAnalysisScreen extends StatefulWidget {
  const CostAnalysisScreen({super.key});

  @override
  State<CostAnalysisScreen> createState() => _CostAnalysisScreenState();
}

class _CostAnalysisScreenState extends State<CostAnalysisScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CostAnalysisProvider>().load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<CostAnalysisProvider>();
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          // Header
          Container(
            width: double.infinity,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [AppColors.primaryDark, AppColors.primary],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
                child: Row(
                  children: [
                    GestureDetector(
                      onTap: () => context.go('/home'),
                      child: const Icon(Icons.arrow_back_ios,
                          color: Colors.white, size: 20),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text('Cost Analysis',
                          style: TextStyle(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.bold)),
                    ),
                    IconButton(
                      icon: const Icon(Icons.refresh, color: Colors.white),
                      onPressed: () => context.read<CostAnalysisProvider>().load(),
                    ),
                    PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'pdf') {
                          context.read<CostAnalysisProvider>().exportToPDF();
                        } else if (value == 'csv') {
                          context.read<CostAnalysisProvider>().exportToCSV();
                        } else if (value == 'share') {
                          context.read<CostAnalysisProvider>().shareAsText();
                        }
                      },
                      itemBuilder: (BuildContext context) => [
                        const PopupMenuItem(
                          value: 'pdf',
                          child: Row(
                            children: [
                              Icon(Icons.picture_as_pdf, size: 20),
                              SizedBox(width: 8),
                              Text('Export as PDF'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'csv',
                          child: Row(
                            children: [
                              Icon(Icons.table_chart, size: 20),
                              SizedBox(width: 8),
                              Text('Export as CSV'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'share',
                          child: Row(
                            children: [
                              Icon(Icons.share, size: 20),
                              SizedBox(width: 8),
                              Text('Share Report'),
                            ],
                          ),
                        ),
                      ],
                      child: const Icon(Icons.more_vert, color: Colors.white),
                    ),
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            child: prov.loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
                : prov.error != null
                    ? _buildError(prov)
                    : prov.data != null
                        ? _buildContent(prov.data!)
                        : const Center(
                            child: Text('No data available',
                                style: TextStyle(color: AppColors.textSecondary)),
                          ),
          ),
        ],
      ),
    );
  }

  Widget _buildError(CostAnalysisProvider prov) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, color: AppColors.error, size: 48),
          const SizedBox(height: 12),
          Text(prov.error!,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.textSecondary)),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: () => prov.load(),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            child: const Text('Retry', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  Widget _buildContent(CostAnalysisData data) {
    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Key Metrics
            _buildMetricsGrid(data),
            const SizedBox(height: 24),
            // Cost Breakdown Chart
            if (data.costBreakdown.isNotEmpty)
              _buildCostBreakdownSection(data),
            const SizedBox(height: 24),
            // Project Summary Table
            _buildProjectSummarySection(data),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildMetricsGrid(CostAnalysisData data) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildMetricCard(
                'Total Budget',
                'GHS ${_formatCurrency(data.totalBudget)}',
                data.lastMonthBudgetChange,
                Colors.blue,
                Icons.wallet_outlined,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildMetricCard(
                'Total Cost',
                'GHS ${_formatCurrency(data.totalCost)}',
                data.lastMonthCostChange,
                Colors.green,
                Icons.attach_money_outlined,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildMetricCard(
                'Cost Variance',
                'GHS ${_formatCurrency(data.costVariance)}',
                data.lastMonthVarianceChange,
                Colors.orange,
                Icons.trending_up_outlined,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildMetricCard(
                'Budget Utilization',
                '${data.budgetUtilization.toStringAsFixed(1)}%',
                data.lastMonthUtilizationChange,
                Colors.red,
                Icons.percent,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildMetricCard(
    String label,
    String value,
    double change,
    Color color,
    IconData icon,
  ) {
    final isPositive = change >= 0;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: color, size: 20),
              ),
              const Spacer(),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            label,
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontSize: 11,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontSize: 15,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              Icon(
                isPositive ? Icons.arrow_upward : Icons.arrow_downward,
                color: isPositive ? Colors.green : Colors.red,
                size: 12,
              ),
              const SizedBox(width: 4),
              Text(
                '${change.abs().toStringAsFixed(1)}% vs last month',
                style: TextStyle(
                  color: isPositive ? Colors.green : Colors.red,
                  fontSize: 10,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCostBreakdownSection(CostAnalysisData data) {
    final items = data.costBreakdown.entries.toList();
    final colors = [
      Colors.blue[600]!,
      Colors.green[600]!,
      Colors.orange[600]!,
      Colors.purple[600]!,
      Colors.red[600]!,
    ];

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Cost Breakdown',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            height: 180,
            child: PieChart(
              PieChartData(
                sections: List.generate(
                  items.length,
                  (index) => PieChartSectionData(
                    value: items[index].value.percentage,
                    color: colors[index % colors.length],
                    radius: 60,
                    showTitle: false,
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 20),
          // Legend
          Column(
            children: List.generate(
              items.length,
              (index) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  children: [
                    Container(
                      width: 12,
                      height: 12,
                      decoration: BoxDecoration(
                        color: colors[index % colors.length],
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        items[index].value.category,
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontSize: 12,
                        ),
                      ),
                    ),
                    Text(
                      '${items[index].value.percentage.toStringAsFixed(1)}% • GHS ${_formatCurrency(items[index].value.amount)}',
                      style: const TextStyle(
                        color: AppColors.textPrimary,
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProjectSummarySection(CostAnalysisData data) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: const Text(
              'Project Cost Summary',
              style: TextStyle(
                color: AppColors.textPrimary,
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          if (data.projects.isEmpty)
            const Padding(
              padding: EdgeInsets.all(20),
              child: Text(
                'No projects available',
                style: TextStyle(color: AppColors.textSecondary),
              ),
            )
          else
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                columns: const [
                  DataColumn(
                    label: Text(
                      'Project',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                  DataColumn(
                    label: Text(
                      'Budget',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                  DataColumn(
                    label: Text(
                      'Actual Cost',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                  DataColumn(
                    label: Text(
                      'Variance',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                  DataColumn(
                    label: Text(
                      'Utilization',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
                rows: List.generate(
                  data.projects.length,
                  (index) {
                    final project = data.projects[index];
                    final statusColor = _getStatusColor(project.status);
                    return DataRow(cells: [
                      DataCell(Text(project.name)),
                      DataCell(Text('GHS ${_formatCurrency(project.budget)}')),
                      DataCell(Text('GHS ${_formatCurrency(project.actualCost)}')),
                      DataCell(
                        Text(
                          'GHS ${_formatCurrency(project.variance)}',
                          style: TextStyle(color: statusColor),
                        ),
                      ),
                      DataCell(
                        Row(
                          children: [
                            Expanded(
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: LinearProgressIndicator(
                                  value: project.utilization / 100,
                                  minHeight: 6,
                                  backgroundColor: Colors.grey[300],
                                  valueColor:
                                      AlwaysStoppedAnimation<Color>(statusColor),
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              '${project.utilization.toStringAsFixed(1)}%',
                              style: const TextStyle(fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                    ]);
                  },
                ),
              ),
            ),
        ],
      ),
    );
  }

  Color _getStatusColor(ProjectStatus status) {
    switch (status) {
      case ProjectStatus.onTrack:
        return Colors.green;
      case ProjectStatus.atRisk:
        return Colors.orange;
      case ProjectStatus.overBudget:
        return Colors.red;
    }
  }

  String _formatCurrency(double value) {
    if (value >= 1000000) {
      return '${(value / 1000000).toStringAsFixed(2)}M';
    } else if (value >= 1000) {
      return '${(value / 1000).toStringAsFixed(2)}K';
    }
    return value.toStringAsFixed(0);
  }
}
