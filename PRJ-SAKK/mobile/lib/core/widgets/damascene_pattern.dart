import 'dart:math' as math;
import 'package:flutter/material.dart';

/// زخرفة دمشقية راقية — ميدالية واحدة (ختم) بنجمة ثمانية ومثمّنات متداخلة،
/// مرسومة كخطوط رفيعة نظيفة (vector line-art) بدل التكرار الشبكي المزعج.
/// تُرسم مرة واحدة، عادةً نازفة عن إحدى الحواف لتبدو كختم شامي أنيق.
class DamasceneMedallionPainter extends CustomPainter {
  final Color color;
  final double opacity;
  final double strokeWidth;
  final Alignment alignment; // مركز الميدالية داخل المساحة
  final double? radius; // نصف القطر — يُحسب من المساحة افتراضياً

  const DamasceneMedallionPainter({
    this.color = const Color(0xFFD9B978),
    this.opacity = 0.16,
    this.strokeWidth = 1.1,
    this.alignment = const Alignment(1.15, -0.05),
    this.radius,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final p = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeJoin = StrokeJoin.round
      ..isAntiAlias = true
      ..color = color.withValues(alpha: opacity);

    final center = alignment.alongSize(size);
    final r = radius ?? size.shortestSide * 0.62;

    // نجمة ثمانية = مربعان متقاطعان داخل مثمّن، ومثمّنات متداخلة (ختم دمشقي)
    _poly(canvas, center, r, 8, math.pi / 8, p); // مثمّن خارجي
    _poly(canvas, center, r * 0.97, 4, 0, p); // مربع
    _poly(canvas, center, r * 0.97, 4, math.pi / 4, p); // معيّن → نجمة ثمانية
    canvas.drawCircle(center, r * 0.64, p); // حلقة رفيعة
    _poly(canvas, center, r * 0.5, 8, 0, p); // مثمّن أوسط
    _poly(canvas, center, r * 0.46, 4, math.pi / 8, p);
    _poly(canvas, center, r * 0.46, 4, math.pi / 8 + math.pi / 4, p); // نجمة داخلية
    _poly(canvas, center, r * 0.18, 8, math.pi / 8, p); // مثمّن صغير (البحرة)
  }

  void _poly(Canvas canvas, Offset c, double r, int sides, double rot, Paint paint) {
    final path = Path();
    for (int i = 0; i < sides; i++) {
      final a = (2 * math.pi / sides) * i + rot;
      final pt = Offset(c.dx + r * math.cos(a), c.dy + r * math.sin(a));
      if (i == 0) {
        path.moveTo(pt.dx, pt.dy);
      } else {
        path.lineTo(pt.dx, pt.dy);
      }
    }
    path.close();
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant DamasceneMedallionPainter old) =>
      old.color != color ||
      old.opacity != opacity ||
      old.alignment != alignment ||
      old.radius != radius ||
      old.strokeWidth != strokeWidth;
}

/// علامة مائية جاهزة (ميدالية واحدة) للوضع داخل [Stack] على البطاقات.
/// تُقصّ على نصف القطر وتتجاهل اللمس تلقائياً.
class DamasceneWatermark extends StatelessWidget {
  final Color color;
  final double opacity;
  final double radius; // قص الزوايا ليطابق البطاقة
  final Alignment alignment;
  final double? medallionRadius;

  const DamasceneWatermark({
    super.key,
    this.color = const Color(0xFFD9B978),
    this.opacity = 0.16,
    this.radius = 20,
    this.alignment = const Alignment(1.15, -0.05),
    this.medallionRadius,
  });

  @override
  Widget build(BuildContext context) {
    return Positioned.fill(
      child: IgnorePointer(
        child: ClipRRect(
          borderRadius: BorderRadius.circular(radius),
          child: CustomPaint(
            painter: DamasceneMedallionPainter(
              color: color,
              opacity: opacity,
              alignment: alignment,
              radius: medallionRadius,
            ),
          ),
        ),
      ),
    );
  }
}
