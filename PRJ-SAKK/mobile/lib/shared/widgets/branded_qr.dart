import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../core/theme/app_colors.dart';

/// A polished, branded QR card used across the app (receive, request money…).
/// Uses the modern qr_flutter styling API (eye/data module colors) and a
/// graceful error state, so it always renders reliably.
class BrandedQr extends StatelessWidget {
  final String data;
  final double size;
  final String? caption;

  const BrandedQr({super.key, required this.data, this.size = 220, this.caption});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(28),
            border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
            boxShadow: [
              BoxShadow(color: AppColors.primary.withValues(alpha: 0.10), blurRadius: 28, offset: const Offset(0, 12)),
            ],
          ),
          child: Stack(
            alignment: Alignment.center,
            children: [
              QrImageView(
                data: data,
                size: size,
                padding: EdgeInsets.zero,
                backgroundColor: Colors.white,
                errorCorrectionLevel: QrErrorCorrectLevel.H,
                eyeStyle: const QrEyeStyle(eyeShape: QrEyeShape.circle, color: AppColors.primary),
                dataModuleStyle: const QrDataModuleStyle(
                  dataModuleShape: QrDataModuleShape.circle,
                  color: Color(0xFF1E1B4B),
                ),
                errorStateBuilder: (context, error) => SizedBox(
                  width: size,
                  height: size,
                  child: const Center(
                    child: Text('تعذّر إنشاء الرمز', style: TextStyle(color: AppColors.textSecondary)),
                  ),
                ),
              ),
              // Center brand chip (kept small; high EC level keeps it scannable).
              Container(
                width: size * 0.18,
                height: size * 0.18,
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  border: Border.all(color: AppColors.primary.withValues(alpha: 0.15), width: 2),
                ),
                alignment: Alignment.center,
                child: Text(
                  'صكّ',
                  style: TextStyle(
                    fontSize: size * 0.07,
                    fontWeight: FontWeight.w900,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ],
          ),
        ),
        if (caption != null) ...[
          const SizedBox(height: 14),
          Text(
            caption!,
            textDirection: TextDirection.ltr,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              fontFamily: 'monospace',
              letterSpacing: 2,
              color: colors.textPrimary,
            ),
          ),
        ],
      ],
    );
  }
}
