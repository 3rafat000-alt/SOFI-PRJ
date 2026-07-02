import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../data/repositories/auth_repository.dart';
import '../../../pin/data/pin_service.dart';
import '../widgets/auth_text_field.dart';

class RegisterPage extends ConsumerStatefulWidget {
  /// Pre-filled referral code, supplied by an invite deep link (?ref=CODE).
  final String? referralCode;

  const RegisterPage({super.key, this.referralCode});

  @override
  ConsumerState<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends ConsumerState<RegisterPage> {
  int _currentStep = 1;
  bool _isLoading = false;

  // Step 1: Login Credentials
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  // Step 2: Personal Information
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _firstNameFocus = FocusNode();
  final _occupationController = TextEditingController();
  final _referralController = TextEditingController();
  DateTime? _selectedDate;
  String? _selectedGender;
  bool _acceptTerms = false;

  @override
  void initState() {
    super.initState();
    // Carry an invite deep link's code into the (optional) referral field.
    if (widget.referralCode != null && widget.referralCode!.trim().isNotEmpty) {
      _referralController.text = widget.referralCode!.trim().toUpperCase();
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _firstNameController.dispose();
    _lastNameController.dispose();
    _firstNameFocus.dispose();
    _occupationController.dispose();
    _referralController.dispose();
    super.dispose();
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime(2000, 1, 1),
      firstDate: DateTime(1950, 1, 1),
      lastDate: DateTime.now(),
      builder: (context, child) {
        final colors = context.appColors;
        final isDark = Theme.of(context).brightness == Brightness.dark;
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: Theme.of(context).colorScheme.copyWith(
              primary: colors.primary,
              onPrimary: isDark ? colors.background : Colors.white,
              surface: colors.surface,
              onSurface: colors.textPrimary,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() => _selectedDate = picked);
    }
  }

  bool _validateStep1() {
    if (_emailController.text.trim().isEmpty) {
      _showError('البريد الإلكتروني مطلوب');
      return false;
    }
    if (!RegExp(r'^[^@]+@[^@]+\.[^@]+').hasMatch(_emailController.text.trim())) {
      _showError('البريد الإلكتروني غير صالح');
      return false;
    }
    if (_phoneController.text.trim().isEmpty) {
      _showError('رقم الهاتف مطلوب');
      return false;
    }
    if (_passwordController.text.length < 8) {
      _showError('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
      return false;
    }
    if (_passwordController.text != _confirmPasswordController.text) {
      _showError('كلمات المرور غير متطابقة');
      return false;
    }
    return true;
  }

  bool _validateStep2() {
    if (_firstNameController.text.trim().isEmpty) {
      _showError('الاسم الأول مطلوب');
      return false;
    }
    if (_lastNameController.text.trim().isEmpty) {
      _showError('الاسم الأخير (القب) مطلوب');
      return false;
    }
    if (_selectedDate == null) {
      _showError('تاريخ الميلاد مطلوب');
      return false;
    }
    if (_selectedGender == null) {
      _showError('الجنس مطلوب');
      return false;
    }
    if (_occupationController.text.trim().isEmpty) {
      _showError('الوظيفة مطلوبة');
      return false;
    }
    if (!_acceptTerms) {
      _showError('يجب الموافقة على الشروط والأحكام');
      return false;
    }
    return true;
  }

  void _showError(String message) {
    final colors = context.appColors;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: colors.error,
      ),
    );
  }

  void _nextStep() {
    if (_currentStep == 1 && _validateStep1()) {
      setState(() => _currentStep = 2);
      // Auto-focus the name field once step 2 has rendered.
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _firstNameFocus.requestFocus();
      });
    }
  }

  void _previousStep() {
    setState(() => _currentStep = 1);
  }

  Future<void> _register() async {
    if (!_validateStep2()) return;

    setState(() => _isLoading = true);

    try {
      final user = await ref.read(authRepositoryProvider).register(
        firstName: _firstNameController.text.trim(),
        lastName: _lastNameController.text.trim(),
        email: _emailController.text.trim(),
        phone: _phoneController.text.trim(),
        password: _passwordController.text,
        passwordConfirmation: _confirmPasswordController.text,
        dateOfBirth: _selectedDate?.toIso8601String(),
        gender: _selectedGender,
        occupation: _occupationController.text.trim(),
        referralCode: _referralController.text.trim().isEmpty
            ? null
            : _referralController.text.trim(),
      );

      ref.read(currentUserProvider.notifier).state = user;

      if (mounted) {
        final pinSet = await ref.read(pinServiceProvider).isPinSet();
        if (mounted) context.go(pinSet ? '/dashboard' : '/pin-setup');
      }
    } catch (e) {
      if (mounted) {
        _showError(e.toString());
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return PopScope(
      canPop: _currentStep == 1,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && _currentStep == 2) {
          _previousStep();
        }
      },
      child: Scaffold(
        backgroundColor: colors.background,
        body: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: _currentStep == 1 ? _buildStep1() : _buildStep2(),
          ),
        ),
      ),
    );
  }

  // Brand logo — صكّ mark (real app icon)
  Widget _brandIcon() {
    return Center(
      child: Container(
        width: 80,
        height: 80,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.3),
              blurRadius: 20,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(24),
          child: Image.asset(
            'assets/images/logo.png',
            width: 80,
            height: 80,
            fit: BoxFit.cover,
          ),
        ),
      ),
    ).animate().scale(begin: const Offset(0.8, 0.8)).fadeIn(duration: 500.ms);
  }

  // Primary gradient button (full-width, 54h)
  Widget _primaryButton({
    required VoidCallback? onPressed,
    required Widget child,
  }) {
    final colors = context.appColors;
    return SizedBox(
      height: 54,
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          gradient: LinearGradient(
            colors: colors.cardGradientVisa,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.3),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: ElevatedButton(
          onPressed: onPressed,
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.transparent,
            shadowColor: Colors.transparent,
            disabledBackgroundColor: Colors.transparent,
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          child: child,
        ),
      ),
    );
  }

  Widget _buildStep1() {
    final colors = context.appColors;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const SizedBox(height: 24),

        _brandIcon(),

        const SizedBox(height: 32),

        Text(
          'بيانات الدخول',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 28,
            fontWeight: FontWeight.bold,
            color: colors.textPrimary,
          ),
        ).animate().fadeIn().slideY(begin: 0.3),

        const SizedBox(height: 8),

        Text(
          'أدخل بيانات الدخول الخاصة بك',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 14,
            color: colors.textSecondary,
          ),
        ).animate(delay: 100.ms).fadeIn(),

        const SizedBox(height: 32),

        AuthTextField(
          controller: _emailController,
          label: 'البريد الإلكتروني',
          hint: 'example@email.com',
          keyboardType: TextInputType.emailAddress,
          textDirection: TextDirection.ltr,
          prefixIcon: Iconsax.sms,
        ).animate(delay: 200.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 16),

        AuthTextField(
          controller: _phoneController,
          label: 'رقم الهاتف',
          hint: '09XXXXXXXX',
          keyboardType: TextInputType.phone,
          textDirection: TextDirection.ltr,
          prefixIcon: Iconsax.call,
          inputFormatters: [
            FilteringTextInputFormatter.digitsOnly,
          ],
        ).animate(delay: 300.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 16),

        AuthTextField(
          controller: _passwordController,
          label: 'كلمة المرور',
          hint: '••••••••',
          obscureText: _obscurePassword,
          textDirection: TextDirection.ltr,
          prefixIcon: Iconsax.lock,
          suffixIcon: IconButton(
            icon: Icon(
              _obscurePassword ? Iconsax.eye_slash : Iconsax.eye,
              color: colors.textHint,
            ),
            onPressed: () {
              setState(() => _obscurePassword = !_obscurePassword);
            },
          ),
        ).animate(delay: 400.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 16),

        AuthTextField(
          controller: _confirmPasswordController,
          label: 'تأكيد كلمة المرور',
          hint: '••••••••',
          obscureText: _obscureConfirmPassword,
          textDirection: TextDirection.ltr,
          prefixIcon: Iconsax.lock,
          suffixIcon: IconButton(
            icon: Icon(
              _obscureConfirmPassword ? Iconsax.eye_slash : Iconsax.eye,
              color: colors.textHint,
            ),
            onPressed: () {
              setState(() => _obscureConfirmPassword = !_obscureConfirmPassword);
            },
          ),
        ).animate(delay: 500.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 16),

        // Referral code — optional, auto-filled when arriving from an invite link.
        AuthTextField(
          controller: _referralController,
          label: 'كود الإحالة (اختياري)',
          hint: 'إن دعاك صديق',
          textDirection: TextDirection.ltr,
          prefixIcon: Iconsax.ticket_star,
          inputFormatters: [
            UpperCaseTextFormatter(),
          ],
        ).animate(delay: 550.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 32),

        _primaryButton(
          onPressed: _nextStep,
          child: const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                'التالي',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.white,
                ),
              ),
              SizedBox(width: 8),
              Icon(Iconsax.arrow_left_2, color: Colors.white),
            ],
          ),
        ).animate(delay: 600.ms).fadeIn().slideY(begin: 0.2),

        const SizedBox(height: 24),

        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              'لديك حساب بالفعل؟',
              style: TextStyle(color: colors.textSecondary),
            ),
            TextButton(
              onPressed: () => context.go('/login'),
              child: Text(
                'تسجيل الدخول',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: colors.primary,
                ),
              ),
            ),
          ],
        ).animate(delay: 700.ms).fadeIn(),
      ],
    );
  }

  Widget _buildStep2() {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const SizedBox(height: 40),
        Text(
          'البيانات الشخصية',
          style: TextStyle(
            fontSize: 28,
            fontWeight: FontWeight.bold,
            color: colors.textPrimary,
          ),
        ).animate().fadeIn().slideY(begin: 0.3),

        const SizedBox(height: 8),

        Text(
          'أكمل بياناتك الشخصية',
          style: TextStyle(
            fontSize: 14,
            color: colors.textSecondary,
          ),
        ).animate(delay: 100.ms).fadeIn(),

        const SizedBox(height: 32),

        // Name Row
        Row(
          children: [
            Expanded(
              child: AuthTextField(
                controller: _firstNameController,
                focusNode: _firstNameFocus,
                label: 'الاسم',
                hint: 'أحمد',
                prefixIcon: Iconsax.user,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: AuthTextField(
                controller: _lastNameController,
                label: 'القب (العائلة)',
                hint: 'الفلاني',
                prefixIcon: Iconsax.user,
              ),
            ),
          ],
        ).animate(delay: 200.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 16),

        // Date of Birth
        InkWell(
          onTap: _selectDate,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: BoxDecoration(
              color: colors.inputBackground,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: colors.inputBackground),
            ),
            child: Row(
              children: [
                Icon(Iconsax.calendar, color: colors.textHint),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    _selectedDate != null
                        ? '${_selectedDate!.day}/${_selectedDate!.month}/${_selectedDate!.year}'
                        : 'تاريخ الميلاد',
                    style: TextStyle(
                      color: _selectedDate != null
                          ? colors.textPrimary
                          : colors.textHint,
                      fontSize: 14,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ).animate(delay: 300.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 16),

        // Gender
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
          decoration: BoxDecoration(
            color: colors.inputBackground,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: colors.inputBackground),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: _selectedGender,
              hint: Row(
                children: [
                  Icon(Iconsax.user, color: colors.textHint),
                  const SizedBox(width: 12),
                  Text(
                    'الجنس',
                    style: TextStyle(color: colors.textHint, fontSize: 14),
                  ),
                ],
              ),
              isExpanded: true,
              icon: Icon(Iconsax.arrow_down_1, color: colors.textHint),
              items: const [
                DropdownMenuItem(
                  value: 'male',
                  child: Text('ذكر'),
                ),
                DropdownMenuItem(
                  value: 'female',
                  child: Text('أنثى'),
                ),
              ],
              onChanged: (value) {
                setState(() => _selectedGender = value);
              },
            ),
          ),
        ).animate(delay: 400.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 16),

        // Occupation
        AuthTextField(
          controller: _occupationController,
          label: 'الوظيفة',
          hint: 'مهندس، طبيب، ...',
          prefixIcon: Iconsax.briefcase,
        ).animate(delay: 500.ms).fadeIn().slideX(begin: -0.1),

        const SizedBox(height: 24),

        // Terms Checkbox
        Row(
          children: [
            Checkbox(
              value: _acceptTerms,
              onChanged: (value) {
                setState(() => _acceptTerms = value ?? false);
              },
              activeColor: isDark ? colors.surface : colors.primary,
              checkColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            Expanded(
              child: GestureDetector(
                onTap: () {
                  setState(() => _acceptTerms = !_acceptTerms);
                },
                child: RichText(
                  text: TextSpan(
                    style: TextStyle(
                      fontSize: 13,
                      color: colors.textSecondary,
                    ),
                    children: [
                      const TextSpan(text: 'أوافق على '),
                      TextSpan(
                        text: 'الشروط والأحكام',
                        style: TextStyle(
                          color: colors.primary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const TextSpan(text: ' و'),
                      TextSpan(
                        text: 'سياسة الخصوصية',
                        style: TextStyle(
                          color: colors.primary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ).animate(delay: 600.ms).fadeIn(),

        const SizedBox(height: 24),

        // Register Button
        _primaryButton(
          onPressed: _isLoading ? null : _register,
          child: _isLoading
              ? const SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(
                    color: Colors.white,
                    strokeWidth: 2,
                  ),
                )
              : const Text(
                  'إنشاء حساب',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
        ).animate(delay: 700.ms).fadeIn().slideY(begin: 0.2),

        const SizedBox(height: 16),

        // Back Button
        TextButton(
          onPressed: _previousStep,
          child: Text(
            'العودة للخطوة السابقة',
            style: TextStyle(color: colors.primary),
          ),
        ).animate(delay: 800.ms).fadeIn(),
      ],
    );
  }
}

/// Forces the referral-code field to uppercase as the user types (codes are
/// case-insensitive server-side, but uppercase reads cleanly).
class UpperCaseTextFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    return TextEditingValue(
      text: newValue.text.toUpperCase(),
      selection: newValue.selection,
    );
  }
}
