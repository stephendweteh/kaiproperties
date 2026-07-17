import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'core/constants/app_colors.dart';
import 'core/providers/auth_provider.dart';
import 'core/providers/ticket_provider.dart';
import 'core/providers/dashboard_provider.dart';
import 'core/router/app_router.dart';
import 'core/services/api_service.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const KaiPropertiesApp());
}

class KaiPropertiesApp extends StatefulWidget {
  const KaiPropertiesApp({super.key});

  @override
  State<KaiPropertiesApp> createState() => _KaiPropertiesAppState();
}

class _KaiPropertiesAppState extends State<KaiPropertiesApp> {
  late final AuthProvider _authProvider;
  late final TicketProvider _ticketProvider;
  late final DashboardProvider _dashboardProvider;
  late final GoRouter _router;

  @override
  void initState() {
    super.initState();
    _authProvider = AuthProvider();
    _ticketProvider = TicketProvider();
    _dashboardProvider = DashboardProvider();
    _router = createAppRouter(_authProvider);

    // Wire the API service once with router/auth-aware unauthorized handling.
    ApiService.instance.init(
      onUnauthorized: () {
        _authProvider.handleUnauthorizedLocally();
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _router.go('/login');
        });
      },
    );
  }

  @override
  void dispose() {
    _authProvider.dispose();
    _ticketProvider.dispose();
    _dashboardProvider.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider<AuthProvider>.value(value: _authProvider),
        ChangeNotifierProvider<TicketProvider>.value(value: _ticketProvider),
        ChangeNotifierProvider<DashboardProvider>.value(value: _dashboardProvider),
      ],
      child: MaterialApp.router(
        title: 'KAI Properties',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          useMaterial3: true,
          colorScheme: ColorScheme.fromSeed(
            seedColor: AppColors.primary,
            primary: AppColors.primary,
            secondary: AppColors.accent,
            surface: AppColors.background,
          ),
          fontFamily: 'Roboto',
          scaffoldBackgroundColor: AppColors.background,
          appBarTheme: const AppBarTheme(
            backgroundColor: AppColors.primary,
            foregroundColor: Colors.white,
            elevation: 0,
          ),
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
          inputDecorationTheme: InputDecorationTheme(
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide.none,
            ),
          ),
        ),
        routerConfig: _router,
      ),
    );
  }
}
