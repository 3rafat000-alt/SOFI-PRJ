import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:share_plus/share_plus.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/services/permission_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../../shared/widgets/branded_qr.dart';
import '../../data/repositories/contacts_repository.dart';
import '../../data/repositories/payment_request_repository.dart';

enum _Phase { form, contacts, result }

/// Request money — one unified flow:
///   1. enter amount (+ note)
///   2. choose HOW: a shareable link/QR, OR pick a friend (they get a
///      notification to accept/reject).
class RequestMoneyPage extends ConsumerStatefulWidget {
  const RequestMoneyPage({super.key});

  @override
  ConsumerState<RequestMoneyPage> createState() => _RequestMoneyPageState();
}

class _RequestMoneyPageState extends ConsumerState<RequestMoneyPage> {
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();
  String _currency = 'USD';
  bool _isLoading = false;
  String? _amountError;
  Map<String, dynamic>? _created;
  _Phase _phase = _Phase.form;

  // Contacts (for "from a friend").
  bool _loadingContacts = false;
  String? _contactsError;
  bool _permissionDenied = false;
  final List<_Reg> _registered = [];
  String _query = '';
  String? _creatingAccount;

  @override
  void dispose() {
    _amountController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  double get _amount =>
      Money.parseAmount(_amountController.text, currency: _currency) ?? 0;

  void _snack(String msg, {bool success = false}) {
    final colors = context.appColors;
    final color = success ? colors.success : colors.error;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: color,
        margin: const EdgeInsets.all(AppSpacing.lg),
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.md)),
        content: Row(children: [
          Icon(success ? Iconsax.tick_circle : Iconsax.warning_2,
              color: Colors.white, size: 20),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
              child: Text(msg,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                      color: Colors.white, fontWeight: FontWeight.w600))),
        ]),
      ));
  }

  bool _validateAmount() {
    if (_amountController.text.trim().isEmpty) {
      setState(() => _amountError = 'الرجاء إدخال المبلغ المطلوب');
      return false;
    }
    if (_amount <= 0) {
      setState(() => _amountError = 'أدخل مبلغاً أكبر من صفر');
      return false;
    }
    setState(() => _amountError = null);
    return true;
  }

  Future<void> _createLink() async {
    if (!_validateAmount()) return;
    setState(() => _isLoading = true);
    try {
      final result = await ref.read(paymentRequestRepositoryProvider).create(
            amount: _amount,
            currency: _currency,
            note: _noteController.text,
          );
      if (mounted) {
        setState(() {
          _created = result;
          _phase = _Phase.result;
        });
      }
    } on ApiException catch (e) {
      if (mounted) _snack(e.message);
    } catch (_) {
      if (mounted) _snack('تعذّر إنشاء الطلب، حاول مجدداً');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _goContacts() {
    if (!_validateAmount()) return;
    setState(() => _phase = _Phase.contacts);
    _loadContacts();
  }

  /// Denied-state CTA: jump to OS settings on permanent denial, else re-request.
  Future<void> _retryContacts() async {
    if (await PermissionService.isContactsPermanentlyDenied()) {
      await PermissionService.openSettings();
      return;
    }
    await _loadContacts();
  }

  Future<void> _loadContacts() async {
    setState(() {
      _loadingContacts = true;
      _contactsError = null;
      _permissionDenied = false;
    });
    _registered.clear();
    try {
      final granted = await FlutterContacts.requestPermission(readonly: true);
      if (!granted) {
        setState(() {
          _permissionDenied = true;
          _loadingContacts = false;
        });
        return;
      }
      final contacts = await FlutterContacts.getContacts(withProperties: true);
      final phoneToName = <String, String>{};
      for (final c in contacts) {
        for (final p in c.phones) {
          final n = p.number.trim();
          if (n.isNotEmpty) phoneToName[n] = c.displayName;
        }
      }
      final repo = ref.read(contactsRepositoryProvider);
      final phones = phoneToName.keys.toList();
      final matches =
          phones.isEmpty ? <Map<String, dynamic>>[] : await repo.match(phones);
      for (final m in matches) {
        final phone = m['phone']?.toString() ?? '';
        _registered.add(_Reg(
          name: (m['name']?.toString().isNotEmpty ?? false)
              ? m['name'].toString()
              : (phoneToName[phone] ?? phone),
          account: m['account_number']?.toString() ?? '',
          initials: m['initials']?.toString() ?? '',
        ));
      }
      _registered.sort((a, b) => a.name.compareTo(b.name));
      setState(() => _loadingContacts = false);
    } catch (e) {
      setState(() {
        _contactsError =
            e is ApiException ? e.message : 'تعذّر تحميل جهات الاتصال';
        _loadingContacts = false;
      });
    }
  }

  Future<void> _requestFrom(_Reg c) async {
    setState(() => _creatingAccount = c.account);
    try {
      await ref.read(paymentRequestRepositoryProvider).requestFromUser(
            account: c.account,
            amount: _amount,
            currency: _currency,
            note: _noteController.text,
          );
      if (!mounted) return;
      _snack('تم إرسال طلب الدفعة إلى ${c.name}', success: true);
      if (context.canPop()) {
        context.pop();
      } else {
        context.go('/dashboard');
      }
    } on ApiException catch (e) {
      if (mounted) {
        setState(() => _creatingAccount = null);
        _snack(e.message);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _creatingAccount = null);
        _snack('تعذّر إرسال الطلب، حاول مجدداً');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final title = switch (_phase) {
      _Phase.form => 'طلب دفعة',
      _Phase.contacts => 'اختر صديقاً',
      _Phase.result => 'تم إنشاء الطلب',
    };

    return PopScope(
      canPop: _phase == _Phase.form,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) setState(() => _phase = _Phase.form);
      },
      child: AppScaffold(
        title: title,
        onBack: _phase == _Phase.form
            ? null
            : () => setState(() => _phase = _Phase.form),
        action: _phase == _Phase.form
            ? IconButton(
                tooltip: 'طلباتي',
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                icon: Icon(Iconsax.task_square,
                    size: 22, color: colors.primary),
                onPressed: () => context.push('/my-requests'),
              )
            : null,
        body: switch (_phase) {
          _Phase.form => _buildForm(),
          _Phase.contacts => _buildContacts(),
          _Phase.result => _buildResult(_created!),
        },
      ),
    );
  }

  // ════════════════════ Phase 1 — amount + method ════════════════════
  Widget _buildForm() {
    final colors = context.appColors;
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppSpacing.xl),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _currencyToggle(),
          const SizedBox(height: AppSpacing.lg),
          AppCard(
            color: colors.primaryLight,
            child: Column(
              children: [
                Text('المبلغ المطلوب',
                    style:
                        TextStyle(fontSize: 12.5, color: colors.textSecondary)),
                const SizedBox(height: AppSpacing.sm),
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: Text(Money.format(_amount, _currency),
                      style: TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.w800,
                          color: colors.primary)),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          TextField(
            controller: _amountController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            textDirection: TextDirection.ltr,
            inputFormatters: [
              FilteringTextInputFormatter.allow(
                  RegExp(Money.amountInputPattern(_currency)))
            ],
            onChanged: (_) => setState(() => _amountError = null),
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
            decoration: _input(
              label: 'أدخل المبلغ',
              hint: _currency == 'USD' ? '0.00' : '0',
              icon: _currency == 'USD' ? Iconsax.dollar_circle : Iconsax.money,
              suffix: _currency,
              error: _amountError,
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          TextField(
            controller: _noteController,
            maxLength: 140,
            decoration: _input(
              label: 'سبب الطلب (اختياري)',
              hint: 'مثال: مقابل الكتاب',
              icon: Iconsax.note_1,
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text('كيف تريد أن تطلب الدفعة؟',
                style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
          ),
          const SizedBox(height: AppSpacing.md),
          _methodCard(
            icon: Iconsax.scan_barcode,
            title: 'رابط ورمز QR',
            subtitle: 'يدفعه أي شخص فوراً عبر الرابط',
            loading: _isLoading,
            onTap: _createLink,
          ),
          _methodCard(
            icon: Iconsax.profile_2user,
            title: 'من صديق',
            subtitle: 'اختر جهة اتصال، يصله إشعار ليقبل أو يرفض',
            onTap: _goContacts,
          ),
        ],
      ),
    );
  }

  Widget _methodCard({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    bool loading = false,
  }) {
    final colors = context.appColors;
    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      onTap: loading ? null : onTap,
      child: Row(children: [
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
              color: colors.primaryLight,
              borderRadius: BorderRadius.circular(14)),
          child: Icon(icon, color: colors.primary, size: 24),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title,
                  style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary)),
              const SizedBox(height: 2),
              Text(subtitle,
                  style: TextStyle(
                      fontSize: 12, color: colors.textSecondary)),
            ],
          ),
        ),
        const SizedBox(width: AppSpacing.sm),
        loading
            ? SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                    strokeWidth: 2.2, color: colors.primary))
            : Icon(Iconsax.arrow_left_2,
                size: 18, color: colors.textHint),
      ]),
    ).animate().fadeIn(duration: 250.ms);
  }

  // ════════════════════ Phase 2 — pick a friend ════════════════════
  Widget _buildContacts() {
    final colors = context.appColors;
    return Column(
      children: [
        Container(
          width: double.infinity,
          margin: const EdgeInsets.fromLTRB(
              AppSpacing.lg, AppSpacing.md, AppSpacing.lg, 0),
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.lg, vertical: AppSpacing.md),
          decoration: BoxDecoration(
            color: colors.primaryLight,
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
          child: Row(children: [
            Icon(Iconsax.money_recive, size: 18, color: colors.primary),
            const SizedBox(width: AppSpacing.sm),
            Text('تطلب ',
                style: TextStyle(fontSize: 13, color: colors.textSecondary)),
            Directionality(
              textDirection: TextDirection.ltr,
              child: Text(Money.format(_amount, _currency),
                  style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: colors.primary)),
            ),
          ]),
        ),
        Expanded(child: _contactsBody()),
      ],
    );
  }

  Widget _contactsBody() {
    final colors = context.appColors;
    if (_loadingContacts) {
      return Center(
          child: CircularProgressIndicator(color: colors.primary));
    }
    if (_permissionDenied) {
      return EmptyState(
        icon: Iconsax.profile_2user,
        title: 'السماح بالوصول لجهات الاتصال',
        subtitle: 'نستخدم جهات اتصالك فقط لإيجاد أصدقائك على صكّ. لا نحفظ أرقامك.',
        actionLabel: 'السماح بالوصول',
        onAction: _retryContacts,
      );
    }
    if (_contactsError != null) {
      return EmptyState(
        icon: Iconsax.warning_2,
        title: 'تعذّر تحميل جهات الاتصال',
        subtitle: _contactsError,
        actionLabel: 'إعادة المحاولة',
        onAction: _loadContacts,
      );
    }
    if (_registered.isEmpty) {
      return const EmptyState(
        icon: Iconsax.profile_2user,
        title: 'لا يوجد أصدقاء على صكّ',
        subtitle: 'لم نعثر على أيٍّ من جهات اتصالك مسجّلاً على صكّ لطلب دفعة منه.',
      );
    }

    final q = _query.trim();
    final list = q.isEmpty
        ? _registered
        : _registered
            .where((c) => c.name.contains(q) || c.account.contains(q))
            .toList();

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.sm),
          child: TextField(
            onChanged: (v) => setState(() => _query = v),
            decoration: InputDecoration(
              hintText: 'ابحث بالاسم أو الرقم',
              prefixIcon: Icon(Iconsax.search_normal,
                  size: 20, color: colors.textHint),
              filled: true,
              fillColor: colors.surface,
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none),
              enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide:
                      BorderSide(color: colors.textHint.withValues(alpha: 0.25))),
              focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide:
                      BorderSide(color: colors.primary, width: 1.4)),
              contentPadding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.lg, vertical: AppSpacing.md),
            ),
          ),
        ),
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.xxl),
            itemCount: list.length,
            itemBuilder: (c, i) => _contactTile(list[i])
                .animate(delay: (i * 35).ms)
                .fadeIn()
                .slideX(begin: 0.05),
          ),
        ),
      ],
    );
  }

  Widget _contactTile(_Reg c) {
    final colors = context.appColors;
    final busy = _creatingAccount == c.account;
    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.md, vertical: AppSpacing.md),
      onTap: busy ? null : () => _requestFrom(c),
      child: Row(children: [
        Container(
          width: 46,
          height: 46,
          decoration: BoxDecoration(
              color: colors.primaryLight,
              borderRadius: BorderRadius.circular(AppRadius.md)),
          alignment: Alignment.center,
          child: Text(c.initials,
              style: TextStyle(
                  color: colors.primary,
                  fontWeight: FontWeight.w800,
                  fontSize: 15)),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(c.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary)),
              const SizedBox(height: 2),
              Text(c.account,
                  textDirection: TextDirection.ltr,
                  style: TextStyle(
                      fontSize: 11.5,
                      color: colors.textSecondary,
                      fontFamily: 'monospace')),
            ],
          ),
        ),
        const SizedBox(width: AppSpacing.sm),
        busy
            ? SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(
                    strokeWidth: 2.2, color: colors.primary))
            : Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.md, vertical: 6),
                decoration: BoxDecoration(
                  color: colors.primaryLight,
                  borderRadius: BorderRadius.circular(AppRadius.pill),
                ),
                child: Row(mainAxisSize: MainAxisSize.min, children: [
                  Text('طلب',
                      style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w700,
                          color: colors.primary)),
                  const SizedBox(width: 2),
                  Icon(Iconsax.money_recive, size: 14, color: colors.primary),
                ]),
              ),
      ]),
    );
  }

  // ════════════════════ Phase 3 — link/QR result ════════════════════
  Widget _buildResult(Map<String, dynamic> req) {
    final colors = context.appColors;
    final uuid = req['uuid'].toString();
    final amount = (req['amount'] as num).toDouble();
    final currency = req['currency'].toString();
    final note = (req['note'] ?? '').toString();
    final payUrl = (req['pay_url'] ?? 'sakk://pay/$uuid').toString();
    final shareText = 'طلب دفعة عبر صكّ\n'
        'المبلغ: ${Money.format(amount, currency)}'
        '${note.isNotEmpty ? '\nالسبب: $note' : ''}\n'
        'ادفع الآن عبر الرابط:\n$payUrl';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppSpacing.xl),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                  color: colors.successLight,
                  borderRadius: BorderRadius.circular(AppRadius.lg)),
              child: Icon(Iconsax.tick_circle,
                  color: colors.success, size: 32),
            ),
          ).animate().scale(begin: const Offset(0.8, 0.8)),
          const SizedBox(height: AppSpacing.lg),
          Center(
            child: Directionality(
              textDirection: TextDirection.ltr,
              child: Text(Money.format(amount, currency),
                  style: TextStyle(
                      fontSize: 30,
                      fontWeight: FontWeight.w800,
                      color: colors.textPrimary)),
            ),
          ),
          if (note.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.xs),
            Center(
                child: Text(note,
                    style: TextStyle(
                        fontSize: 13, color: colors.textSecondary))),
          ],
          const SizedBox(height: AppSpacing.md),
          const Center(
              child:
                  StatusBadge(label: 'صالح لمدة 24 ساعة', kind: StatusKind.warning)),
          const SizedBox(height: AppSpacing.xl),
          Center(child: BrandedQr(data: payUrl, size: 220))
              .animate()
              .fadeIn(delay: 120.ms),
          const SizedBox(height: AppSpacing.xl),
          AppCard(
            color: colors.primaryLight,
            onTap: () => _copy(payUrl),
            padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.lg, vertical: AppSpacing.md),
            child: Row(children: [
              Icon(Iconsax.link_2, size: 18, color: colors.primary),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Text(payUrl,
                    textDirection: TextDirection.ltr,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        fontSize: 12.5,
                        color: colors.primary,
                        fontWeight: FontWeight.w600)),
              ),
              const SizedBox(width: AppSpacing.sm),
              Icon(Iconsax.copy, size: 16, color: colors.primary),
            ]),
          ),
          const SizedBox(height: AppSpacing.md),
          Row(children: [
            Expanded(
              child: AppButton(
                label: 'نسخ الرابط',
                icon: Iconsax.copy,
                variant: AppButtonVariant.secondary,
                onPressed: () => _copy(payUrl),
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: AppButton(
                label: 'مشاركة',
                icon: Iconsax.share,
                onPressed: () => Share.share(shareText),
              ),
            ),
          ]),
          const SizedBox(height: AppSpacing.sm),
          TextButton(
            onPressed: () =>
                context.canPop() ? context.pop() : context.go('/dashboard'),
            child: const Text('تم'),
          ),
        ],
      ),
    );
  }

  void _copy(String payUrl) {
    Clipboard.setData(ClipboardData(text: payUrl));
    _snack('تم نسخ رابط الدفع', success: true);
  }

  // ── Shared bits ───────────────────────────────────────────────────────
  Widget _currencyToggle() {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.xs),
      decoration: BoxDecoration(
        color: colors.inputBackground,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Row(children: [
        Expanded(child: _currencyPill('USD', 'دولار أمريكي')),
        const SizedBox(width: AppSpacing.xs),
        Expanded(child: _currencyPill('SYP', 'ليرة سورية')),
      ]),
    );
  }

  Widget _currencyPill(String code, String label) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final selected = _currency == code;
    return GestureDetector(
      onTap: () => setState(() {
        _currency = code;
        _amountError = null;
      }),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
        decoration: BoxDecoration(
          color: selected
              ? (isDark ? colors.surface : colors.primary)
              : Colors.transparent,
          borderRadius: BorderRadius.circular(AppRadius.sm + 2),
        ),
        child: Column(children: [
          Text(code,
              style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: selected
                      ? (isDark ? colors.textPrimary : Colors.white)
                      : colors.textPrimary)),
          const SizedBox(height: 2),
          Text(label,
              style: TextStyle(
                  fontSize: 11,
                  color: selected
                      ? (isDark ? colors.textPrimary : Colors.white)
                          .withValues(alpha: 0.9)
                      : colors.textSecondary)),
        ]),
      ),
    );
  }

  InputDecoration _input({
    required String label,
    String? hint,
    IconData? icon,
    String? suffix,
    String? error,
  }) {
    final colors = context.appColors;
    OutlineInputBorder border(Color c, [double w = 1]) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: c, width: w),
        );
    return InputDecoration(
      labelText: label,
      hintText: hint,
      errorText: error,
      prefixIcon: icon != null ? Icon(icon, color: colors.textHint) : null,
      suffixText: suffix,
      filled: true,
      fillColor: colors.surface,
      border: border(colors.textHint.withValues(alpha: 0.25)),
      enabledBorder: border(colors.textHint.withValues(alpha: 0.25)),
      focusedBorder: border(colors.primary, 1.4),
      errorBorder: border(colors.error),
      focusedErrorBorder: border(colors.error, 1.4),
      contentPadding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg, vertical: AppSpacing.lg),
    );
  }
}

class _Reg {
  final String name;
  final String account;
  final String initials;
  _Reg({required this.name, required this.account, required this.initials});
}
