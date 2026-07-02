/// Converts an Arabic personal name to a professional Latin (English)
/// representation for card display (cardholder names are printed in Latin).
///
/// Strategy:
///  1. If the input has no Arabic letters, it's already Latin → uppercase it.
///  2. Common Arabic names use a polished, human spelling (MOHAMMAD, AHMAD…).
///  3. Any other word falls back to letter-by-letter transliteration.
String latinizeName(String input) {
  final raw = input.trim();
  if (raw.isEmpty) return '';

  // Already Latin (no Arabic block characters) → just uppercase.
  final hasArabic = RegExp(r'[\u0600-\u06FF]').hasMatch(raw);
  if (!hasArabic) return raw.toUpperCase();

  // Polished spellings for the most common Arabic names.
  const common = <String, String>{
    'محمد': 'MOHAMMAD', 'أحمد': 'AHMAD', 'احمد': 'AHMAD', 'محمود': 'MAHMOUD',
    'علي': 'ALI', 'عمر': 'OMAR', 'خالد': 'KHALED', 'عبدالله': 'ABDULLAH',
    'عبد': 'ABD', 'الله': 'ALLAH', 'حسن': 'HASSAN', 'حسين': 'HUSSEIN',
    'إبراهيم': 'IBRAHIM', 'ابراهيم': 'IBRAHIM', 'يوسف': 'YOUSEF',
    'عبدالرحمن': 'ABDULRAHMAN', 'عبدالعزيز': 'ABDULAZIZ',
    'عبدالكريم': 'ABDULKAREEM', 'مصطفى': 'MOSTAFA', 'سعيد': 'SAEED',
    'سامي': 'SAMI', 'رامي': 'RAMI', 'كريم': 'KAREEM', 'ياسر': 'YASSER',
    'وليد': 'WALEED', 'ماجد': 'MAJED', 'طارق': 'TAREQ', 'باسل': 'BASEL',
    'نادر': 'NADER', 'فهد': 'FAHAD', 'بدر': 'BADR', 'ناصر': 'NASSER',
    'صالح': 'SALEH', 'سلطان': 'SULTAN', 'عادل': 'ADEL', 'أمين': 'AMEEN',
    'جمال': 'JAMAL', 'كمال': 'KAMAL', 'سليم': 'SALIM', 'زياد': 'ZIAD',
    'هاني': 'HANI', 'فادي': 'FADI', 'إياد': 'EYAD', 'اياد': 'EYAD',
    'سارة': 'SARA', 'ساره': 'SARA', 'فاطمة': 'FATIMA', 'فاطمه': 'FATIMA',
    'مريم': 'MARYAM', 'ليلى': 'LAILA', 'نور': 'NOUR', 'زينب': 'ZAINAB',
    'عائشة': 'AISHA', 'عائشه': 'AISHA', 'خديجة': 'KHADIJA', 'هدى': 'HODA',
    'رنا': 'RANA', 'دانا': 'DANA', 'لينا': 'LINA', 'ريم': 'REEM',
    'سلمى': 'SALMA', 'رؤى': 'RUaA', 'يارا': 'YARA', 'جنى': 'JANA',
  };

  // Strip Arabic diacritics + tatweel.
  String stripMarks(String s) =>
      s.replaceAll(RegExp(r'[\u064B-\u065F\u0670\u0640]'), '');

  const map = <String, String>{
    'ا': 'A', 'أ': 'A', 'إ': 'I', 'آ': 'AA', 'ٱ': 'A',
    'ب': 'B', 'ت': 'T', 'ث': 'TH', 'ج': 'J', 'ح': 'H', 'خ': 'KH',
    'د': 'D', 'ذ': 'TH', 'ر': 'R', 'ز': 'Z', 'س': 'S', 'ش': 'SH',
    'ص': 'S', 'ض': 'D', 'ط': 'T', 'ظ': 'Z', 'ع': 'A', 'غ': 'GH',
    'ف': 'F', 'ق': 'Q', 'ك': 'K', 'ل': 'L', 'م': 'M', 'ن': 'N',
    'ه': 'H', 'و': 'W', 'ي': 'Y', 'ى': 'A', 'ة': 'A', 'ء': '',
    'ئ': 'Y', 'ؤ': 'W',
  };

  String translitWord(String w) {
    final b = StringBuffer();
    for (final ch in w.split('')) {
      if (map.containsKey(ch)) {
        b.write(map[ch]);
      } else if (RegExp(r'[A-Za-z0-9]').hasMatch(ch)) {
        b.write(ch);
      }
    }
    return b.toString().toUpperCase();
  }

  final words = stripMarks(raw).split(RegExp(r'\s+'));
  final out = <String>[];
  for (final w in words) {
    if (w.isEmpty) continue;
    out.add(common[w] ?? translitWord(w));
  }
  return out.where((w) => w.isNotEmpty).join(' ').trim();
}
