import 'package:flutter/material.dart';

import 'app_ui.dart';
import '../theme/app_colors.dart';

// ════════════════════════════════════════════════════════════════════
// نظام الهيكل العظمي (Skeleton) الموحّد — SAKK
//
// قاعدة واحدة لكل شاشات التحميل:
//   1) ذرّات   : SkeletonBox · SkeletonCircle · SkeletonLines
//   2) مكوّنات  : تطابق ودجِت حقيقية (ListTileCard · AppButton ·
//                AppActionButton · SectionHeader · الحقول · بطاقة الرصيد)
//   3) مشاهد   : SkeletonListScene · SkeletonWalletScene ·
//                SkeletonDetailScene · SkeletonFormScene
//
// كل مشهد ملفوف بـ [SakkShimmer] (لمعان متحرك، بلا أي حزمة خارجية،
// يحترم «تقليل الحركة»). الذرّات والمكوّنات تُرسم بلون رمادي ثابت حتى
// يلوّنها الـ shimmer من الأعلى — فتلمع الصفحة كلها بإيقاع واحد.
// أسماء الفئات القديمة محفوظة، فكل الاستيرادات السابقة تعمل بلا تغيير.
// ════════════════════════════════════════════════════════════════════

Color _baseColor(BuildContext c) => c.appColors.inputBackground;
Color _highlightColor(BuildContext c) => Color.lerp(
      c.appColors.inputBackground,
      c.appColors.surface,
      0.65,
    )!;

// ════════════════════════════════════════════════════════════════════
// 0) SakkShimmer — لمعان متحرك يكسو كامل شجرة الهيكل
// ════════════════════════════════════════════════════════════════════
/// يلفّ شجرة هيكل ويمرّر فوقها شريط ضوء متحرك بـ [ShaderMask] واحد
/// (إعادة رسم واحدة على GPU). يسقط تلقائياً إلى ثابت عند تفعيل
/// «تقليل الحركة» أو عند [enabled] = false.
class SakkShimmer extends StatefulWidget {
  final Widget child;
  final bool enabled;
  const SakkShimmer({super.key, required this.child, this.enabled = true});

  @override
  State<SakkShimmer> createState() => _SakkShimmerState();
}

class _SakkShimmerState extends State<SakkShimmer>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    )..repeat();
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final reduceMotion = MediaQuery.maybeOf(context)?.disableAnimations ?? false;
    if (!widget.enabled || reduceMotion) return widget.child;

    final base = _baseColor(context);
    final highlight = _highlightColor(context);

    return AnimatedBuilder(
      animation: _ctrl,
      child: widget.child,
      builder: (context, child) => ShaderMask(
        blendMode: BlendMode.srcATop,
        shaderCallback: (bounds) => LinearGradient(
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
          colors: [base, highlight, base],
          stops: const [0.30, 0.50, 0.70],
          transform: _SlideGradient(_ctrl.value),
        ).createShader(bounds),
        child: child,
      ),
    );
  }
}

/// يحرّك شريط اللمعان أفقياً من خارج اليسار إلى خارج اليمين.
class _SlideGradient extends GradientTransform {
  final double t; // 0 → 1
  const _SlideGradient(this.t);

  @override
  Matrix4? transform(Rect bounds, {TextDirection? textDirection}) =>
      Matrix4.translationValues(bounds.width * (t * 2.0 - 1.0), 0.0, 0.0);
}

// ════════════════════════════════════════════════════════════════════
// 1) الذرّات
// ════════════════════════════════════════════════════════════════════
/// صندوق مدوّر — اللبنة الذرّية لكل هيكل.
class SkeletonBox extends StatelessWidget {
  final double width, height, radius;
  final EdgeInsetsGeometry? margin;

  const SkeletonBox({
    super.key,
    this.width = double.infinity,
    required this.height,
    this.radius = AppRadius.sm,
    this.margin,
  });

  @override
  Widget build(BuildContext context) => Container(
        width: width,
        height: height,
        margin: margin,
        decoration: BoxDecoration(
          color: _baseColor(context),
          borderRadius: BorderRadius.circular(radius),
        ),
      );
}

/// هيكل دائري (الصور الرمزية، حاويات الأيقونات).
class SkeletonCircle extends StatelessWidget {
  final double size;
  const SkeletonCircle({super.key, this.size = 44});

  @override
  Widget build(BuildContext context) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: _baseColor(context),
          shape: BoxShape.circle,
        ),
      );
}

/// أسطر نصّية بعرض متفاوت (آخر سطر 55٪).
class SkeletonLines extends StatelessWidget {
  final int lines;
  final double lineHeight;
  final double lastLineFactor;
  const SkeletonLines({
    super.key,
    this.lines = 2,
    this.lineHeight = 14,
    this.lastLineFactor = 0.55,
  });

  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: List.generate(lines, (i) {
          final w = i == lines - 1 ? lastLineFactor : 1.0;
          return Padding(
            padding: EdgeInsets.only(bottom: i < lines - 1 ? 10 : 0),
            child: FractionallySizedBox(
              alignment: AlignmentDirectional.centerStart,
              widthFactor: w,
              child: Container(
                height: lineHeight,
                decoration: BoxDecoration(
                  color: _baseColor(context),
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
            ),
          );
        }),
      );
}

// ════════════════════════════════════════════════════════════════════
// 2) مكوّنات تطابق ودجِت حقيقية
// ════════════════════════════════════════════════════════════════════
/// هيكل عنوان قسم — يطابق [SectionHeader].
class SkeletonSectionHeader extends StatelessWidget {
  final bool withAction;
  const SkeletonSectionHeader({super.key, this.withAction = false});

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
        child: Row(
          children: [
            const SkeletonBox(width: 120, height: 16, radius: 4),
            const Spacer(),
            if (withAction) const SkeletonBox(width: 48, height: 13, radius: 4),
          ],
        ),
      );
}

/// هيكل زر أساسي — يطابق [AppButton] (ارتفاع 54، نصف قطر md).
class SkeletonButton extends StatelessWidget {
  final bool fullWidth;
  final double width;
  const SkeletonButton({super.key, this.fullWidth = true, this.width = 180});

  @override
  Widget build(BuildContext context) => SkeletonBox(
        width: fullWidth ? double.infinity : width,
        height: 54,
        radius: AppRadius.md,
      );
}

/// عمود أيقونة + تسمية — يطابق [AppActionButton].
class _SkeletonActionItem extends StatelessWidget {
  const _SkeletonActionItem();

  @override
  Widget build(BuildContext context) => Expanded(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
          child: Column(
            children: const [
              SkeletonBox(width: 52, height: 52, radius: AppRadius.lg),
              SizedBox(height: AppSpacing.sm),
              SkeletonBox(width: 44, height: 11, radius: 4),
            ],
          ),
        ),
      );
}

/// صفّ أزرار الإجراءات السريعة — يطابق صفّ [AppActionButton].
class SkeletonActionRow extends StatelessWidget {
  final int count;
  const SkeletonActionRow({super.key, this.count = 4});

  @override
  Widget build(BuildContext context) => Row(
        children: List.generate(count, (_) => const _SkeletonActionItem()),
      );
}

/// هيكل حقل إدخال — تسمية + صندوق إدخال (ارتفاع 54).
class SkeletonField extends StatelessWidget {
  const SkeletonField({super.key});

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: const [
            SkeletonBox(width: 90, height: 12, radius: 4),
            SizedBox(height: AppSpacing.sm),
            SkeletonBox(height: 54, radius: AppRadius.md),
          ],
        ),
      );
}

/// رقاقة/شريحة قابلة للضغط (مرشّحات، عناصر مجزّأة).
class SkeletonChip extends StatelessWidget {
  final double width;
  const SkeletonChip({super.key, this.width = 84});

  @override
  Widget build(BuildContext context) =>
      SkeletonBox(width: width, height: 34, radius: AppRadius.pill);
}

/// هيكل لـ [AppCard] فارغة.
class SkeletonCard extends StatelessWidget {
  final double height;
  final EdgeInsetsGeometry margin;
  const SkeletonCard({
    super.key,
    this.height = 120,
    this.margin = const EdgeInsets.only(bottom: AppSpacing.md),
  });

  @override
  Widget build(BuildContext context) => Padding(
        padding: margin,
        child: Container(
          width: double.infinity,
          height: height,
          decoration: BoxDecoration(
            color: _baseColor(context),
            borderRadius: BorderRadius.circular(AppRadius.lg),
          ),
        ),
      );
}

/// صفّ قائمة واحد — يطابق [ListTileCard]: أيقونة + سطران + قيمة لاحقة.
class SkeletonListItem extends StatelessWidget {
  final EdgeInsetsGeometry margin;
  final bool trailing;
  const SkeletonListItem({
    super.key,
    this.margin = const EdgeInsets.only(bottom: AppSpacing.md),
    this.trailing = true,
  });

  @override
  Widget build(BuildContext context) {
    final color = _baseColor(context);
    return Padding(
      padding: margin,
      child: Container(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.md),
        decoration: BoxDecoration(
          color: context.appColors.surface,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          boxShadow: AppShadows.card,
        ),
        child: Row(
          children: [
            SkeletonCircle(size: 44),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 140,
                    height: 14,
                    decoration: BoxDecoration(
                      color: color,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    width: 90,
                    height: 11,
                    decoration: BoxDecoration(
                      color: color,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ],
              ),
            ),
            if (trailing) ...[
              const SizedBox(width: AppSpacing.sm),
              Container(
                width: 56,
                height: 14,
                decoration: BoxDecoration(
                  color: color,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// هيكل يحاكي بطاقة الرصيد المتدرّجة.
class SkeletonBalanceCard extends StatelessWidget {
  final EdgeInsetsGeometry margin;
  const SkeletonBalanceCard({
    super.key,
    this.margin =
        const EdgeInsets.fromLTRB(AppSpacing.lg, 8, AppSpacing.lg, AppSpacing.md),
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Container(
      height: 200,
      margin: margin,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppRadius.xl),
        gradient: LinearGradient(
          colors: [
            colors.primary.withValues(alpha: 0.18),
            colors.primary.withValues(alpha: 0.06),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: const Padding(
        padding: EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SkeletonBox(width: 100, height: 14, radius: 4),
            SizedBox(height: AppSpacing.lg),
            SkeletonBox(width: 180, height: 34, radius: 6),
            Spacer(),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                SkeletonBox(width: 80, height: 12, radius: 4),
                SkeletonBox(width: 80, height: 12, radius: 4),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// هيكل ودود يشبه الحالة الفارغة (دائرة + نص + زر) لصفحات بلا بطاقات.
class SkeletonEmptyFriendly extends StatelessWidget {
  final double height;
  const SkeletonEmptyFriendly({super.key, this.height = 280});

  @override
  Widget build(BuildContext context) => SakkShimmer(
        child: SizedBox(
          height: height,
          width: double.infinity,
          child: const Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                SkeletonCircle(size: 72),
                SizedBox(height: AppSpacing.lg),
                SkeletonLines(lines: 2, lineHeight: 14),
                SizedBox(height: AppSpacing.xl),
                SkeletonBox(width: 160, height: 48, radius: AppRadius.md),
              ],
            ),
          ),
        ),
      );
}

// ════════════════════════════════════════════════════════════════════
// 3) مشاهد كاملة — تركيب المكوّنات بما يطابق بنية كل نوع صفحة
//    كلها ملفوفة بـ [SakkShimmer] فتلمع كوحدة واحدة.
// ════════════════════════════════════════════════════════════════════

EdgeInsets _scenePad = const EdgeInsets.fromLTRB(
    AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.lg);

/// صفحة قائمة: بطاقات صفوف متكرّرة (المعاملات، الإشعارات، الوكلاء، الأجهزة).
class SkeletonListScene extends StatelessWidget {
  final int items;
  final bool trailing;
  final Widget? header;
  const SkeletonListScene({
    super.key,
    this.items = 6,
    this.trailing = true,
    this.header,
  });

  @override
  Widget build(BuildContext context) => SakkShimmer(
        child: ListView(
          padding: _scenePad,
          physics: const NeverScrollableScrollPhysics(),
          children: [
            if (header != null) header!,
            ...List.generate(items, (_) => SkeletonListItem(trailing: trailing)),
          ],
        ),
      );
}

/// صفحة محفظة/لوحة: بطاقة رصيد + صفّ إجراءات + عنوان قسم + قائمة.
class SkeletonWalletScene extends StatelessWidget {
  final int actions;
  final int items;
  const SkeletonWalletScene({super.key, this.actions = 4, this.items = 4});

  @override
  Widget build(BuildContext context) => SakkShimmer(
        child: ListView(
          padding: const EdgeInsets.only(bottom: AppSpacing.lg),
          physics: const NeverScrollableScrollPhysics(),
          children: [
            const SkeletonBalanceCard(),
            Padding(
              padding:
                  const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: SkeletonActionRow(count: actions),
            ),
            const SizedBox(height: AppSpacing.md),
            Padding(
              padding:
                  const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: Column(
                children: [
                  const SkeletonSectionHeader(withAction: true),
                  ...List.generate(items, (_) => const SkeletonListItem()),
                ],
              ),
            ),
          ],
        ),
      );
}

/// صفحة تفاصيل: بطاقة بطل + أسطر معلومات + قائمة عناصر.
class SkeletonDetailScene extends StatelessWidget {
  final double heroHeight;
  final int infoLines;
  final int items;
  const SkeletonDetailScene({
    super.key,
    this.heroHeight = 200,
    this.infoLines = 3,
    this.items = 3,
  });

  @override
  Widget build(BuildContext context) => SakkShimmer(
        child: ListView(
          padding: _scenePad,
          physics: const NeverScrollableScrollPhysics(),
          children: [
            SkeletonCard(height: heroHeight),
            const SizedBox(height: AppSpacing.sm),
            Padding(
              padding: const EdgeInsets.all(AppSpacing.sm),
              child: SkeletonLines(lines: infoLines, lineHeight: 14),
            ),
            const SizedBox(height: AppSpacing.lg),
            ...List.generate(items, (_) => const SkeletonListItem()),
          ],
        ),
      );
}

/// صفحة نموذج: حقول إدخال + زر أساسي بالأسفل.
class SkeletonFormScene extends StatelessWidget {
  final int fields;
  final bool withButton;
  final Widget? header;
  const SkeletonFormScene({
    super.key,
    this.fields = 4,
    this.withButton = true,
    this.header,
  });

  @override
  Widget build(BuildContext context) => SakkShimmer(
        child: ListView(
          padding: _scenePad,
          physics: const NeverScrollableScrollPhysics(),
          children: [
            if (header != null) ...[
              header!,
              const SizedBox(height: AppSpacing.lg),
            ],
            ...List.generate(fields, (_) => const SkeletonField()),
            if (withButton) ...[
              const SizedBox(height: AppSpacing.sm),
              const SkeletonButton(),
            ],
          ],
        ),
      );
}
