import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:iconsax/iconsax.dart';

/// Rasterised brand map pins for Google Maps.
///
/// Google Maps markers cannot render Flutter widgets (unlike flutter_map), only
/// [BitmapDescriptor]s — so we paint the brand teardrop pin (gradient head +
/// shop glyph + pointer) to a bitmap once and reuse it. Anchor the pins at the
/// tip (0.5, 1.0) so the point sits on the coordinate.
class AgentMapPins {
  /// Brand teardrop pin. [selected] grows it and switches the border to accent.
  static Future<BitmapDescriptor> agentPin({
    required List<Color> gradient,
    required Color border,
    required bool selected,
    required double dpr,
  }) async {
    final head = selected ? 48.0 : 38.0;
    final borderW = selected ? 3.0 : 2.5;
    final tip = head * 0.34;
    final w = head;
    final h = head + tip;

    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    canvas.scale(dpr);

    final cx = w / 2;
    final cy = head / 2;
    final r = head / 2 - borderW / 2;
    final colors = gradient.length >= 2 ? gradient : [gradient.first, gradient.first];

    // Soft shadow under the head.
    canvas.drawCircle(
      Offset(cx, cy + 2),
      r,
      Paint()
        ..color = Colors.black.withValues(alpha: 0.30)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 3),
    );

    // Pointer (teardrop tip).
    final tipPath = Path()
      ..moveTo(cx - r * 0.5, cy + r * 0.5)
      ..lineTo(cx + r * 0.5, cy + r * 0.5)
      ..lineTo(cx, h - borderW)
      ..close();
    canvas.drawPath(tipPath, Paint()..color = colors.last);
    canvas.drawPath(
      tipPath,
      Paint()
        ..color = border
        ..style = PaintingStyle.stroke
        ..strokeWidth = borderW,
    );

    // Head circle with brand gradient.
    final rect = Rect.fromCircle(center: Offset(cx, cy), radius: r);
    canvas.drawCircle(
      Offset(cx, cy),
      r,
      Paint()..shader = ui.Gradient.linear(rect.topLeft, rect.bottomRight, colors),
    );
    canvas.drawCircle(
      Offset(cx, cy),
      r,
      Paint()
        ..color = border
        ..style = PaintingStyle.stroke
        ..strokeWidth = borderW,
    );

    // Shop glyph (Iconsax icon font) centred in the head.
    const glyph = Iconsax.shop;
    final tp = TextPainter(textDirection: TextDirection.ltr)
      ..text = TextSpan(
        text: String.fromCharCode(glyph.codePoint),
        style: TextStyle(
          fontFamily: glyph.fontFamily,
          package: glyph.fontPackage,
          fontSize: selected ? 24.0 : 18.0,
          color: Colors.white,
        ),
      )
      ..layout();
    tp.paint(canvas, Offset(cx - tp.width / 2, cy - tp.height / 2));

    return _toBitmap(recorder, w, h, dpr);
  }

  /// The pulsing blue "you are here" dot.
  static Future<BitmapDescriptor> userDot({required double dpr}) async {
    const d = 26.0;
    const blue = Color(0xFF2563EB);

    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    canvas.scale(dpr);

    final c = Offset(d / 2, d / 2);
    final r = d / 2 - 3;
    canvas.drawCircle(
      c,
      r + 2,
      Paint()
        ..color = blue.withValues(alpha: 0.5)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 4),
    );
    canvas.drawCircle(c, r, Paint()..color = blue);
    canvas.drawCircle(
      c,
      r,
      Paint()
        ..color = Colors.white
        ..style = PaintingStyle.stroke
        ..strokeWidth = 3,
    );

    return _toBitmap(recorder, d, d, dpr);
  }

  static Future<BitmapDescriptor> _toBitmap(
      ui.PictureRecorder recorder, double w, double h, double dpr) async {
    final img = await recorder.endRecording().toImage((w * dpr).round(), (h * dpr).round());
    final bytes = await img.toByteData(format: ui.ImageByteFormat.png);
    return BitmapDescriptor.bytes(
      bytes!.buffer.asUint8List(),
      imagePixelRatio: dpr,
    );
  }
}
