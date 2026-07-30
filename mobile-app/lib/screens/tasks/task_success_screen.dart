import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/constants/app_colors.dart';

class TaskSuccessScreen extends StatelessWidget {
  final String ticketNo;
  final String? message;
  final int? ticketId;
  final int attachmentCount;

  const TaskSuccessScreen({
    super.key,
    required this.ticketNo,
    this.message,
    this.ticketId,
    this.attachmentCount = 0,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Success circle
              Container(
                width: 110,
                height: 110,
                decoration: BoxDecoration(
                  color: AppColors.statusCompleted.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: Container(
                  margin: const EdgeInsets.all(12),
                  decoration: const BoxDecoration(
                    color: AppColors.statusCompleted,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.check, color: Colors.white, size: 48),
                ),
              ),
              const SizedBox(height: 28),
              const Text(
                'Task Submitted!',
                style: TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 10),
              if (ticketNo.isNotEmpty)
                Text(
                  ticketNo,
                  style: const TextStyle(
                    color: AppColors.textSecondary,
                    fontSize: 14,
                  ),
                ),
              const SizedBox(height: 12),
              Text(
                (message != null && message!.trim().isNotEmpty)
                    ? message!
                    : 'Your maintenance task has been successfully submitted. You will be notified when it is assigned.',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: AppColors.textSecondary,
                  fontSize: 14,
                  height: 1.6,
                ),
              ),
              if (attachmentCount > 0) ...[
                const SizedBox(height: 10),
                Text(
                  '$attachmentCount attachment${attachmentCount == 1 ? '' : 's'} uploaded.',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
              const SizedBox(height: 40),
              if (ticketId != null) ...[
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    onPressed: () => context.go('/tasks/$ticketId'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.statusCompleted,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14)),
                      elevation: 0,
                    ),
                    child: const Text('View Task Details',
                        style: TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.w600)),
                  ),
                ),
                const SizedBox(height: 12),
              ],
              SizedBox(
                width: double.infinity,
                height: 52,
                child: ElevatedButton(
                  onPressed: () => context.go('/tasks'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14)),
                    elevation: 0,
                  ),
                  child: const Text('View My Tasks',
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.w600)),
                ),
              ),
              const SizedBox(height: 14),
              TextButton(
                onPressed: () => context.go('/home'),
                child: const Text('Go to Home',
                    style: TextStyle(color: AppColors.primary)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
