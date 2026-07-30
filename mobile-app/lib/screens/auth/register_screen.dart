import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/providers/auth_provider.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _obscure = true;
  bool _done = false;

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final success = await context.read<AuthProvider>().register(
          name: _nameCtrl.text.trim(),
          email: _emailCtrl.text.trim(),
          password: _passCtrl.text,
          phone: _phoneCtrl.text.trim().isEmpty ? null : _phoneCtrl.text.trim(),
        );
    if (!mounted) return;
    if (success) setState(() => _done = true);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Scaffold(
      backgroundColor: AppColors.background,
      body: _done ? _buildSuccess() : _buildForm(auth),
    );
  }

  Widget _buildSuccess() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: const BoxDecoration(
                color: AppColors.success,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check, color: Colors.white, size: 44),
            ),
            const SizedBox(height: 24),
            const Text('Request Submitted!',
                style: TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 22,
                    fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            const Text(
              'Your account request has been submitted and is pending approval from the Operations Manager.',
              textAlign: TextAlign.center,
              style: TextStyle(
                  color: AppColors.textSecondary, fontSize: 14, height: 1.6),
            ),
            const SizedBox(height: 36),
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                onPressed: () => context.go('/login'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14)),
                  elevation: 0,
                ),
                child: const Text('Back to Login',
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w600)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildForm(AuthProvider auth) {
    return Column(
      children: [
        Container(
          width: double.infinity,
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [AppColors.primaryDark, AppColors.primary],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.vertical(bottom: Radius.circular(32)),
          ),
          child: SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(24, 16, 24, 36),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  GestureDetector(
                    onTap: () => context.go('/login'),
                    child: const Icon(Icons.arrow_back_ios,
                        color: Colors.white, size: 20),
                  ),
                  const SizedBox(height: 20),
                  const Text('Request Access',
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 26,
                          fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  const Text('Fill in your details to request access',
                      style:
                          TextStyle(color: Colors.white70, fontSize: 13)),
                ],
              ),
            ),
          ),
        ),
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                children: [
                  const SizedBox(height: 8),
                  if (auth.error != null)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: AppColors.error.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(auth.error!,
                          style: const TextStyle(
                              color: AppColors.error, fontSize: 13)),
                    ),
                  _field(_nameCtrl, 'Full Name', Icons.person_outline,
                      validator: (v) =>
                          v!.isEmpty ? 'Enter your name' : null),
                  const SizedBox(height: 14),
                  _field(_emailCtrl, 'Email Address', Icons.email_outlined,
                      type: TextInputType.emailAddress,
                      validator: (v) =>
                          !v!.contains('@') ? 'Invalid email' : null),
                  const SizedBox(height: 14),
                  _field(
                    _phoneCtrl,
                    'Phone Number (Optional)',
                    Icons.phone_outlined,
                    type: TextInputType.phone,
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    controller: _passCtrl,
                    obscureText: _obscure,
                    validator: (v) =>
                        v!.length < 8 ? 'At least 8 characters' : null,
                    decoration: _decor('Password', Icons.lock_outline,
                        suffix: IconButton(
                          icon: Icon(
                            _obscure
                                ? Icons.visibility_outlined
                                : Icons.visibility_off_outlined,
                            color: AppColors.textSecondary,
                          ),
                          onPressed: () =>
                              setState(() => _obscure = !_obscure),
                        )),
                  ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: ElevatedButton(
                      onPressed: auth.loading ? null : _submit,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14)),
                        elevation: 0,
                      ),
                      child: auth.loading
                          ? const CircularProgressIndicator(
                              color: Colors.white, strokeWidth: 2)
                          : const Text('Submit Request',
                              style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600)),
                    ),
                  ),
                  const SizedBox(height: 20),
                  TextButton(
                    onPressed: () {
                      context.read<AuthProvider>().clearError();
                      context.go('/login');
                    },
                    child: const Text('Already have an account? Sign In',
                        style: TextStyle(color: AppColors.primary)),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _field(
    TextEditingController ctrl,
    String label,
    IconData icon, {
    TextInputType? type,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: ctrl,
      keyboardType: type,
      validator: validator,
      decoration: _decor(label, icon),
    );
  }

  InputDecoration _decor(String label, IconData icon, {Widget? suffix}) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, color: AppColors.primary, size: 20),
      suffixIcon: suffix,
      filled: true,
      fillColor: Colors.white,
      border:
          OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: AppColors.divider.withOpacity(0.8)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
      ),
      labelStyle: const TextStyle(color: AppColors.textSecondary, fontSize: 14),
    );
  }
}
