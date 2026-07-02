import 'package:flutter/material.dart';

import '../../../../core/theme/app_colors.dart';

class ScanPage extends StatelessWidget {
  const ScanPage({super.key});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => Navigator.maybePop(context),
        ),
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(24),
                border: Border.all(
                  color: colors.primary.withValues(alpha: 0.3),
                  width: 2,
                ),
              ),
              child: Center(
                child: Icon(
                  Icons.qr_code_scanner_rounded,
                  size: 100,
                  color: colors.primary,
                ),
              ),
            ),
            const SizedBox(height: 32),
            Text(
              'قم بتوجيه الكاميرا نحو رمز QR',
              style: TextStyle(
                fontSize: 16,
                color: colors.textSecondary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'لإتمام عملية الدفع أو التحويل',
              style: TextStyle(
                fontSize: 13,
                color: colors.textSecondary.withValues(alpha: 0.7),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
