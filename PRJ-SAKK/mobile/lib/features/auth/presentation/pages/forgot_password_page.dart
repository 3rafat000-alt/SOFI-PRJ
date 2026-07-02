import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../data/repositories/auth_repository.dart';
import '../widgets/auth_text_field.dart';

class ForgotPasswordPage extends ConsumerStatefulWidget {
  const ForgotPasswordPage({super.key});

  @override
  ConsumerState<ForgotPasswordPage> createState() => _ForgotPasswordPageState();
}

class _ForgotPasswordPageState extends ConsumerState<ForgotPasswordPage> {
  final _emailController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  bool _isSuccess = false;

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _sendResetLink() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      await ref.read(authRepositoryProvider).forgotPassword(
            _emailController.text.trim(),
          );
      if (mounted) setState(() => _isSuccess = true);
    } catch (e) {
      if (mounted) {
        final colors = context.appColors;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString()),
            backgroundColor: colors.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Scaffold(
      backgroundColor: colors.background,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: _isSuccess ? _buildSuccessState() : _buildForm(),
        ),
      ),
    );
  }

  Widget _buildSuccessState() {
    final colors = context.appColors;
    return Column(
      children: [
        const SizedBox(height: 80),
        Container(
          width: 80,
          height: 80,
          decoration: BoxDecoration(
            color: colors.successLight,
            borderRadius: BorderRadius.circular(20),
          ),
          child: Icon(Iconsax.tick_circle, color: colors.success, size: 40),
        ).animate().scale(
            begin: const Offset(0, 0), duration: 600.ms, curve: Curves.elasticOut),
        const SizedBox(height: 24),
        Text(
          'تم إرسال الرابط إلى بريدك الإلكتروني',
          textAlign: TextAlign.center,
          style: TextStyle(
              fontSize: 20, fontWeight: FontWeight.bold, color: colors.textPrimary),
        ).animate(delay: 400.ms).fadeIn().slideY(begin: 0.2),
        const SizedBox(height: 12),
        Text(
          'يرجى التحقق من بريدك الإلكتروني واتباع الرابط لإعادة تعيين كلمة المرور',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 14, color: colors.textSecondary, height: 1.5),
        ).animate(delay: 500.ms).fadeIn(),
        const SizedBox(height: 40),
        _gradientButton(
          label: 'العودة إلى تسجيل الدخول',
          onPressed: () => context.go('/login'),
        ).animate(delay: 600.ms).fadeIn().slideY(begin: 0.2),
      ],
    );
  }

  Widget _buildForm() {
    final colors = context.appColors;
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const SizedBox(height: 60),

          // Brand lock icon — gradient accent on white
          Center(
            child: Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: colors.cardGradientVisa,
                ),
                borderRadius: BorderRadius.circular(22),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.3),
                    blurRadius: 18,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: const Icon(Iconsax.lock_1, color: Colors.white, size: 36),
            ),
          ).animate().scale(
              begin: const Offset(0.6, 0.6),
              duration: 500.ms,
              curve: Curves.easeOutBack),

          const SizedBox(height: 32),

          Text(
            'نسيت كلمة المرور',
            textAlign: TextAlign.center,
            style: TextStyle(
                fontSize: 28, fontWeight: FontWeight.bold, color: colors.textPrimary),
          ).animate(delay: 200.ms).fadeIn().slideY(begin: 0.3),

          const SizedBox(height: 8),

          Text(
            'أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة التعيين',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: colors.textSecondary, height: 1.5),
          ).animate(delay: 300.ms).fadeIn(),

          const SizedBox(height: 40),

          AuthTextField(
            controller: _emailController,
            label: 'البريد الإلكتروني',
            hint: 'example@email.com',
            keyboardType: TextInputType.emailAddress,
            textDirection: TextDirection.ltr,
            prefixIcon: Iconsax.sms,
            validator: (value) {
              if (value == null || value.isEmpty) {
                return 'البريد الإلكتروني مطلوب';
              }
              if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                return 'البريد الإلكتروني غير صالح';
              }
              return null;
            },
          ).animate(delay: 400.ms).fadeIn().slideX(begin: -0.1),

          const SizedBox(height: 32),

          _gradientButton(
            label: 'إرسال رابط إعادة التعيين',
            loading: _isLoading,
            onPressed: _isLoading ? null : _sendResetLink,
          ).animate(delay: 500.ms).fadeIn().slideY(begin: 0.2),

          const SizedBox(height: 24),

          TextButton(
            onPressed: () => context.go('/login'),
            child: Text(
              'تذكرت كلمة المرور؟ تسجيل الدخول',
              style: TextStyle(color: colors.primary, fontWeight: FontWeight.w600),
            ),
          ).animate(delay: 600.ms).fadeIn(),
        ],
      ),
    );
  }

  Widget _gradientButton({
    required String label,
    required VoidCallback? onPressed,
    bool loading = false,
  }) {
    final colors = context.appColors;
    return SizedBox(
      width: double.infinity,
      height: 54,
      child: DecoratedBox(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: colors.cardGradientVisa,
          ),
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.3),
              blurRadius: 14,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: ElevatedButton(
          onPressed: loading ? null : onPressed,
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.transparent,
            shadowColor: Colors.transparent,
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
          child: loading
              ? const SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                )
              : Text(
                  label,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Colors.white),
                ),
        ),
      ),
    );
  }
}
