import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/constants/app_colors.dart';

class _Page {
  final String title;
  final String subtitle;
  final IconData icon;
  const _Page({required this.title, required this.subtitle, required this.icon});
}

const _pages = [
  _Page(
    title: 'Track Maintenance',
    subtitle:
        'Log, assign and monitor maintenance tasks across all your properties in real-time.',
    icon: Icons.build_circle_outlined,
  ),
  _Page(
    title: 'Stay Up-to-Date',
    subtitle:
        'Get instant updates on task progress, approvals and technician activities.',
    icon: Icons.notifications_active_outlined,
  ),
  _Page(
    title: 'Work Smarter',
    subtitle:
        'Making property management seamless and efficient — from anywhere.',
    icon: Icons.speed_outlined,
  ),
];

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final _controller = PageController();
  int _page = 0;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _next() {
    if (_page < _pages.length - 1) {
      _controller.nextPage(
          duration: const Duration(milliseconds: 350), curve: Curves.easeOut);
    } else {
      context.go('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Stack(
        children: [
          // Building wireframe background pattern
          Opacity(
            opacity: 0.04,
            child: CustomPaint(
              painter: _GridPainter(),
              child: const SizedBox.expand(),
            ),
          ),
          Column(
            children: [
              const SizedBox(height: 60),
              // Logo row
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Row(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      padding: const EdgeInsets.all(2),
                      decoration: BoxDecoration(
                        color: Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.asset(
                          'assets/images/kai logo-app.png',
                          fit: BoxFit.contain,
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('KAI PROPERTIES',
                            style: TextStyle(
                                color: AppColors.primary,
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                                letterSpacing: 1)),
                        Text('GHANA LIMITED',
                            style: TextStyle(
                                color: AppColors.textSecondary,
                                fontSize: 10,
                                letterSpacing: 1.5)),
                      ],
                    ),
                  ],
                ),
              ),
              Expanded(
                child: PageView.builder(
                  controller: _controller,
                  itemCount: _pages.length,
                  onPageChanged: (i) => setState(() => _page = i),
                  itemBuilder: (_, i) => _buildPage(_pages[i]),
                ),
              ),
              // Dots
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(
                  _pages.length,
                  (i) => AnimatedContainer(
                    duration: const Duration(milliseconds: 300),
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    width: _page == i ? 24 : 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: _page == i ? AppColors.primary : AppColors.divider,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 32),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    onPressed: _next,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14)),
                      elevation: 0,
                    ),
                    child: Text(
                      _page == _pages.length - 1 ? 'Get Started' : 'Next',
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.w600),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              TextButton(
                onPressed: () => context.go('/login'),
                child: const Text('Already have an account? Sign in',
                    style: TextStyle(color: AppColors.textSecondary)),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPage(_Page p) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 120,
            height: 120,
            decoration: BoxDecoration(
              color: AppColors.primary.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(p.icon, color: AppColors.primary, size: 56),
          ),
          const SizedBox(height: 36),
          Text(
            p.title,
            textAlign: TextAlign.center,
            style: const TextStyle(
                color: AppColors.textPrimary,
                fontSize: 26,
                fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          Text(
            p.subtitle,
            textAlign: TextAlign.center,
            style: const TextStyle(
                color: AppColors.textSecondary, fontSize: 15, height: 1.6),
          ),
        ],
      ),
    );
  }
}

class _GridPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = AppColors.primary
      ..strokeWidth = 1;
    const spacing = 40.0;
    for (double x = 0; x < size.width; x += spacing) {
      canvas.drawLine(Offset(x, 0), Offset(x, size.height), paint);
    }
    for (double y = 0; y < size.height; y += spacing) {
      canvas.drawLine(Offset(0, y), Offset(size.width, y), paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter old) => false;
}
