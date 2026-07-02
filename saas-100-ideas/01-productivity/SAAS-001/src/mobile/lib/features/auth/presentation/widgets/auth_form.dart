import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../bloc/auth_bloc.dart';

class AuthForm extends StatefulWidget {
  final bool isLogin;
  final VoidCallback? onToggleMode;

  const AuthForm({
    super.key,
    this.isLogin = true,
    this.onToggleMode,
  });

  @override
  State<AuthForm> createState() => _AuthFormState();
}

class _AuthFormState extends State<AuthForm> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  final _workspaceController = TextEditingController();
  bool _obscurePassword = true;
  bool _obscureConfirm = true;
  bool _isLoading = false;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _workspaceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final isArabic = localizations.isArabic;

    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        setState(() => _isLoading = state is AuthLoading);
        if (state is AuthError) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: AppColors.error,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      },
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Title
            Text(
              widget.isLogin ? localizations.loginTitle : localizations.registerTitle,
              style: Theme.of(context).textTheme.headlineLarge,
              textAlign: isArabic ? TextAlign.right : TextAlign.left,
            ),
            const SizedBox(height: AppDimensions.spacing8),
            Text(
              widget.isLogin ? '' : localizations.signUpFree,
              style: Theme.of(context).textTheme.bodyMedium,
              textAlign: isArabic ? TextAlign.right : TextAlign.left,
            ),
            const SizedBox(height: AppDimensions.spacing32),

            // Name field (register only)
            if (!widget.isLogin) ...[
              TextFormField(
                controller: _nameController,
                decoration: InputDecoration(
                  labelText: localizations.name,
                  prefixIcon: const Icon(Icons.person_outline),
                ),
                textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
                validator: (value) {
                  if (!widget.isLogin && (value == null || value.trim().isEmpty)) {
                    return isArabic ? 'الاسم مطلوب' : 'Name is required';
                  }
                  return null;
                },
              ),
              const SizedBox(height: AppDimensions.spacing16),
            ],

            // Workspace name (register only)
            if (!widget.isLogin) ...[
              TextFormField(
                controller: _workspaceController,
                decoration: InputDecoration(
                  labelText: localizations.workspaceName,
                  prefixIcon: const Icon(Icons.workspaces_outline),
                ),
                textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
                validator: (value) {
                  if (!widget.isLogin && (value == null || value.trim().isEmpty)) {
                    return isArabic ? 'اسم مساحة العمل مطلوب' : 'Workspace name is required';
                  }
                  return null;
                },
              ),
              const SizedBox(height: AppDimensions.spacing16),
            ],

            // Email
            TextFormField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: InputDecoration(
                labelText: localizations.email,
                prefixIcon: const Icon(Icons.email_outlined),
              ),
              textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return isArabic ? 'البريد الإلكتروني مطلوب' : 'Email is required';
                }
                if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value.trim())) {
                  return isArabic ? 'بريد إلكتروني غير صالح' : 'Invalid email';
                }
                return null;
              },
            ),
            const SizedBox(height: AppDimensions.spacing16),

            // Password
            TextFormField(
              controller: _passwordController,
              obscureText: _obscurePassword,
              decoration: InputDecoration(
                labelText: localizations.password,
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  icon: Icon(
                    _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                  ),
                  onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                ),
              ),
              textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return isArabic ? 'كلمة المرور مطلوبة' : 'Password is required';
                }
                if (value.length < 8) {
                  return isArabic ? 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' : 'Password must be at least 8 characters';
                }
                return null;
              },
            ),
            const SizedBox(height: AppDimensions.spacing16),

            // Confirm password (register only)
            if (!widget.isLogin) ...[
              TextFormField(
                controller: _confirmPasswordController,
                obscureText: _obscureConfirm,
                decoration: InputDecoration(
                  labelText: localizations.confirmPassword,
                  prefixIcon: const Icon(Icons.lock_outline),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscureConfirm ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                    ),
                    onPressed: () => setState(() => _obscureConfirm = !_obscureConfirm),
                  ),
                ),
                textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
                validator: (value) {
                  if (value != _passwordController.text) {
                    return isArabic ? 'كلمة المرور غير متطابقة' : 'Passwords do not match';
                  }
                  return null;
                },
              ),
              const SizedBox(height: AppDimensions.spacing16),
            ],

            // Forgot password link (login only)
            if (widget.isLogin) ...[
              Align(
                alignment: isArabic ? Alignment.centerLeft : Alignment.centerRight,
                child: TextButton(
                  onPressed: () {
                    // TODO: Navigate to forgot password
                  },
                  child: Text(
                    localizations.forgotPassword,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: AppColors.primary,
                        ),
                  ),
                ),
              ),
              const SizedBox(height: AppDimensions.spacing8),
            ],

            // Submit button
            SizedBox(
              height: AppDimensions.buttonHeightLarge,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _submit,
                child: _isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : Text(
                        widget.isLogin ? localizations.login : localizations.register,
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                      ),
              ),
            ),
            const SizedBox(height: AppDimensions.spacing24),

            // Toggle mode
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  widget.isLogin ? localizations.noAccount : localizations.haveAccount,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                TextButton(
                  onPressed: widget.onToggleMode,
                  child: Text(
                    widget.isLogin ? localizations.register : localizations.login,
                    style: Theme.of(context).textTheme.labelLarge?.copyWith(
                          color: AppColors.primary,
                        ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;

    final bloc = context.read<AuthBloc>();
    if (widget.isLogin) {
      bloc.add(LoginEvent(
        email: _emailController.text.trim(),
        password: _passwordController.text,
      ));
    } else {
      bloc.add(RegisterEvent(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        password: _passwordController.text,
        passwordConfirmation: _confirmPasswordController.text,
        workspaceName: _workspaceController.text.trim(),
      ));
    }
  }
}
