import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../core/providers/auth_provider.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;
  late Animation<double> _fadeAnim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 1200));
    _fadeAnim = CurvedAnimation(parent: _ctrl, curve: Curves.easeIn);
    _ctrl.forward();

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await context.read<AuthProvider>().init();
      if (!mounted) return;
      await Future.delayed(const Duration(milliseconds: 1800));
      if (!mounted) return;
      final auth = context.read<AuthProvider>();
      if (auth.status == AuthStatus.authenticated) {
        context.go('/home');
      } else {
        context.go('/onboarding');
      }
    });
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // Background image
          Container(
            decoration: const BoxDecoration(
              image: DecorationImage(
                image: AssetImage('assets/images/splash_screen.png'),
                fit: BoxFit.cover,
              ),
            ),
          ),
          // Overlay
          Container(
            color: Colors.black.withOpacity(0.3),
          ),
          FadeTransition(
            opacity: _fadeAnim,
            child: Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Logo
                  Container(
                    width: 150,
                    height: 150,
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(28),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.3),
                          blurRadius: 24,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(24),
                      child: Image.asset(
                        'assets/images/kai white.png',
                        fit: BoxFit.contain,
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  const Text(
                    'KAI PROPERTIES',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 2,
                    ),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'GHANA LIMITED',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 13,
                      letterSpacing: 3,
                    ),
                  ),
                  const SizedBox(height: 60),
                  const CircularProgressIndicator(
                    color: Colors.white54,
                    strokeWidth: 2,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
