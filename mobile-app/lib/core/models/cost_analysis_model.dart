class CostAnalysisData {
  final double totalBudget;
  final double totalCost;
  final double costVariance;
  final double budgetUtilization; // percentage
  final double lastMonthBudgetChange; // percentage
  final double lastMonthCostChange; // percentage
  final double lastMonthVarianceChange; // percentage
  final double lastMonthUtilizationChange; // percentage
  final List<ProjectCostItem> projects;
  final Map<String, CostBreakdownItem> costBreakdown;

  CostAnalysisData({
    required this.totalBudget,
    required this.totalCost,
    required this.costVariance,
    required this.budgetUtilization,
    required this.lastMonthBudgetChange,
    required this.lastMonthCostChange,
    required this.lastMonthVarianceChange,
    required this.lastMonthUtilizationChange,
    required this.projects,
    required this.costBreakdown,
  });

  factory CostAnalysisData.fromJson(Map<String, dynamic> json) {
    return CostAnalysisData(
      totalBudget: (json['total_budget'] as num?)?.toDouble() ?? 0.0,
      totalCost: (json['total_cost'] as num?)?.toDouble() ?? 0.0,
      costVariance: (json['cost_variance'] as num?)?.toDouble() ?? 0.0,
      budgetUtilization: (json['budget_utilization'] as num?)?.toDouble() ?? 0.0,
      lastMonthBudgetChange: (json['last_month_budget_change'] as num?)?.toDouble() ?? 0.0,
      lastMonthCostChange: (json['last_month_cost_change'] as num?)?.toDouble() ?? 0.0,
      lastMonthVarianceChange: (json['last_month_variance_change'] as num?)?.toDouble() ?? 0.0,
      lastMonthUtilizationChange: (json['last_month_utilization_change'] as num?)?.toDouble() ?? 0.0,
      projects: (json['projects'] as List<dynamic>?)
          ?.map((p) => ProjectCostItem.fromJson(p as Map<String, dynamic>))
          .toList() ?? [],
      costBreakdown: (json['cost_breakdown'] as Map<String, dynamic>?)
          ?.map((k, v) => MapEntry(k, CostBreakdownItem.fromJson(v as Map<String, dynamic>)))
          ?? {},
    );
  }
}

class ProjectCostItem {
  final int id;
  final String name;
  final double budget;
  final double actualCost;
  final double variance;
  final double utilization; // percentage
  final ProjectStatus status;
  final String? propertyName;

  ProjectCostItem({
    required this.id,
    required this.name,
    required this.budget,
    required this.actualCost,
    required this.variance,
    required this.utilization,
    required this.status,
    this.propertyName,
  });

  factory ProjectCostItem.fromJson(Map<String, dynamic> json) {
    return ProjectCostItem(
      id: json['id'] as int? ?? 0,
      name: json['name'] as String? ?? '',
      budget: (json['budget'] as num?)?.toDouble() ?? 0.0,
      actualCost: (json['actual_cost'] as num?)?.toDouble() ?? 0.0,
      variance: (json['variance'] as num?)?.toDouble() ?? 0.0,
      utilization: (json['utilization'] as num?)?.toDouble() ?? 0.0,
      status: ProjectStatus.values.firstWhere(
        (e) => e.name == (json['status'] as String?)?.toLowerCase(),
        orElse: () => ProjectStatus.onTrack,
      ),
      propertyName: json['property_name'] as String?,
    );
  }
}

enum ProjectStatus {
  onTrack,
  atRisk,
  overBudget,
}

class CostBreakdownItem {
  final String category;
  final double amount;
  final double percentage;

  CostBreakdownItem({
    required this.category,
    required this.amount,
    required this.percentage,
  });

  factory CostBreakdownItem.fromJson(Map<String, dynamic> json) {
    return CostBreakdownItem(
      category: json['category'] as String? ?? '',
      amount: (json['amount'] as num?)?.toDouble() ?? 0.0,
      percentage: (json['percentage'] as num?)?.toDouble() ?? 0.0,
    );
  }
}
