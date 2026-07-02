import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/agent_model.dart';
import '../../data/repositories/agent_repository.dart';
import '../widgets/agent_map_pin.dart';

class AgentDetailsPage extends ConsumerWidget {
  final int agentId;
  final AgentModel? agent;

  const AgentDetailsPage({super.key, required this.agentId, this.agent});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    if (agent != null) return _buildContent(context, ref, agent!, colors);

    final async = ref.watch(agentDetailProvider(agentId));
    return Scaffold(
      backgroundColor: colors.background,
      body: async.when(
        loading: () => Scaffold(
          backgroundColor: colors.background,
          body: const SafeArea(
            child: SkeletonDetailScene(heroHeight: 240, infoLines: 2, items: 3),
          ),
        ),
        error: (_, __) => Center(
          child: EmptyState(
            icon: Iconsax.warning_2,
            title: 'تعذّر تحميل الوكيل',
            actionLabel: 'إعادة المحاولة',
            onAction: () => ref.invalidate(agentDetailProvider(agentId)),
          ),
        ),
        data: (a) => _buildContent(context, ref, a, colors),
      ),
    );
  }

  Widget _buildContent(BuildContext context, WidgetRef ref, AgentModel a, AppColorsTheme colors) {
    final point = LatLng(a.latitude, a.longitude);
    return Scaffold(
      backgroundColor: colors.background,
      body: CustomScrollView(
        slivers: [
          // ───────── Map header ─────────
          SliverToBoxAdapter(
            child: SizedBox(
              height: 240,
              child: Stack(
                children: [
                  _AgentMiniMap(
                    point: point,
                    gradient: colors.cardGradientVisa,
                    accent: colors.accent,
                  ),
                  // gradient scrim for the back button legibility
                  Positioned(
                    top: 0, left: 0, right: 0,
                    child: SafeArea(
                      bottom: false,
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: GestureDetector(
                          onTap: () => context.canPop() ? context.pop() : context.go('/agents'),
                          child: Container(
                            width: 46,
                            height: 46,
                            decoration: BoxDecoration(
                              color: colors.surface,
                              shape: BoxShape.circle,
                              boxShadow: [
                                BoxShadow(color: Colors.black.withValues(alpha: 0.15), blurRadius: 10),
                              ],
                            ),
                            child: Icon(Iconsax.arrow_right_3, color: colors.primary, size: 22),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          SliverToBoxAdapter(
            child: Transform.translate(
              offset: const Offset(0, -24),
              child: Container(
                decoration: BoxDecoration(
                  color: colors.background,
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
                ),
                padding: const EdgeInsets.fromLTRB(20, 22, 20, 40),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _header(a, colors),
                    const SizedBox(height: 20),
                    _withdrawalCodeCard(context, a, colors),
                    const SizedBox(height: 16),
                    _actions(context, a, colors),
                    const SizedBox(height: 24),
                    _infoSection(a, colors),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _header(AgentModel a, AppColorsTheme colors) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                a.name,
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: colors.textPrimary),
              ),
            ),
            if (a.isVerified)
              Icon(Iconsax.verify5, size: 22, color: colors.info),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Icon(Iconsax.star1, size: 16, color: colors.warning),
            const SizedBox(width: 4),
            Text('${a.rating.toStringAsFixed(1)} (${a.reviewsCount})',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: colors.textPrimary)),
            const SizedBox(width: 12),
            if (a.distanceLabel != null) ...[
              Icon(Iconsax.routing, size: 15, color: colors.textSecondary),
              const SizedBox(width: 3),
              Text(a.distanceLabel!, style: TextStyle(fontSize: 13, color: colors.textSecondary)),
              const SizedBox(width: 12),
            ],
            if (a.supportsCashOut) _tag('سحب نقدي', colors.success),
            if (a.supportsCashIn) ...[
              const SizedBox(width: 6),
              _tag('إيداع نقدي', colors.info),
            ],
          ],
        ),
      ],
    );
  }

  // The prominent withdrawal code — the user shows this to the agent.
  Widget _withdrawalCodeCard(BuildContext context, AgentModel a, AppColorsTheme colors) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: colors.cardGradientVisa),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.25), blurRadius: 18, offset: const Offset(0, 8)),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              const Icon(Iconsax.barcode, color: Colors.white, size: 18),
              const SizedBox(width: 8),
              Text('كود الوكيل للسحب',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 13)),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                a.agentCode,
                textDirection: TextDirection.ltr,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 30,
                  fontWeight: FontWeight.w900,
                  fontFamily: 'monospace',
                  letterSpacing: 3,
                ),
              ),
              const SizedBox(width: 12),
              GestureDetector(
                onTap: () {
                  Clipboard.setData(ClipboardData(text: a.agentCode));
                  ScaffoldMessenger.of(context)
                    ..hideCurrentSnackBar()
                    ..showSnackBar(SnackBar(
                      content: const Text('تم نسخ كود الوكيل'),
                      behavior: SnackBarBehavior.floating,
                      backgroundColor: colors.success,
                    ));
                },
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.18),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Iconsax.copy, color: Colors.white, size: 18),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            'اعرض هذا الكود لدى الوكيل لإتمام عملية السحب أو الإيداع النقدي.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 12, height: 1.5),
          ),
        ],
      ),
    );
  }

  Widget _actions(BuildContext context, AgentModel a, AppColorsTheme colors) {
    return Row(
      children: [
        Expanded(
          child: _actionButton(
            icon: Iconsax.routing_2,
            label: 'الاتجاهات',
            filled: true,
            colors: colors,
            onTap: () => _openDirections(a),
          ),
        ),
        if (a.phone != null && a.phone!.isNotEmpty) ...[
          const SizedBox(width: 12),
          Expanded(
            child: _actionButton(
              icon: Iconsax.call,
              label: 'اتصال',
              filled: false,
              colors: colors,
              onTap: () => _call(a.phone!),
            ),
          ),
        ],
      ],
    );
  }

  Widget _actionButton({
    required IconData icon,
    required String label,
    required bool filled,
    required AppColorsTheme colors,
    required VoidCallback onTap,
  }) {
    final bg = filled ? colors.primary : colors.surface;
    final fg = filled ? Colors.white : colors.primary;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 52,
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(14),
          border: filled ? null : Border.all(color: colors.primary.withValues(alpha: 0.4)),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 19, color: fg),
            const SizedBox(width: 8),
            Text(label, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: fg)),
          ],
        ),
      ),
    );
  }

  Widget _infoSection(AgentModel a, AppColorsTheme colors) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: colors.inputBackground),
      ),
      child: Column(
        children: [
          _infoRow(Iconsax.location, 'العنوان', '${a.address}، ${a.city}', colors),
          if (a.workingHours != null)
            _infoRow(Iconsax.clock, 'ساعات العمل', a.workingHours!, colors),
          _infoRow(Iconsax.percentage_square, 'العمولة', '${a.commissionRate.toStringAsFixed(1)}%', colors),
          _infoRow(
            Iconsax.money_recive,
            'حدود المبلغ',
            a.maxAmount != null
                ? '\$${a.minAmount.toStringAsFixed(0)} - \$${a.maxAmount!.toStringAsFixed(0)}'
                : 'من \$${a.minAmount.toStringAsFixed(0)}',
            colors,
          ),
          if (a.ownerName != null)
            _infoRow(Iconsax.user, 'المسؤول', a.ownerName!, colors),
          if (a.phone != null)
            _infoRow(Iconsax.call, 'الهاتف', a.phone!, colors, ltr: true),
          _infoRow(Iconsax.barcode, 'كود الوكيل', a.agentCode, colors, ltr: true, last: true),
        ],
      ),
    );
  }

  Widget _infoRow(IconData icon, String label, String value, AppColorsTheme colors,
      {bool ltr = false, bool last = false}) {
    return Padding(
      padding: EdgeInsets.only(bottom: last ? 0 : 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: colors.primary),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(fontSize: 13, color: colors.textSecondary)),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.end,
              textDirection: ltr ? TextDirection.ltr : null,
              style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: colors.textPrimary),
            ),
          ),
        ],
      ),
    );
  }

  Widget _tag(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: color)),
    );
  }

  Future<void> _openDirections(AgentModel a) async {
    final uri = Uri.parse(
        'https://www.google.com/maps/dir/?api=1&destination=${a.latitude},${a.longitude}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _call(String phone) async {
    final uri = Uri.parse('tel:${phone.replaceAll(RegExp(r'\s+'), '')}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }
}

/// Lightweight Google Map preview in the details header — a single brand pin,
/// lite mode (static image on Android) so it doesn't fight the scroll view.
class _AgentMiniMap extends StatefulWidget {
  final LatLng point;
  final List<Color> gradient;
  final Color accent;

  const _AgentMiniMap({
    required this.point,
    required this.gradient,
    required this.accent,
  });

  @override
  State<_AgentMiniMap> createState() => _AgentMiniMapState();
}

class _AgentMiniMapState extends State<_AgentMiniMap> {
  BitmapDescriptor? _pin;
  bool _requested = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_requested) {
      _requested = true;
      AgentMapPins.agentPin(
        gradient: widget.gradient,
        border: Colors.white,
        selected: false,
        dpr: MediaQuery.of(context).devicePixelRatio,
      ).then((b) {
        if (mounted) setState(() => _pin = b);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return GoogleMap(
      initialCameraPosition: CameraPosition(target: widget.point, zoom: 15),
      liteModeEnabled: true,
      myLocationButtonEnabled: false,
      zoomControlsEnabled: false,
      mapToolbarEnabled: false,
      compassEnabled: false,
      markers: _pin == null
          ? const <Marker>{}
          : {
              Marker(
                markerId: const MarkerId('agent'),
                position: widget.point,
                icon: _pin!,
                anchor: const Offset(0.5, 1.0),
              ),
            },
    );
  }
}
