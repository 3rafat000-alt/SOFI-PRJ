import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';

/// Bill categories data
class _BillCategory {
  final IconData icon;
  final String name;
  final Color color;
  final String? note;

  const _BillCategory({
    required this.icon,
    required this.name,
    required this.color,
    this.note,
  });
}

const _categories = [
  _BillCategory(
    icon: Iconsax.flash,
    name: 'الكهرباء',
    color: Color(0xFFF5A623),
    note: 'فواتير الكهرباء',
  ),
  _BillCategory(
    icon: Iconsax.global,
    name: 'الإنترنت',
    color: Color(0xFF4A90D9),
    note: 'اشتراك الإنترنت',
  ),
  _BillCategory(
    icon: Iconsax.mobile,
    name: 'الجوال',
    color: Color(0xFF2ECC71),
    note: 'تعبئة رصيد',
  ),
  _BillCategory(
    icon: Iconsax.call,
    name: 'الهاتف الأرضي',
    color: Color(0xFF9B59B6),
    note: 'فواتير الهاتف',
  ),
  _BillCategory(
    icon: Iconsax.drop,
    name: 'المياه',
    color: Color(0xFF3498DB),
    note: 'فواتير المياه',
  ),
  _BillCategory(
    icon: Iconsax.buildings,
    name: 'العقارات',
    color: Color(0xFFE74C3C),
    note: 'إيجار وأقساط',
  ),
  _BillCategory(
    icon: Iconsax.ticket,
    name: 'مخالفات',
    color: Color(0xFFE67E22),
    note: 'مخالفات السير',
  ),
  _BillCategory(
    icon: Iconsax.more,
    name: 'أخرى',
    color: Color(0xFF95A5A6),
    note: 'خدمات أخرى',
  ),
];

/// Bills page — pay electricity, internet, mobile, etc.
class BillsPage extends ConsumerWidget {
  const BillsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    return AppScaffold(
      title: 'دفع الفواتير',
      subtitle: 'اختر نوع الفاتورة',
      body: Padding(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.xl, AppSpacing.lg, AppSpacing.xl, AppSpacing.xxxl),
        child: GridView.builder(
          padding: EdgeInsets.zero,
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 4,
            mainAxisSpacing: 16,
            crossAxisSpacing: 4,
            childAspectRatio: 0.8,
          ),
          itemCount: _categories.length,
          itemBuilder: (context, index) {
            final cat = _categories[index];
            return _BillCard(category: cat, colors: colors);
          },
        ),
      ),
    );
  }
}

class _BillCard extends StatelessWidget {
  final _BillCategory category;
  final AppColorsTheme colors;

  const _BillCard({required this.category, required this.colors});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('الدفع لـ ${category.name} — قريباً'),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            duration: const Duration(seconds: 2),
          ),
        );
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: category.color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(category.icon, color: category.color, size: 26),
          ),
          const SizedBox(height: 8),
          Text(
            category.name,
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: colors.textPrimary,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
          if (category.note != null) ...[
            const SizedBox(height: 2),
            Text(
              category.note!,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: colors.textHint,
                fontSize: 9,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
