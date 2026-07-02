// Admin-managed support contact channels, served by GET /app/support.
// Lets the "تواصل معنا" screen show live channels without an app rebuild.

class SupportContactModel {
  final bool enabled;
  final String hours;
  final String message;
  final String? email;
  final String? phone;
  final String? whatsapp;
  final String? telegram;
  final String? faqUrl;

  const SupportContactModel({
    required this.enabled,
    required this.hours,
    required this.message,
    this.email,
    this.phone,
    this.whatsapp,
    this.telegram,
    this.faqUrl,
  });

  bool get hasAnyChannel =>
      [email, phone, whatsapp, telegram].any((c) => c != null && c.isNotEmpty);

  factory SupportContactModel.fromJson(Map<String, dynamic> json) {
    final channels = (json['channels'] as Map<String, dynamic>?) ?? const {};
    String? pick(String key) {
      final v = channels[key];
      return (v is String && v.isNotEmpty) ? v : null;
    }

    return SupportContactModel(
      enabled: (json['enabled'] ?? true) as bool,
      hours: (json['hours'] ?? '') as String,
      message: (json['message'] ?? '') as String,
      email: pick('email'),
      phone: pick('phone'),
      whatsapp: pick('whatsapp'),
      telegram: pick('telegram'),
      faqUrl: pick('faq_url'),
    );
  }
}
