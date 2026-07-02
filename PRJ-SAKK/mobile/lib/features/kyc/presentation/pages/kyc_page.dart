import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:image_picker/image_picker.dart';

import '../../data/repositories/kyc_repository.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/services/permission_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';

/// KYC — 3-level system (غير موثّق → موثّق أساسي → موثّق كامل).
/// Requirements per level:
///   L0 → L1: email + phone + id_document
///   L1 → L2: + selfie
/// Documents are auto-approved but flagged for review; camera-only capture.
class KycPage extends ConsumerStatefulWidget {
  const KycPage({super.key});

  @override
  ConsumerState<KycPage> createState() => _KycPageState();
}

class _KycPageState extends ConsumerState<KycPage> {
  final _picker = ImagePicker();

  Map<String, dynamic>? _status;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final status = await ref.read(kycRepositoryProvider).getKycStatus();
      if (mounted) setState(() { _status = status; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Map<String, dynamic> get _verifications =>
      Map<String, dynamic>.from(_status?['verifications'] as Map? ?? {});

  int get _currentLevel => (_status?['current_level'] as int?) ?? 0;
  bool get _isVerified => _currentLevel >= 2;

  String _stateOf(String key) {
    final v = _verifications[key];
    if (v is Map) return (v['status'] ?? 'not_started').toString();
    return 'not_started';
  }

  bool _pendingReview(String key) {
    final v = _verifications[key];
    return v is Map && v['pending_review'] == true;
  }

  bool _isDone(String key) => _stateOf(key) == 'approved';

  List<String> get _allRequirements => ['email', 'phone', 'id_document', 'selfie'];

  int get _doneCount => _allRequirements.where(_isDone).length;

  void _snack(String msg, Color color) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: color),
    );
  }

  /// Map a thrown error to human Arabic copy — surfaces the backend's own
  /// message/field error instead of a raw exception string.
  String _errorText(Object e) {
    if (e is ApiException) {
      final errorLists = e.errors?.values;
      if (errorLists != null && errorLists.isNotEmpty) {
        final firstList = errorLists.first;
        if (firstList.isNotEmpty) return firstList.first;
      }
      final msg = e.message.trim();
      if (msg.isNotEmpty) return msg;
    }
    return 'حدث خطأ غير متوقع، حاول مرة أخرى';
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return AppScaffold(
      title: 'توثيق الهوية',
      onBack: () => context.canPop() ? context.pop() : context.go('/dashboard'),
      body: _loading
          ? const SkeletonEmptyFriendly()
          : RefreshIndicator(
              color: colors.primary,
              onRefresh: _load,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 40),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _hero(),
                    const SizedBox(height: AppSpacing.xl),
                    _levelProgress(),
                    const SizedBox(height: AppSpacing.lg),
                    const SectionHeader(title: 'متطلبات التوثيق'),
                    const SizedBox(height: AppSpacing.xs),
                    _requirementTile(
                      key: 'email',
                      icon: Iconsax.sms,
                      title: 'البريد الإلكتروني',
                      subtitle: 'تأكيد عبر رمز يُرسل لبريدك',
                      onTap: _emailSheet,
                    ),
                    _requirementTile(
                      key: 'phone',
                      icon: Iconsax.call,
                      title: 'رقم الهاتف',
                      subtitle: 'تأكيد عبر رمز SMS',
                      onTap: _phoneSheet,
                    ),
                    _requirementTile(
                      key: 'id_document',
                      icon: Iconsax.personalcard,
                      title: 'وثيقة الهوية',
                      subtitle: 'صوّر بطاقتك أو جوازك',
                      onTap: _idSheet,
                    ),
                    _requirementTile(
                      key: 'selfie',
                      icon: Iconsax.camera,
                      title: 'صورة شخصية',
                      subtitle: 'التقط صورة واضحة لوجهك',
                      onTap: _selfieSheet,
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    const SectionHeader(title: 'حدودك الحالية'),
                    const SizedBox(height: AppSpacing.xs),
                    _limitsCard(),
                  ],
                ),
              ),
            ),
    );
  }

  // ──────────────── Hero (single indigo gradient) ────────────────

  Widget _hero() {
    final labels = {
      0: 'غير موثّق',
      1: 'موثّق أساسي',
      2: 'موثّق كامل',
    };
    final icons = {
      0: Iconsax.shield,
      1: Iconsax.shield_tick,
      2: Iconsax.shield_security,
    };

    final label = labels[_currentLevel] ?? 'غير موثّق';
    final icon = icons[_currentLevel] ?? Iconsax.shield;
    final colors = context.appColors;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.xxl),
      decoration: BoxDecoration(
        gradient: LinearGradient(
            colors: colors.cardGradientVisa,
            begin: Alignment.topLeft,
            end: Alignment.bottomRight),
        borderRadius: BorderRadius.circular(AppRadius.xl),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withValues(alpha: 0.3),
              blurRadius: 20,
              offset: const Offset(0, 8))
        ],
      ),
      child: Column(
        children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(AppRadius.xl)),
            child: Icon(icon, color: Colors.white, size: 36),
          ),
          const SizedBox(height: AppSpacing.lg),
          Text(
            label,
            style: const TextStyle(
                color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            _currentLevel == 2
                ? 'تم توثيق هويتك بالكامل — جميع الميزات مفتوحة'
                : _currentLevel == 1
                    ? 'أكمل الصورة الشخصية للوصول إلى أعلى مستوى'
                    : 'أكمل المتطلبات لرفع حدودك وفتح الميزات',
            textAlign: TextAlign.center,
            style: TextStyle(
                color: Colors.white.withValues(alpha: 0.9), fontSize: 13, height: 1.4),
          ),
          if (!_isVerified) ...[
            const SizedBox(height: AppSpacing.lg),
            Row(
              children: [
                Expanded(
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(AppRadius.sm),
                    child: LinearProgressIndicator(
                      value: _doneCount / 4,
                      backgroundColor: Colors.white24,
                      valueColor: const AlwaysStoppedAnimation(Colors.white),
                      minHeight: 8,
                    ),
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Text('$_doneCount/4',
                    textDirection: TextDirection.ltr,
                    style: const TextStyle(
                        color: Colors.white, fontWeight: FontWeight.bold)),
              ],
            ),
          ],
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.08, end: 0);
  }

  // ──────────────── Level Progress ────────────────

  Widget _levelProgress() {
    final colors = context.appColors;
    final levels = [
      {'label': 'غير موثّق', 'reqs': 0},
      {'label': 'أساسي', 'reqs': 3},
      {'label': 'كامل', 'reqs': 4},
    ];

    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('مستويات التوثيق',
              style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: colors.textPrimary)),
          const SizedBox(height: AppSpacing.lg),
          Row(
            children: levels.asMap().entries.map((entry) {
              final idx = entry.key;
              final level = entry.value;
              final isActive = idx <= _currentLevel;
              final isCurrent = idx == _currentLevel;

              return Expanded(
                child: Column(
                  children: [
                    Container(
                      width: 36, height: 36,
                      decoration: BoxDecoration(
                        gradient: isActive
                            ? LinearGradient(
                                colors: colors.cardGradientVisa,
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight)
                            : null,
                        color: isActive ? null : colors.textHint.withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                        border: isCurrent ? Border.all(color: colors.surface, width: 3) : null,
                        boxShadow: isCurrent
                            ? [BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 8)]
                            : null,
                      ),
                      child: Center(
                        child: isActive
                            ? const Icon(Iconsax.tick_circle, size: 18, color: Colors.white)
                            : Text('${idx + 1}',
                                style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                    color: colors.textHint)),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      level['label'] as String,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: isCurrent ? FontWeight.bold : FontWeight.normal,
                        color: isCurrent ? colors.primary : colors.textSecondary,
                      ),
                    ),
                    if (idx < levels.length - 1)
                      Container(
                        height: 2,
                        margin: const EdgeInsets.only(top: 4),
                        decoration: BoxDecoration(
                          gradient: isActive && idx < _currentLevel
                              ? LinearGradient(
                                  colors: colors.cardGradientVisa)
                              : null,
                          color: isActive && idx < _currentLevel
                              ? null
                              : colors.textHint.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(1),
                        ),
                      ),
                  ],
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  // ──────────────── Requirement tile ────────────────

  Widget _requirementTile({
    required String key,
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    final colors = context.appColors;
    final done = _isDone(key);
    final pending = done && _pendingReview(key);
    final rejected = _stateOf(key) == 'rejected';
    final actionable = !done || rejected;

    Color iconColor;
    if (rejected) {
      iconColor = colors.error;
    } else if (pending) {
      iconColor = colors.warning;
    } else if (done) {
      iconColor = colors.success;
    } else {
      iconColor = colors.primary;
    }

    Widget trailing;
    if (rejected) {
      trailing = const StatusBadge(label: 'مرفوض — أعد المحاولة', kind: StatusKind.error);
    } else if (pending) {
      trailing = const StatusBadge(label: 'قيد المراجعة', kind: StatusKind.warning);
    } else if (done) {
      trailing = const StatusBadge(label: 'مكتمل', kind: StatusKind.success);
    } else {
      trailing = Icon(Iconsax.arrow_left_2, color: colors.primary, size: 20);
    }

    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      padding: const EdgeInsets.all(AppSpacing.lg),
      onTap: actionable ? onTap : null,
      child: Row(
        children: [
          IconTile(icon: icon, color: iconColor),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                        color: colors.textPrimary)),
                const SizedBox(height: 3),
                Text(subtitle,
                    style: TextStyle(
                        fontSize: 12.5, color: colors.textSecondary)),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          trailing,
        ],
      ),
    );
  }

  // ──────────────── Limits card ────────────────

  Widget _limitsCard() {
    final limits = _status?['limits'] as Map?;
    final usd = (limits?['USD'] as Map?) ?? {};
    final syp = (limits?['SYP'] as Map?) ?? {};
    final balanceLimit = (_status?['balance_limit'] as Map?) ?? {};
    final cardsLimit = (_status?['cards_limit'] as int?) ?? 0;
    double n(dynamic v) => (v as num?)?.toDouble() ?? 0;

    final levelLabels = {0: 'غير موثّق', 1: 'موثّق أساسي', 2: 'موثّق كامل'};
    final levelKinds = {
      0: StatusKind.neutral,
      1: StatusKind.warning,
      2: StatusKind.success
    };
    final colors = context.appColors;

    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Iconsax.chart_2, size: 18, color: colors.primary),
              const SizedBox(width: AppSpacing.sm),
              Text('حدودك الحالية',
                  style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary)),
              const Spacer(),
              StatusBadge(
                label: levelLabels[_currentLevel] ?? 'غير موثّق',
                kind: levelKinds[_currentLevel] ?? StatusKind.neutral,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          _limitRow('الحد اليومي', Money.format(n(usd['daily']), 'USD'),
              Money.format(n(syp['daily']), 'SYP')),
          const Divider(height: 18),
          _limitRow('الحد الشهري', Money.format(n(usd['monthly']), 'USD'),
              Money.format(n(syp['monthly']), 'SYP')),
          const Divider(height: 18),
          _limitRow('حد المعاملة', Money.format(n(usd['single']), 'USD'),
              Money.format(n(syp['single']), 'SYP')),
          const Divider(height: 18),
          _limitRow('حد الرصيد', Money.format(n(balanceLimit['USD']), 'USD'),
              Money.format(n(balanceLimit['SYP']), 'SYP')),
          const Divider(height: 18),
          _limitRowSimple('عدد البطاقات المسموح', cardsLimit.toString()),
        ],
      ),
    );
  }

  Widget _limitRow(String label, String usd, String syp) {
    final colors = context.appColors;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
            child: Text(label,
                style: TextStyle(fontSize: 13, color: colors.textSecondary))),
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(usd,
                textDirection: TextDirection.ltr,
                style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
            const SizedBox(height: 2),
            Text(syp,
                textDirection: TextDirection.ltr,
                style: TextStyle(fontSize: 11, color: colors.textSecondary)),
          ],
        ),
      ],
    );
  }

  Widget _limitRowSimple(String label, String value) {
    final colors = context.appColors;
    return Row(
      children: [
        Expanded(
            child: Text(label,
                style: TextStyle(fontSize: 13, color: colors.textSecondary))),
        Text(value,
            textDirection: TextDirection.ltr,
            style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: colors.textPrimary)),
      ],
    );
  }

  // ──────────────── Email sheet ────────────────

  Future<void> _emailSheet() async {
    final colors = context.appColors;
    final otp = TextEditingController();
    bool sent = false;
    bool busy = false;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(ctx).viewInsets.bottom + 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _sheetHandle(),
              _sheetHeader(Iconsax.sms, 'تأكيد البريد الإلكتروني',
                  sent ? 'أدخل الرمز المرسل إلى بريدك' : 'سنرسل رمز تحقق إلى بريدك الإلكتروني'),
              const SizedBox(height: 20),
              if (!sent)
                _primaryButton('إرسال الرمز', busy, () async {
                  setSheet(() => busy = true);
                  try {
                    await ref.read(kycRepositoryProvider).sendEmailCode();
                    setSheet(() { sent = true; busy = false; });
                    _snack('تم إرسال الرمز إلى بريدك', colors.success);
                  } catch (e) {
                    setSheet(() => busy = false);
                    _snack(_errorText(e), colors.error);
                  }
                })
              else ...[
                _otpField(otp),
                const SizedBox(height: 16),
                _primaryButton('تأكيد', busy, () async {
                  if (otp.text.trim().length != 6) { _snack('أدخل الرمز المكوّن من 6 أرقام', colors.warning); return; }
                  setSheet(() => busy = true);
                  try {
                    await ref.read(kycRepositoryProvider).verifyEmailCode(otp.text.trim());
                    if (ctx.mounted) Navigator.pop(ctx);
                    await _load();
                    _snack('✓ تم تأكيد البريد الإلكتروني', colors.success);
                  } catch (e) {
                    setSheet(() => busy = false);
                    _snack(_errorText(e), colors.error);
                  }
                }),
                TextButton(onPressed: busy ? null : () async {
                  try {
                    await ref.read(kycRepositoryProvider).sendEmailCode();
                    _snack('تم إعادة الإرسال', colors.success);
                  } catch (e) {
                    _snack(_errorText(e), colors.error);
                  }
                }, child: const Text('إعادة إرسال الرمز')),
              ],
            ],
          ),
        ),
      ),
    );
    otp.dispose();
  }

  // ──────────────── Phone sheet ────────────────

  Future<void> _phoneSheet() async {
    final colors = context.appColors;
    final phone = TextEditingController(text: '+963');
    final otp = TextEditingController();
    bool updated = false;
    bool sent = false;
    bool busy = false;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(ctx).viewInsets.bottom + 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _sheetHandle(),
              _sheetHeader(Iconsax.call, 'تأكيد رقم الهاتف',
                  sent ? 'أدخل الرمز المرسل إلى هاتفك' : 'أدخل رقم هاتفك لاستلام رمز التحقق'),
              const SizedBox(height: 20),
              if (!sent) ...[
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: TextField(
                    controller: phone,
                    keyboardType: TextInputType.phone,
                    decoration: _fieldDeco('رقم الهاتف', Iconsax.call),
                  ),
                ),
                const SizedBox(height: 16),
                _primaryButton('إرسال الرمز', busy, () async {
                  if (phone.text.trim().length < 8) { _snack('أدخل رقم هاتف صحيح', colors.warning); return; }
                  setSheet(() => busy = true);
                  try {
                    final repo = ref.read(kycRepositoryProvider);
                    if (!updated) { await repo.updatePhone(phone.text.trim()); updated = true; }
                    await repo.sendPhoneCode();
                    setSheet(() { sent = true; busy = false; });
                    _snack('تم إرسال الرمز إلى هاتفك', colors.success);
                  } catch (e) {
                    setSheet(() => busy = false);
                    _snack(_errorText(e), colors.error);
                  }
                }),
              ] else ...[
                _otpField(otp),
                const SizedBox(height: 16),
                _primaryButton('تأكيد', busy, () async {
                  if (otp.text.trim().length != 6) { _snack('أدخل الرمز المكوّن من 6 أرقام', colors.warning); return; }
                  setSheet(() => busy = true);
                  try {
                    await ref.read(kycRepositoryProvider).verifyPhoneCode(otp.text.trim());
                    if (ctx.mounted) Navigator.pop(ctx);
                    await _load();
                    _snack('✓ تم تأكيد رقم الهاتف', colors.success);
                  } catch (e) {
                    setSheet(() => busy = false);
                    _snack(_errorText(e), colors.error);
                  }
                }),
                TextButton(onPressed: busy ? null : () async {
                  try { await ref.read(kycRepositoryProvider).sendPhoneCode(); _snack('تم إعادة الإرسال', colors.success); } catch (e) { _snack(_errorText(e), colors.error); }
                }, child: const Text('إعادة إرسال الرمز')),
              ],
            ],
          ),
        ),
      ),
    );
    phone.dispose();
    otp.dispose();
  }

  // ──────────────── ID document sheet ────────────────

  Future<void> _idSheet() async {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    String idType = 'national_id';
    File? front;
    File? back;
    bool busy = false;

    const labels = {'national_id': 'بطاقة هوية', 'passport': 'جواز سفر', 'drivers_license': 'رخصة قيادة'};

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(ctx).viewInsets.bottom + 24),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _sheetHandle(),
                _sheetHeader(Iconsax.personalcard, 'وثيقة الهوية', 'اختر النوع وصوّر الوثيقة بالكاميرا'),
                const SizedBox(height: 20),
                Wrap(
                  spacing: 8,
                  children: labels.entries.map((e) {
                    final sel = idType == e.key;
                    return ChoiceChip(
                      label: Text(e.value),
                      selected: sel,
                      onSelected: (_) => setSheet(() => idType = e.key),
                      selectedColor: isDark ? colors.surface : colors.primary,
                      labelStyle: TextStyle(color: sel ? (isDark ? colors.textPrimary : Colors.white) : colors.textPrimary, fontWeight: FontWeight.w600, fontSize: 12),
                      backgroundColor: colors.inputBackground,
                    );
                  }).toList(),
                ),
                const SizedBox(height: 16),
                _captureTile('الوجه الأمامي', front, () async {
                  final f = await _capture();
                  if (f != null) setSheet(() => front = f);
                }),
                const SizedBox(height: 10),
                _captureTile('الوجه الخلفي (اختياري)', back, () async {
                  final f = await _capture();
                  if (f != null) setSheet(() => back = f);
                }),
                const SizedBox(height: 20),
                _primaryButton('رفع الوثيقة', busy, () async {
                  if (front == null) { _snack('صوّر الوجه الأمامي للوثيقة', colors.warning); return; }
                  setSheet(() => busy = true);
                  try {
                    await ref.read(kycRepositoryProvider).submitIdDocument(
                      frontImage: front!, documentType: idType, backImage: back);
                    if (ctx.mounted) Navigator.pop(ctx);
                    await _load();
                    _snack('✓ تم استلام وثيقة الهوية', colors.success);
                  } catch (e) {
                    setSheet(() => busy = false);
                    _snack(_errorText(e), colors.error);
                  }
                }),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ──────────────── Selfie sheet ────────────────

  Future<void> _selfieSheet() async {
    final colors = context.appColors;
    File? selfie;
    bool busy = false;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(ctx).viewInsets.bottom + 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _sheetHandle(),
              _sheetHeader(Iconsax.camera, 'الصورة الشخصية', 'التقط صورة واضحة لوجهك في إضاءة جيدة'),
              const SizedBox(height: 20),
              _captureTile('الصورة الشخصية', selfie, () async {
                final f = await _capture(front: true);
                if (f != null) setSheet(() => selfie = f);
              }),
              const SizedBox(height: 20),
              _primaryButton('رفع الصورة', busy, () async {
                if (selfie == null) { _snack('التقط صورتك الشخصية', colors.warning); return; }
                setSheet(() => busy = true);
                try {
                  await ref.read(kycRepositoryProvider).submitSelfie(selfieImage: selfie!);
                  if (ctx.mounted) Navigator.pop(ctx);
                  await _load();
                  _snack('✓ تم استلام الصورة الشخصية', colors.success);
                } catch (e) {
                  setSheet(() => busy = false);
                  _snack(_errorText(e), colors.error);
                }
              }),
            ],
          ),
        ),
      ),
    );
  }

  // ──────────────── Camera capture ────────────────

  Future<File?> _capture({bool front = false}) async {
    final colors = context.appColors;
    if (!await PermissionService.ensureCamera()) {
      final permanent = await PermissionService.isCameraPermanentlyDenied();
      _snack(
        permanent
            ? 'إذن الكاميرا مرفوض. فعّله من إعدادات التطبيق.'
            : 'يلزم إذن الكاميرا لتصوير الوثيقة.',
        colors.error,
      );
      if (permanent) await PermissionService.openSettings();
      return null;
    }
    try {
      final picked = await _picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 85,
        preferredCameraDevice: front ? CameraDevice.front : CameraDevice.rear,
      );
      if (picked == null) return null;
      final file = File(picked.path);
      if (!mounted) return null;

      final ok = await showDialog<bool>(
        context: context,
        builder: (ctx) => Dialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                child: Image.file(file, height: 300, width: double.infinity, fit: BoxFit.cover),
              ),
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => Navigator.pop(ctx, false),
                        icon: const Icon(Iconsax.refresh, size: 18),
                        label: const Text('إعادة'),
                        style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 12)),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () => Navigator.pop(ctx, true),
                        icon: const Icon(Iconsax.tick_circle, size: 18),
                        label: const Text('تأكيد'),
                        style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 12)),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
      return ok == true ? file : null;
    } catch (_) {
      _snack('تعذّر فتح الكاميرا. تأكد من الإذن.', colors.error);
      return null;
    }
  }

  // ──────────────── Shared widgets ────────────────

  Widget _sheetHandle() {
    final colors = context.appColors;
    return Center(
        child: Container(
          width: 40, height: 4,
          margin: const EdgeInsets.only(bottom: 16),
          decoration: BoxDecoration(
              color: colors.textHint.withValues(alpha: 0.35),
              borderRadius: BorderRadius.circular(2)),
        ),
      );
  }

  Widget _sheetHeader(IconData icon, String title, String subtitle) {
    final colors = context.appColors;
    return Column(
      children: [
        Container(
          width: 56, height: 56,
          decoration: BoxDecoration(
            gradient: LinearGradient(colors: [colors.primary.withValues(alpha: 0.15), colors.secondary.withValues(alpha: 0.1)]),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Icon(icon, color: colors.primary, size: 28),
        ),
        const SizedBox(height: 14),
        Text(title, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: colors.textPrimary)),
        const SizedBox(height: 6),
        Text(subtitle, textAlign: TextAlign.center, style: TextStyle(fontSize: 13, color: colors.textSecondary, height: 1.4)),
      ],
    );
  }

  Widget _primaryButton(String label, bool busy, VoidCallback onTap) {
    return SizedBox(
      height: 52,
      child: ElevatedButton(
        onPressed: busy ? null : onTap,
        style: ElevatedButton.styleFrom(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
        child: busy
            ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
            : Text(label, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
      ),
    );
  }

  Widget _otpField(TextEditingController c) {
    final colors = context.appColors;
    return Directionality(
      textDirection: TextDirection.ltr,
      child: TextField(
        controller: c,
        textAlign: TextAlign.center,
        keyboardType: TextInputType.number,
        maxLength: 6,
        autofocus: true,
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, letterSpacing: 12),
        decoration: InputDecoration(
          counterText: '',
          hintText: '------',
          hintStyle: TextStyle(letterSpacing: 12, color: colors.textHint.withValues(alpha: 0.4)),
          filled: true,
          fillColor: colors.inputBackground,
          border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14),
              borderSide: BorderSide(color: colors.textHint.withValues(alpha: 0.2))),
          focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14),
              borderSide: BorderSide(color: colors.primary, width: 1.5)),
        ),
      ),
    );
  }

  InputDecoration _fieldDeco(String label, IconData icon) {
    final colors = context.appColors;
    return InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, size: 20),
        filled: true,
        fillColor: colors.inputBackground,
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
        enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide(color: colors.textHint.withValues(alpha: 0.2))),
        focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide(color: colors.primary, width: 1.5)),
      );
  }

  Widget _captureTile(String label, File? file, VoidCallback onTap) {
    final colors = context.appColors;
    final has = file != null;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: has ? colors.success.withValues(alpha: 0.06) : colors.inputBackground,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
              color: has
                  ? colors.success.withValues(alpha: 0.4)
                  : colors.textHint.withValues(alpha: 0.2)),
        ),
        child: Row(
          children: [
            Container(
              width: 56, height: 56,
              decoration: BoxDecoration(
                color: has ? colors.success.withValues(alpha: 0.15) : colors.textHint.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
                image: has ? DecorationImage(image: FileImage(file), fit: BoxFit.cover) : null,
              ),
              child: has ? null : Icon(Iconsax.camera, size: 24, color: colors.textSecondary),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14, color: has ? colors.success : colors.textPrimary)),
                  const SizedBox(height: 2),
                  Text(has ? 'تم التصوير ✓ — اضغط للإعادة' : 'اضغط للتصوير', style: TextStyle(fontSize: 12, color: has ? colors.success : colors.textSecondary)),
                ],
              ),
            ),
            Icon(has ? Iconsax.tick_circle : Iconsax.camera, size: 20, color: has ? colors.success : colors.textHint),
          ],
        ),
      ),
    );
  }
}
