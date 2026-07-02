import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/services/permission_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/contacts_repository.dart';

/// Send to contacts — clean search + two sections (على صكّ / دعوة) rendered
/// as AppCard rows. Registered contacts open the send flow; the rest can be
/// invited via a referral link.
class ContactsTransferPage extends ConsumerStatefulWidget {
  const ContactsTransferPage({super.key});

  @override
  ConsumerState<ContactsTransferPage> createState() => _ContactsTransferPageState();
}

class _ContactsTransferPageState extends ConsumerState<ContactsTransferPage> {
  bool _loading = true;
  String? _error;
  bool _permissionDenied = false;

  final List<_Registered> _registered = [];
  final List<_Plain> _others = [];
  Map<String, dynamic>? _referral;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
      _permissionDenied = false;
    });
    _registered.clear();
    _others.clear();
    try {
      final granted = await FlutterContacts.requestPermission(readonly: true);
      if (!granted) {
        setState(() {
          _permissionDenied = true;
          _loading = false;
        });
        return;
      }

      final contacts = await FlutterContacts.getContacts(withProperties: true);

      final phoneToName = <String, String>{};
      for (final c in contacts) {
        for (final p in c.phones) {
          final num = p.number.trim();
          if (num.isEmpty) continue;
          phoneToName[num] = c.displayName;
        }
      }

      final repo = ref.read(contactsRepositoryProvider);
      final phones = phoneToName.keys.toList();
      final matches = phones.isEmpty ? <Map<String, dynamic>>[] : await repo.match(phones);
      _referral = await repo.referralInfo();

      final matchedPhones = <String>{};
      for (final m in matches) {
        final phone = m['phone']?.toString() ?? '';
        matchedPhones.add(phone);
        _registered.add(_Registered(
          name: (m['name']?.toString().isNotEmpty ?? false)
              ? m['name'].toString()
              : (phoneToName[phone] ?? phone),
          accountNumber: m['account_number']?.toString() ?? '',
          initials: m['initials']?.toString() ?? '',
        ));
      }

      phoneToName.forEach((phone, name) {
        if (!matchedPhones.contains(phone)) {
          _others.add(_Plain(name: name.isNotEmpty ? name : phone, phone: phone));
        }
      });

      _registered.sort((a, b) => a.name.compareTo(b.name));
      _others.sort((a, b) => a.name.compareTo(b.name));

      setState(() => _loading = false);
    } catch (e) {
      // Real backend message (ApiException.toString()) for the match/referral calls.
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  /// Denied-state CTA: jump to OS settings if the user permanently denied
  /// contacts, otherwise re-request (first denial is recoverable in-app).
  Future<void> _retryContacts() async {
    if (await PermissionService.isContactsPermanentlyDenied()) {
      await PermissionService.openSettings();
      return;
    }
    await _load();
  }

  Future<void> _invite(_Plain c) async {
    final code = _referral?['referral_code']?.toString() ?? '';
    final inviteUrl = (_referral?['invite_url'] ?? 'https://sakk.app/invite/$code').toString();
    final text = 'انضم إليّ على محفظة صكّ!\n'
        'حوّل واستقبل الأموال فوراً وبدون رسوم.\n'
        'سجّل عبر الرابط (كود الإحالة $code مُضمَّن):\n$inviteUrl';

    var phone = c.phone.replaceAll(RegExp(r'[^\d+]'), '');
    if (phone.startsWith('+')) phone = phone.substring(1);
    if (phone.startsWith('00')) phone = phone.substring(2);

    final whatsapp = Uri.parse('https://wa.me/$phone?text=${Uri.encodeComponent(text)}');
    try {
      final launched = await launchUrl(whatsapp, mode: LaunchMode.externalApplication);
      if (!launched && mounted) await Share.share(text);
    } catch (_) {
      if (mounted) await Share.share(text);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return AppScaffold(
      title: 'التحويل لجهات الاتصال',
      body: _loading
          ? Center(child: CircularProgressIndicator(color: colors.primary))
          : _permissionDenied
              ? EmptyState(
                  icon: Iconsax.profile_2user,
                  title: 'السماح بالوصول لجهات الاتصال',
                  subtitle: 'نستخدم جهات اتصالك فقط لإيجاد أصدقائك على صكّ. لا نحفظ أرقامك.',
                  actionLabel: 'السماح بالوصول',
                  onAction: _retryContacts,
                )
              : _error != null
                  ? EmptyState(
                      icon: Iconsax.warning_2,
                      title: 'تعذّر تحميل جهات الاتصال',
                      subtitle: _error,
                      actionLabel: 'إعادة المحاولة',
                      onAction: _load,
                    )
                  : _list(),
    );
  }

  Widget _list() {
    final colors = context.appColors;
    final q = _query.trim();
    final reg = q.isEmpty
        ? _registered
        : _registered.where((c) => c.name.contains(q) || c.accountNumber.contains(q)).toList();
    final oth = q.isEmpty ? _others : _others.where((c) => c.name.contains(q) || c.phone.contains(q)).toList();

    final noContacts = _registered.isEmpty && _others.isEmpty;
    if (noContacts) {
      return const EmptyState(
        icon: Iconsax.profile_2user,
        title: 'لا توجد جهات اتصال',
        subtitle: 'لم نعثر على جهات اتصال على جهازك لعرضها هنا.',
      );
    }

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.sm),
          child: TextField(
            onChanged: (v) => setState(() => _query = v),
            decoration: InputDecoration(
              hintText: 'ابحث بالاسم أو الرقم',
              prefixIcon: Icon(Iconsax.search_normal, size: 20, color: colors.textHint),
              filled: true,
              fillColor: colors.surface,
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
              enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: colors.textHint.withValues(alpha: 0.25))),
              focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: colors.primary, width: 1.4)),
              contentPadding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
            ),
          ),
        ),
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.xxl),
            children: [
              if (reg.isNotEmpty) _sectionHeader('على صكّ', reg.length, StatusKind.success),
              ...reg.asMap().entries.map(
                  (e) => _registeredTile(e.value).animate(delay: (e.key * 35).ms).fadeIn().slideX(begin: 0.05)),
              if (oth.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.sm),
                _sectionHeader('دعوة إلى صكّ', oth.length, StatusKind.neutral),
              ],
              ...oth.asMap().entries.map(
                  (e) => _inviteTile(e.value).animate(delay: (e.key * 35).ms).fadeIn().slideX(begin: 0.05)),
              if (reg.isEmpty && oth.isEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 80),
                  child: Center(
                    child: Text('لا توجد نتائج مطابقة لبحثك',
                        style: TextStyle(color: colors.textSecondary)),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _sectionHeader(String title, int count, StatusKind kind) {
    final colors = context.appColors;
    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSpacing.xs, AppSpacing.lg, AppSpacing.xs, AppSpacing.sm),
      child: Row(children: [
        Text(title,
            style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: colors.textPrimary)),
        const SizedBox(width: AppSpacing.sm),
        StatusBadge(label: '$count', kind: kind),
      ]),
    );
  }

  Widget _registeredTile(_Registered c) {
    final colors = context.appColors;
    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.md),
      onTap: () => context.push('/qr-send', extra: c.accountNumber),
      child: Row(children: [
        _initialsAvatar(c.initials),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(c.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: colors.textPrimary)),
            const SizedBox(height: 2),
            Text(c.accountNumber,
                textDirection: TextDirection.ltr,
                style: TextStyle(fontSize: 11.5, color: colors.textSecondary, fontFamily: 'monospace')),
          ]),
        ),
        const SizedBox(width: AppSpacing.sm),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: 6),
          decoration: BoxDecoration(
            color: colors.primaryLight,
            borderRadius: BorderRadius.circular(AppRadius.pill),
          ),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Text('إرسال',
                style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: colors.primary)),
            const SizedBox(width: 2),
            Icon(Iconsax.arrow_left_2, size: 14, color: colors.primary),
          ]),
        ),
      ]),
    );
  }

  Widget _inviteTile(_Plain c) {
    final colors = context.appColors;
    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.md),
      onTap: () => _invite(c),
      child: Row(children: [
        IconTile(icon: Iconsax.user, color: colors.textSecondary),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(c.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: colors.textPrimary)),
            const SizedBox(height: 2),
            Text(c.phone,
                textDirection: TextDirection.ltr,
                style: TextStyle(fontSize: 11.5, color: colors.textSecondary)),
          ]),
        ),
        const SizedBox(width: AppSpacing.sm),
        Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(Iconsax.gift, size: 16, color: colors.primary),
          const SizedBox(width: 4),
          Text('دعوة', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: colors.primary)),
        ]),
      ]),
    );
  }

  Widget _initialsAvatar(String initials) {
    final colors = context.appColors;
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: colors.primaryLight,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: TextStyle(color: colors.primary, fontWeight: FontWeight.w800, fontSize: 15),
      ),
    );
  }
}

class _Registered {
  final String name;
  final String accountNumber;
  final String initials;
  _Registered({required this.name, required this.accountNumber, required this.initials});
}

class _Plain {
  final String name;
  final String phone;
  _Plain({required this.name, required this.phone});
}
