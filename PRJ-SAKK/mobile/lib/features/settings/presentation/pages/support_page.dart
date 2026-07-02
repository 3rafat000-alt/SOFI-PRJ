import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../support/data/models/support_contact_model.dart';
import '../../../support/data/repositories/support_repository.dart';

/// Help & Support hub. The chat hero opens the in-app live chat (`/chat`), the
/// ticket card opens the real ticket desk (`/support-tickets`), and the quick
/// channels are driven by the admin-managed `/app/support` settings (with
/// sensible fallbacks while loading or when a channel is left blank).
class SupportPage extends ConsumerStatefulWidget {
  const SupportPage({super.key});

  @override
  ConsumerState<SupportPage> createState() => _SupportPageState();
}

class _SupportPageState extends ConsumerState<SupportPage> {
  // Fallback channels — used only until the live config loads (or if blank).
  // The support bot is on Telegram (@SakkSupportBot); WhatsApp is a quick channel.
  static const _fallbackTelegram = 'SakkSupportBot'; // @SakkSupportBot
  static const _fallbackWa = '963982183110';
  static const _fallbackEmail = 'support@zanjour.com';
  static const _fallbackPhone = '+963982183110';

  // Prefilled opener so tapping WhatsApp starts the conversation.
  static const _waGreeting = 'مرحباً 👋 أريد التواصل مع دعم صكّ.';

  static const _faqs = <List<String>>[
    [
      'كيف أحوّل الأموال لشخص آخر؟',
      'من الرئيسية اضغط زر QR ثم «إرسال»، وأدخل رقم حساب المستلم أو امسح رمزه، ثم المبلغ وأكّد.'
    ],
    [
      'كم يستغرق وصول التحويل؟',
      'التحويلات بين مستخدمي صكّ فورية وبدون رسوم.'
    ],
    [
      'كيف أوثّق حسابي؟',
      'من الإعدادات ← توثيق الهوية، أكمل الخطوات لرفع حدودك وتفعيل كامل الخدمات.'
    ],
    [
      'كيف أكسب الكاش باك؟',
      'تكسب كاش باك ومكافآت على عملياتك تلقائياً، وتظهر في بطاقة «الكاش باك» بالرئيسية.'
    ],
    [
      'نسيت كيف أستعيد حسابي؟',
      'تواصل مع فريق الدعم عبر المحادثة وسنساعدك في استعادة الوصول بأمان.'
    ],
  ];

  int _openFaq = -1;

  Future<void> _launch(Uri uri) async {
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('تعذّر فتح التطبيق المطلوب'),
          behavior: SnackBarBehavior.floating,
        ));
      }
    }
  }

  void _openChat() => context.push('/chat');

  void _openTickets() => context.push('/support-tickets');

  /// Open a WhatsApp chat with the support bot, prefilled with a greeting so the
  /// conversation starts immediately. Uses the admin-managed number, else fallback.
  void _openWhatsApp(SupportContactModel? contact) {
    final wa = (contact?.whatsapp?.isNotEmpty ?? false)
        ? contact!.whatsapp!
        : _fallbackWa;
    final text = Uri.encodeComponent(_waGreeting);
    _launch(Uri.parse('https://wa.me/${_digits(wa)}?text=$text'));
  }

  /// Open the Telegram support bot (@SakkSupportBot). Uses the admin-managed
  /// handle when set, else the fallback bot username.
  void _openTelegramBot(SupportContactModel? contact) {
    final tg = (contact?.telegram?.isNotEmpty ?? false)
        ? contact!.telegram!
        : _fallbackTelegram;
    _launch(Uri.parse(_telegramUrl(tg)));
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final contact = ref.watch(supportContactProvider).valueOrNull;

    return AppScaffold(
      title: 'المساعدة والدعم',
      subtitle: 'فريقنا هنا لمساعدتك',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.xl, AppSpacing.lg, AppSpacing.xl, AppSpacing.xxxl),
        children: [
          _chatHero(contact).animate().fadeIn(duration: 320.ms).slideY(begin: 0.08),
          const SizedBox(height: AppSpacing.md),
          _telegramBotCard(contact, colors)
              .animate()
              .fadeIn(duration: 320.ms, delay: 80.ms)
              .slideY(begin: 0.08),
          const SizedBox(height: AppSpacing.lg),
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text('تواصل سريع',
                style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
          ),
          const SizedBox(height: AppSpacing.md),
          _channels(contact, colors),
          const SizedBox(height: AppSpacing.xl),
          _ticketCard(),
          if (contact?.faqUrl != null && contact!.faqUrl!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.md),
            _faqLink(contact.faqUrl!, colors),
          ],
          const SizedBox(height: AppSpacing.xl),
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text('الأسئلة الشائعة',
                style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
          ),
          const SizedBox(height: AppSpacing.md),
          ..._faqs.asMap().entries.map((e) => _faqTile(e.key, e.value[0], e.value[1])),
        ],
      ),
    );
  }

  Widget _chatHero(SupportContactModel? contact) {
    final colors = context.appColors;
    final subtitle = (contact?.hours.isNotEmpty ?? false)
        ? contact!.hours
        : 'متاحون لمساعدتك في أقرب وقت';
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: colors.cardGradientVisa,
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
        ),
        borderRadius: BorderRadius.circular(AppRadius.xl),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.25),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.16), shape: BoxShape.circle),
            child: const Icon(Iconsax.messages_2, color: Colors.white, size: 30),
          ),
          const SizedBox(height: AppSpacing.md),
          const Text('دردش مع فريق الدعم',
              style: TextStyle(
                  color: Colors.white,
                  fontSize: 17,
                  fontWeight: FontWeight.w800)),
          const SizedBox(height: 4),
          Text(subtitle,
              textAlign: TextAlign.center,
              style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.85), fontSize: 12.5)),
          const SizedBox(height: AppSpacing.lg),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _openChat,
              icon: const Icon(Iconsax.message_text, size: 18),
              label: const Text('ابدأ المحادثة',
                  style: TextStyle(fontWeight: FontWeight.w700)),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: colors.cardGradientVisa.last,
                elevation: 0,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(AppRadius.md)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Quick-contact channels as separate tappable cards (WhatsApp · email ·
  /// call). Telegram is omitted here — it's the prominent support-bot card
  /// above — so this row stays focused on the lighter channels.
  Widget _channels(SupportContactModel? contact, AppColorsTheme colors) {
    final email = (contact?.email?.isNotEmpty ?? false)
        ? contact!.email!
        : _fallbackEmail;
    final phone = (contact?.phone?.isNotEmpty ?? false)
        ? contact!.phone!
        : _fallbackPhone;

    final tiles = <Widget>[
      _channel(Iconsax.message, 'واتساب', colors.success,
          () => _openWhatsApp(contact)),
      _channel(Iconsax.sms, 'بريد', colors.primary,
          () => _launch(Uri.parse('mailto:$email'))),
      _channel(Iconsax.call, 'اتصال', colors.info,
          () => _launch(Uri.parse('tel:${phone.replaceAll(' ', '')}'))),
    ];

    return Row(
      children: [
        for (var i = 0; i < tiles.length; i++) ...[
          if (i > 0) const SizedBox(width: AppSpacing.md),
          tiles[i],
        ],
      ],
    );
  }

  String _digits(String s) => s.replaceAll(RegExp(r'[^0-9]'), '');

  /// Prominent "Telegram support bot" CTA — taps straight into a chat with the
  /// @SakkSupportBot Telegram bot, our two-way bridge to the ticket desk.
  Widget _telegramBotCard(SupportContactModel? contact, AppColorsTheme colors) {
    const tgBlue = Color(0xFF229ED9);
    return GestureDetector(
      onTap: () => _openTelegramBot(contact),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          color: tgBlue.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(AppRadius.lg),
          border: Border.all(color: tgBlue.withValues(alpha: 0.25)),
        ),
        child: Row(children: [
          Container(
            width: 46,
            height: 46,
            decoration:
                const BoxDecoration(color: tgBlue, shape: BoxShape.circle),
            child: const Icon(Iconsax.send_2, color: Colors.white, size: 24),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('بوت تيليجرام للدعم',
                      style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: colors.textPrimary)),
                  const SizedBox(height: 2),
                  Text('ابدأ محادثة فورية عبر تيليجرام',
                      style:
                          TextStyle(fontSize: 11.5, color: colors.textSecondary)),
                ]),
          ),
          const SizedBox(width: AppSpacing.sm),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: 8),
            decoration: BoxDecoration(
                color: tgBlue,
                borderRadius: BorderRadius.circular(AppRadius.pill)),
            child: const Text('تواصل',
                style: TextStyle(
                    color: Colors.white,
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700)),
          ),
        ]),
      ),
    );
  }

  String _telegramUrl(String handle) {
    if (handle.startsWith('http')) return handle;
    return 'https://t.me/${handle.replaceAll('@', '')}';
  }

  /// One quick-contact card: tinted icon + label, tappable, sits in a row of
  /// equal-width tiles.
  Widget _channel(IconData icon, String label, Color color, VoidCallback onTap) {
    final colors = context.appColors;
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: BorderRadius.circular(AppRadius.lg),
            border: Border.all(color: colors.inputBackground),
          ),
          child: Column(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(13)),
                child: Icon(icon, color: color, size: 22),
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(label,
                  style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _ticketCard() {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return GestureDetector(
      onTap: _openTickets,
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          color: colors.primaryLight,
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        child: Row(children: [
          Icon(Iconsax.ticket, color: colors.primary, size: 24),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('تذاكر الدعم',
                  style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary)),
              const SizedBox(height: 2),
              Text('افتح تذكرة جديدة أو تابع تذاكرك السابقة',
                  style: TextStyle(fontSize: 11.5, color: colors.textSecondary)),
            ]),
          ),
          const SizedBox(width: AppSpacing.sm),
          Container(
            padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.md, vertical: 8),
            decoration: BoxDecoration(
                color: isDark ? colors.surface : colors.primary,
                borderRadius: BorderRadius.circular(AppRadius.pill)),
            child: Text('فتح',
                style: TextStyle(
                    color: isDark ? colors.textPrimary : Colors.white,
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700)),
          ),
        ]),
      ),
    );
  }

  Widget _faqLink(String url, AppColorsTheme colors) {
    return GestureDetector(
      onTap: () => _launch(Uri.parse(url)),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          border: Border.all(color: colors.inputBackground),
        ),
        child: Row(children: [
          Icon(Iconsax.document_text, color: colors.info, size: 22),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text('مركز المساعدة الكامل',
                style: TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
          ),
          Icon(Iconsax.arrow_left_2, size: 18, color: colors.textHint),
        ]),
      ),
    );
  }

  Widget _faqTile(int index, String q, String a) {
    final colors = context.appColors;
    final open = _openFaq == index;
    return Container(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(
            color: open ? colors.primary.withValues(alpha: 0.3) : colors.inputBackground),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          InkWell(
            onTap: () => setState(() => _openFaq = open ? -1 : index),
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.md),
              child: Row(children: [
                Expanded(
                  child: Text(q,
                      style: TextStyle(
                          fontSize: 13.5,
                          fontWeight: FontWeight.w700,
                          color: colors.textPrimary)),
                ),
                AnimatedRotation(
                  turns: open ? 0.5 : 0,
                  duration: const Duration(milliseconds: 200),
                  child: Icon(Iconsax.arrow_down_1,
                      size: 18,
                      color: open ? colors.primary : colors.textHint),
                ),
              ]),
            ),
          ),
          AnimatedCrossFade(
            firstChild: const SizedBox(width: double.infinity, height: 0),
            secondChild: Padding(
              padding: const EdgeInsets.fromLTRB(
                  AppSpacing.md, 0, AppSpacing.md, AppSpacing.md),
              child: Align(
                alignment: AlignmentDirectional.centerStart,
                child: Text(a,
                    style: TextStyle(
                        fontSize: 13,
                        color: colors.textSecondary,
                        height: 1.7)),
              ),
            ),
            crossFadeState:
                open ? CrossFadeState.showSecond : CrossFadeState.showFirst,
            duration: const Duration(milliseconds: 220),
          ),
        ],
      ),
    );
  }
}
