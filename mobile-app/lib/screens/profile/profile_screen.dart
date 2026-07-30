import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/constants/app_colors.dart';
import '../../core/providers/auth_provider.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _profileFormKey = GlobalKey<FormState>();
  late final TextEditingController _nameCtrl;
  late final TextEditingController _emailCtrl;
  late final TextEditingController _phoneCtrl;
  final _currentPasswordCtrl = TextEditingController();
  final _newPasswordCtrl = TextEditingController();
  final _confirmPasswordCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    final user = context.read<AuthProvider>().user;
    _nameCtrl = TextEditingController(text: user?.name ?? '');
    _emailCtrl = TextEditingController(text: user?.email ?? '');
    _phoneCtrl = TextEditingController(text: user?.phone ?? '');
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _currentPasswordCtrl.dispose();
    _newPasswordCtrl.dispose();
    _confirmPasswordCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickAndUploadPhoto() async {
    final auth = context.read<AuthProvider>();
    final picker = ImagePicker();
    final file = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1600,
    );

    if (file == null || !mounted) {
      return;
    }

    final success = await auth.uploadProfilePhoto(file.path);
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          success
              ? 'Profile photo updated successfully.'
              : (auth.error ?? 'Unable to update profile photo.'),
        ),
        backgroundColor: success ? AppColors.success : AppColors.error,
      ),
    );
  }

  Future<void> _saveProfile() async {
    if (!_profileFormKey.currentState!.validate()) {
      return;
    }

    final auth = context.read<AuthProvider>();
    final success = await auth.updateProfile(
      name: _nameCtrl.text.trim(),
      email: _emailCtrl.text.trim(),
      phone: _phoneCtrl.text.trim(),
    );

    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          success
              ? 'Profile updated successfully.'
              : (auth.error ?? 'Unable to update profile.'),
        ),
        backgroundColor: success ? AppColors.success : AppColors.error,
      ),
    );
  }

  Future<void> _savePassword() async {
    final current = _currentPasswordCtrl.text;
    final next = _newPasswordCtrl.text;
    final confirm = _confirmPasswordCtrl.text;

    if (current.isEmpty || next.isEmpty || confirm.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Fill all password fields.'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    if (next.length < 8) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('New password must be at least 8 characters.'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    if (next != confirm) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Password confirmation does not match.'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    final auth = context.read<AuthProvider>();
    final success = await auth.changePassword(
      currentPassword: current,
      newPassword: next,
    );

    if (!mounted) return;

    if (success) {
      _currentPasswordCtrl.clear();
      _newPasswordCtrl.clear();
      _confirmPasswordCtrl.clear();
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          success
              ? 'Password changed successfully.'
              : (auth.error ?? 'Unable to change password.'),
        ),
        backgroundColor: success ? AppColors.success : AppColors.error,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              child: Row(
                children: [
                  IconButton(
                    onPressed: () => context.go('/home'),
                    icon: const Icon(
                      Icons.arrow_back_ios,
                      color: AppColors.primary,
                      size: 20,
                    ),
                  ),
                  const Expanded(
                    child: Text(
                      'Profile Management',
                      style: TextStyle(
                        color: AppColors.textPrimary,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                children: [
                  _buildProfileHeader(user?.name),
                  const SizedBox(height: 16),
                  _buildProfileForm(auth.loading),
                  const SizedBox(height: 16),
                  _buildPasswordCard(auth.loading),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProfileHeader(String? name) {
    final user = context.watch<AuthProvider>().user;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(color: AppColors.shadow, blurRadius: 8),
        ],
      ),
      child: Row(
        children: [
          Stack(
            children: [
              CircleAvatar(
                radius: 34,
                backgroundColor: AppColors.primary,
                backgroundImage: user?.profilePhotoUrl != null
                    ? NetworkImage(user!.profilePhotoUrl!)
                    : null,
                child: user?.profilePhotoUrl == null
                    ? Text(
                        (name?.isNotEmpty == true
                                ? name![0]
                                : 'U')
                            .toUpperCase(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 24,
                        ),
                      )
                    : null,
              ),
              Positioned(
                right: -6,
                bottom: -6,
                child: IconButton(
                  onPressed: _pickAndUploadPhoto,
                  style: IconButton.styleFrom(
                    backgroundColor: AppColors.accent,
                    foregroundColor: Colors.white,
                  ),
                  icon: const Icon(Icons.camera_alt_outlined, size: 18),
                ),
              ),
            ],
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  user?.name ?? 'User',
                  style: const TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  user?.email ?? '-',
                  style: const TextStyle(
                    color: AppColors.textSecondary,
                    fontSize: 12,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  user?.displayRole ?? '-',
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileForm(bool loading) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(color: AppColors.shadow, blurRadius: 8),
        ],
      ),
      child: Form(
        key: _profileFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Profile Details',
              style: TextStyle(
                color: AppColors.textPrimary,
                fontSize: 15,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 14),
            _buildTextField(
              controller: _nameCtrl,
              label: 'Full Name',
              icon: Icons.person_outline,
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'Name is required' : null,
            ),
            const SizedBox(height: 12),
            _buildTextField(
              controller: _emailCtrl,
              label: 'Email Address',
              icon: Icons.email_outlined,
              keyboardType: TextInputType.emailAddress,
              validator: (v) => (v == null || !v.contains('@'))
                  ? 'Enter a valid email'
                  : null,
            ),
            const SizedBox(height: 12),
            _buildTextField(
              controller: _phoneCtrl,
              label: 'Phone Number',
              icon: Icons.phone_outlined,
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 46,
              child: ElevatedButton.icon(
                onPressed: loading ? null : _saveProfile,
                icon: const Icon(Icons.save_outlined, size: 18),
                label: const Text('Save Profile'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPasswordCard(bool loading) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(color: AppColors.shadow, blurRadius: 8),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Change Password',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontSize: 15,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 14),
          _buildTextField(
            controller: _currentPasswordCtrl,
            label: 'Current Password',
            icon: Icons.lock_outline,
            obscureText: true,
          ),
          const SizedBox(height: 12),
          _buildTextField(
            controller: _newPasswordCtrl,
            label: 'New Password',
            icon: Icons.lock_reset,
            obscureText: true,
          ),
          const SizedBox(height: 12),
          _buildTextField(
            controller: _confirmPasswordCtrl,
            label: 'Confirm New Password',
            icon: Icons.lock_person_outlined,
            obscureText: true,
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            height: 46,
            child: OutlinedButton.icon(
              onPressed: loading ? null : _savePassword,
              icon: const Icon(Icons.password_outlined, size: 18),
              label: const Text('Update Password'),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.primary,
                side: const BorderSide(color: AppColors.primary),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    bool obscureText = false,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: keyboardType,
      validator: validator,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: AppColors.primary, size: 20),
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: AppColors.divider.withOpacity(0.8)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
        labelStyle:
            const TextStyle(color: AppColors.textSecondary, fontSize: 14),
      ),
    );
  }
}
