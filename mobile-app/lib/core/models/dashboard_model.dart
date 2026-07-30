class DashboardModel {
  final DashboardMetrics metrics;
  final Map<String, int> byStatus;
  final List<RecentTicket> recentTickets;

  const DashboardModel({
    required this.metrics,
    required this.byStatus,
    required this.recentTickets,
  });

  factory DashboardModel.fromJson(Map<String, dynamic> json) => DashboardModel(
        metrics: DashboardMetrics.fromJson(
            json['metrics'] as Map<String, dynamic>),
        byStatus: (json['by_status'] as Map<String, dynamic>? ?? {})
            .map((k, v) => MapEntry(k, (v as num).toInt())),
        recentTickets: (json['recent_tickets'] as List<dynamic>? ?? [])
            .map((e) => RecentTicket.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class DashboardMetrics {
  final int total;
  final int newTickets;
  final int inProgress;
  final int overdue;
  final int completed;
  final int closed;

  const DashboardMetrics({
    required this.total,
    required this.newTickets,
    required this.inProgress,
    required this.overdue,
    required this.completed,
    required this.closed,
  });

  factory DashboardMetrics.fromJson(Map<String, dynamic> json) =>
      DashboardMetrics(
        total: (json['total'] as num).toInt(),
        newTickets: (json['new'] as num).toInt(),
        inProgress: (json['in_progress'] as num).toInt(),
        overdue: (json['overdue'] as num).toInt(),
        completed: (json['completed'] as num).toInt(),
        closed: (json['closed'] as num).toInt(),
      );
}

class RecentTicket {
  final int id;
  final String ticketNo;
  final String title;
  final String status;
  final String priority;
  final String? property;
  final String? category;
  final String? createdAt;

  const RecentTicket({
    required this.id,
    required this.ticketNo,
    required this.title,
    required this.status,
    required this.priority,
    this.property,
    this.category,
    this.createdAt,
  });

  factory RecentTicket.fromJson(Map<String, dynamic> json) => RecentTicket(
        id: json['id'] as int,
        ticketNo: json['ticket_no'] as String,
        title: json['title'] as String,
        status: json['status'] as String,
        priority: json['priority'] as String,
        property: json['property'] as String?,
        category: json['category'] as String?,
        createdAt: json['created_at'] as String?,
      );
}
