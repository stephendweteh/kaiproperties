import 'package:go_router/go_router.dart';
import '../providers/auth_provider.dart';
import '../../screens/splash/splash_screen.dart';
import '../../screens/splash/onboarding_screen.dart';
import '../../screens/auth/login_screen.dart';
import '../../screens/auth/register_screen.dart';
import '../../screens/home/home_screen.dart';
import '../../screens/dashboard/dashboard_screen.dart';
import '../../screens/profile/profile_screen.dart';
import '../../screens/tasks/my_task_screen.dart';
import '../../screens/tasks/task_detail_screen.dart';
import '../../screens/tasks/task_create_screen.dart';
import '../../screens/tasks/task_success_screen.dart';
import '../../screens/cost_analysis/cost_analysis_screen.dart';

GoRouter createAppRouter(AuthProvider auth) {
  return GoRouter(
    initialLocation: '/',
    refreshListenable: auth,
    redirect: (_, state) {
      final user = auth.user;
      final authenticated = auth.status == AuthStatus.authenticated;
      final loggingIn =
          state.matchedLocation == '/login' ||
          state.matchedLocation == '/register' ||
          state.matchedLocation == '/onboarding' ||
          state.matchedLocation == '/';

      if (auth.status == AuthStatus.unknown) return null;
      if (!authenticated && !loggingIn) return '/login';
      if (authenticated && loggingIn) return '/home';
      if (authenticated && state.matchedLocation == '/tasks/create') {
        if (!(user?.canCreateTickets ?? false)) {
          return '/tasks';
        }
      }
      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (_, _) => const SplashScreen()),
      GoRoute(path: '/onboarding', builder: (_, _) => const OnboardingScreen()),
      GoRoute(path: '/login', builder: (_, _) => const LoginScreen()),
      GoRoute(path: '/register', builder: (_, _) => const RegisterScreen()),
      GoRoute(path: '/home', builder: (_, _) => const HomeScreen()),
      GoRoute(path: '/dashboard', builder: (_, _) => const DashboardScreen()),
      GoRoute(path: '/profile', builder: (_, _) => const ProfileScreen()),
      GoRoute(path: '/cost-analysis', builder: (_, _) => const CostAnalysisScreen()),
      GoRoute(
        path: '/tasks',
        builder: (_, state) => MyTaskScreen.withStatus(
          initialStatus: state.uri.queryParameters['status'],
        ),
      ),
      GoRoute(path: '/tasks/create', builder: (_, _) => const TaskCreateScreen()),
      GoRoute(
        path: '/tasks/:id/edit',
        builder: (_, state) =>
            TaskCreateScreen(ticketId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/tasks/:id',
        builder: (_, state) =>
            TaskDetailScreen(ticketId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/task-success',
        builder: (_, state) {
          final extra = state.extra;
          if (extra is Map) {
            int? ticketId;
            final rawTicketId = extra['ticketId'];
            if (rawTicketId is int) {
              ticketId = rawTicketId;
            } else if (rawTicketId is String) {
              ticketId = int.tryParse(rawTicketId);
            }

            int attachmentCount = 0;
            final rawAttachmentCount = extra['attachmentCount'];
            if (rawAttachmentCount is int) {
              attachmentCount = rawAttachmentCount;
            } else if (rawAttachmentCount is String) {
              attachmentCount = int.tryParse(rawAttachmentCount) ?? 0;
            }

            return TaskSuccessScreen(
              ticketNo: (extra['ticketNo'] as String?) ?? '',
              message: extra['message'] as String?,
              ticketId: ticketId,
              attachmentCount: attachmentCount,
            );
          }

          return TaskSuccessScreen(ticketNo: extra as String? ?? '');
        },
      ),
    ],
  );
}
