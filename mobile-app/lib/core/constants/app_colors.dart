import 'package:flutter/material.dart';

class AppColors {
  AppColors._();

  // Brand
  static const Color primary = Color(0xFF2D6B8A);
  static const Color primaryDark = Color(0xFF1E4D66);
  static const Color primaryLight = Color(0xFF4A8FAD);
  static const Color accent = Color(0xFFE07B39);

  // Background
  static const Color background = Color(0xFFF0F4F8);
  static const Color cardBg = Colors.white;
  static const Color surfaceGrey = Color(0xFFECEFF1);

  // Text
  static const Color textPrimary = Color(0xFF1A2B38);
  static const Color textSecondary = Color(0xFF607D8B);
  static const Color textLight = Color(0xFF90A4AE);
  static const Color textOnPrimary = Colors.white;

  // Status colours
  static const Color statusLogged = Color(0xFF2196F3);
  static const Color statusAssigned = Color(0xFF673AB7);
  static const Color statusInProgress = Color(0xFFFF9800);
  static const Color statusPendingApproval = Color(0xFFFF9800);
  static const Color statusOnHold = Color(0xFF9E9E9E);
  static const Color statusCompleted = Color(0xFF4CAF50);
  static const Color statusClosed = Color(0xFF607D8B);
  static const Color statusRejected = Color(0xFFF44336);
  static const Color statusOverdue = Color(0xFFF44336);

  // Priority
  static const Color priorityLow = Color(0xFF4CAF50);
  static const Color priorityMedium = Color(0xFFFF9800);
  static const Color priorityHigh = Color(0xFFF44336);
  static const Color priorityUrgent = Color(0xFF9C27B0);

  // Utility
  static const Color divider = Color(0xFFECEFF1);
  static const Color shadow = Color(0x1A000000);
  static const Color error = Color(0xFFF44336);
  static const Color success = Color(0xFF4CAF50);

  static Color statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'logged':
        return statusLogged;
      case 'assigned':
        return statusAssigned;
      case 'in_progress':
        return statusInProgress;
      case 'pending_approval':
        return statusPendingApproval;
      case 'on_hold':
        return statusOnHold;
      case 'completed':
        return statusCompleted;
      case 'closed':
        return statusClosed;
      case 'rejected':
        return statusRejected;
      case 'overdue':
        return statusOverdue;
      default:
        return textSecondary;
    }
  }

  static Color priorityColor(String priority) {
    switch (priority.toLowerCase()) {
      case 'low':
        return priorityLow;
      case 'medium':
        return priorityMedium;
      case 'high':
        return priorityHigh;
      case 'urgent':
        return priorityUrgent;
      default:
        return textSecondary;
    }
  }
}
