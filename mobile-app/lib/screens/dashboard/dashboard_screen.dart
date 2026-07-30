import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../core/constants/app_colors.dart';
import '../../core/providers/dashboard_provider.dart';
import '../../core/models/dashboard_model.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DashboardProvider>().load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<DashboardProvider>();
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
                      child: Text('Dashboard',
                          style: TextStyle(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.bold)),
                    ),
                    IconButton(
                      icon: const Icon(Icons.refresh, color: Colors.white),
                      onPressed: () => context.read<DashboardProvider>().load(),
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
                    : _buildContent(prov.data!),
          ),
        ],
      ),
    );
  }

  Widget _buildError(DashboardProvider prov) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, color: AppColors.error, size: 48),
          const SizedBox(height: 12),
          Text(prov.error!,
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

  Widget _buildContent(DashboardModel data) {
    final m = data.metrics;
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: () => context.read<DashboardProvider>().load(),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Stats grid
          GridView.count(
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            childAspectRatio: 1.5,
            children: [
              _StatCard(
                'Total Tasks',
                m.total,
                Icons.assignment_outlined,
                AppColors.primary,
                onTap: () => context.go('/tasks'),
              ),
              _StatCard(
                'In Progress',
                m.inProgress,
                Icons.pending_actions,
                AppColors.statusInProgress,
                onTap: () => context.go('/tasks?status=in_progress'),
              ),
              _StatCard(
                'Completed',
                m.completed,
                Icons.check_circle_outline,
                AppColors.statusCompleted,
                onTap: () => context.go('/tasks?status=completed'),
              ),
              _StatCard(
                'Overdue',
                m.overdue,
                Icons.warning_amber_outlined,
                AppColors.statusOverdue,
                onTap: () => context.go('/tasks?status=overdue'),
              ),
              _StatCard(
                'New',
                m.newTickets,
                Icons.fiber_new_outlined,
                AppColors.statusLogged,
                onTap: () => context.go('/tasks?status=logged'),
              ),
              _StatCard(
                'Closed',
                m.closed,
                Icons.lock_outline,
                AppColors.statusClosed,
                onTap: () => context.go('/tasks?status=closed'),
              ),
            ],
          ),
          const SizedBox(height: 20),
          // Pie chart
          if (data.byStatus.isNotEmpty) _buildPieChart(data.byStatus),
          const SizedBox(height: 20),
          // Recent tickets
          if (data.recentTickets.isNotEmpty) ...[
            const Text('Recent Tasks',
                style: TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 16,
                    fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            ...data.recentTickets.map(
              (t) => _RecentTicketTile(
                ticket: t,
                onTap: () => context.go('/tasks/${t.id}'),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildPieChart(Map<String, int> byStatus) {
    final entries = byStatus.entries
        .where((e) => e.value > 0)
        .toList();
    final sections = entries.map((e) {
      final color = AppColors.statusColor(e.key);
      return PieChartSectionData(
        value: e.value.toDouble(),
        color: color,
        radius: 55,
        title: '${e.value}',
        titleStyle: const TextStyle(
            color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
      );
    }).toList();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [BoxShadow(color: AppColors.shadow, blurRadius: 8)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Tasks by Status',
              style: TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 15,
                  fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          SizedBox(
            height: 180,
            child: PieChart(PieChartData(
              sections: sections,
              centerSpaceRadius: 32,
              sectionsSpace: 2,
            )),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 6,
            children: entries.map((e) {
              final color = AppColors.statusColor(e.key);
              return Material(
                color: Colors.transparent,
                child: InkWell(
                  borderRadius: BorderRadius.circular(8),
                  onTap: () => context.go('/tasks?status=${e.key}'),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 4,
                      vertical: 2,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                            width: 10,
                            height: 10,
                            decoration: BoxDecoration(
                                color: color, shape: BoxShape.circle)),
                        const SizedBox(width: 4),
                        Text(e.key.replaceAll('_', ' '),
                            style: const TextStyle(
                                color: AppColors.textSecondary, fontSize: 11)),
                      ],
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final int value;
  final IconData icon;
  final Color color;
  final VoidCallback? onTap;

  const _StatCard(
    this.label,
    this.value,
    this.icon,
    this.color, {
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            boxShadow: const [BoxShadow(color: AppColors.shadow, blurRadius: 6)],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: color, size: 24),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('$value',
                      style: TextStyle(
                          color: color,
                          fontSize: 26,
                          fontWeight: FontWeight.bold)),
                  Text(label,
                      style: const TextStyle(
                          color: AppColors.textSecondary, fontSize: 11)),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RecentTicketTile extends StatelessWidget {
  final RecentTicket ticket;
  final VoidCallback? onTap;

  const _RecentTicketTile({required this.ticket, this.onTap});

  @override
  Widget build(BuildContext context) {
    final color = AppColors.statusColor(ticket.status);
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border(left: BorderSide(color: color, width: 4)),
            boxShadow: const [BoxShadow(color: AppColors.shadow, blurRadius: 4)],
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(ticket.title,
                        style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontWeight: FontWeight.w600,
                            fontSize: 13),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 2),
                    Text('${ticket.ticketNo} · ${ticket.property ?? 'N/A'}',
                        style: const TextStyle(
                            color: AppColors.textSecondary, fontSize: 11)),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  ticket.status.replaceAll('_', ' ').toUpperCase(),
                  style: TextStyle(
                      color: color, fontSize: 9, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
