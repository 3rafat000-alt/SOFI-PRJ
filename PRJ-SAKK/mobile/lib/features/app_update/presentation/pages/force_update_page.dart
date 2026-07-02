import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/theme/app_colors.dart';
import '../../data/models/app_update_info.dart';

/// Full-screen, non-dismissable update gate. Shown when the installed build is
/// below the admin-set floor. The only action is to download the new version;
/// the system back button is swallowed so the user cannot reach the app.
class ForceUpdatePage extends StatelessWidget {
  final AppUpdateInfo info;
  const ForceUpdatePage({super.key, required this.info});

  Future<void> _download(BuildContext context) async {
    final url = info.downloadUrl.trim();
    if (url.isEmpty) return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;

    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تعذّر فتح رابط التحميل')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasUrl = info.downloadUrl.trim().isNotEmpty;

    // PopScope(canPop:false) blocks the hardware back button — the gate cannot
    // be dismissed.
    return PopScope(
      canPop: false,
      child: Directionality(
        textDirection: TextDirection.rtl,
        child: Scaffold(
          backgroundColor: AppColors.background,
          body: SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 96,
                      height: 96,
                      decoration: const BoxDecoration(
                        color: AppColors.primaryLight,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.system_update,
                          size: 48, color: AppColors.primary),
                    ),
                    const SizedBox(height: 28),
                    Text(
                      info.title.isNotEmpty ? info.title : 'تحديث مطلوب',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.w900,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      info.message.isNotEmpty
                          ? info.message
                          : 'يتوفّر إصدار جديد من تطبيق صكّ. يرجى التحديث للمتابعة.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 15,
                        height: 1.7,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    if (info.latestVersion.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 14, vertical: 7),
                        decoration: BoxDecoration(
                          color: AppColors.inputBackground,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          'أحدث إصدار: ${info.latestVersion}',
                          style: const TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 36),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: hasUrl ? () => _download(context) : null,
                        style: FilledButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        icon: const Icon(Icons.download_rounded),
                        label: const Text(
                          'تحديث الآن',
                          style: TextStyle(
                              fontSize: 16, fontWeight: FontWeight.w800),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
