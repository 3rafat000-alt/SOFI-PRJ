import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/services/location_service.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/agent_model.dart';
import '../../data/repositories/agent_repository.dart';
import '../widgets/agent_card.dart';
import '../widgets/agent_map_pin.dart';

/// Nearby cash-agent finder: a Google Map of agents with a draggable list of
/// agent cards, service filters and search. Needs a Google Maps API key
/// (Android: local.properties → manifest; iOS: AppDelegate).
class AgentsPage extends ConsumerStatefulWidget {
  /// Optional preselected service filter: 'cash_in' or 'cash_out'.
  final String? initialService;

  const AgentsPage({super.key, this.initialService});

  @override
  ConsumerState<AgentsPage> createState() => _AgentsPageState();
}

class _AgentsPageState extends ConsumerState<AgentsPage> {
  GoogleMapController? _mapCtrl;
  final TextEditingController _searchCtrl = TextEditingController();

  // Damascus center — used for the initial map view when location is unknown.
  static const LatLng _fallbackCenter = LatLng(33.5138, 36.2765);

  LatLng? _userLocation;
  String? _service; // null = all, 'cash_in', 'cash_out'
  String _query = '';
  int? _selectedId;
  bool _locating = true;

  // Rasterised brand pins (Google Maps can't use Flutter-widget markers).
  BitmapDescriptor? _pinNormal;
  BitmapDescriptor? _pinSelected;
  BitmapDescriptor? _userDot;
  bool _iconsRequested = false;

  @override
  void initState() {
    super.initState();
    _service = widget.initialService;
    _initLocation();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_iconsRequested) {
      _iconsRequested = true;
      _buildIcons(MediaQuery.of(context).devicePixelRatio);
    }
  }

  Future<void> _buildIcons(double dpr) async {
    final colors = context.appColors;
    final results = await Future.wait([
      AgentMapPins.agentPin(gradient: colors.cardGradientVisa, border: Colors.white, selected: false, dpr: dpr),
      AgentMapPins.agentPin(gradient: colors.cardGradientVisa, border: colors.accent, selected: true, dpr: dpr),
      AgentMapPins.userDot(dpr: dpr),
    ]);
    if (!mounted) return;
    setState(() {
      _pinNormal = results[0];
      _pinSelected = results[1];
      _userDot = results[2];
    });
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _mapCtrl?.dispose();
    super.dispose();
  }

  Future<void> _initLocation() async {
    final pos = await LocationService.getCurrent();
    if (!mounted) return;
    setState(() {
      _userLocation = pos == null ? null : LatLng(pos.latitude, pos.longitude);
      _locating = false;
    });
    if (_userLocation != null) {
      _moveCamera(_userLocation!, 13);
    }
  }

  void _moveCamera(LatLng target, double zoom) {
    _mapCtrl?.animateCamera(CameraUpdate.newLatLngZoom(target, zoom));
  }

  AgentQuery get _agentQuery => (
        lat: _userLocation?.latitude,
        lng: _userLocation?.longitude,
        service: _service,
        q: _query.isEmpty ? null : _query,
      );

  void _selectAgent(AgentModel a) {
    setState(() => _selectedId = a.id);
    _moveCamera(LatLng(a.latitude, a.longitude), 15);
  }

  void _onMarkerTap(AgentModel a) {
    _selectAgent(a);
    _showAgentPreview(a);
  }

  Future<void> _openDirections(AgentModel a) async {
    final uri = Uri.parse(
        'https://www.google.com/maps/dir/?api=1&destination=${a.latitude},${a.longitude}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  /// Quick preview shown when a map pin is tapped (premium maps pattern):
  /// compact agent info with copy-code, directions and full-details actions.
  void _showAgentPreview(AgentModel a) {
    final colors = context.appColors;
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        margin: const EdgeInsets.all(12),
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.18), blurRadius: 28, offset: const Offset(0, 10)),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              children: [
                Container(
                  width: 54,
                  height: 54,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(colors: colors.cardGradientVisa),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(Iconsax.shop, color: Colors.white, size: 26),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(children: [
                        Flexible(
                          child: Text(a.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                  fontSize: 16, fontWeight: FontWeight.w800, color: colors.textPrimary)),
                        ),
                        if (a.isVerified) ...[
                          const SizedBox(width: 4),
                          Icon(Iconsax.verify5, size: 16, color: colors.info),
                        ],
                      ]),
                      const SizedBox(height: 4),
                      Row(children: [
                        Icon(Iconsax.star1, size: 13, color: colors.warning),
                        const SizedBox(width: 3),
                        Text(a.rating.toStringAsFixed(1),
                            style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: colors.textPrimary)),
                        if (a.distanceLabel != null) ...[
                          const SizedBox(width: 10),
                          Icon(Iconsax.routing, size: 13, color: colors.textSecondary),
                          const SizedBox(width: 3),
                          Text(a.distanceLabel!, style: TextStyle(fontSize: 12.5, color: colors.textSecondary)),
                        ],
                      ]),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            // Withdrawal code
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: colors.inputBackground,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(children: [
                Icon(Iconsax.barcode, size: 16, color: colors.textSecondary),
                const SizedBox(width: 8),
                Text('كود الوكيل:', style: TextStyle(fontSize: 12, color: colors.textSecondary)),
                const SizedBox(width: 6),
                Text(a.agentCode,
                    textDirection: TextDirection.ltr,
                    style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        fontFamily: 'monospace',
                        letterSpacing: 1,
                        color: colors.textPrimary)),
                const Spacer(),
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
                  child: Icon(Iconsax.copy, size: 17, color: colors.primary),
                ),
              ]),
            ),
            const SizedBox(height: 14),
            Row(children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _openDirections(a),
                  icon: Icon(Iconsax.routing_2, size: 18, color: colors.primary),
                  label: Text('الاتجاهات', style: TextStyle(color: colors.primary, fontWeight: FontWeight.w700)),
                  style: OutlinedButton.styleFrom(
                    side: BorderSide(color: colors.primary.withValues(alpha: 0.4)),
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    context.push('/agents/${a.id}', extra: a);
                  },
                  icon: const Icon(Iconsax.arrow_left_2, size: 18),
                  label: const Text('التفاصيل', style: TextStyle(fontWeight: FontWeight.w700)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: colors.primary,
                    foregroundColor: Theme.of(context).brightness == Brightness.dark
                        ? colors.background
                        : Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
            ]),
          ],
        ),
      ),
    );
  }

  void _recenter() {
    final target = _userLocation ?? _fallbackCenter;
    _moveCamera(target, _userLocation != null ? 14 : 12);
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final agentsAsync = ref.watch(agentsProvider(_agentQuery));
    final agents = agentsAsync.asData?.value ?? const <AgentModel>[];

    return Scaffold(
      backgroundColor: colors.background,
      body: Stack(
        children: [
          // ───────── Map ─────────
          GoogleMap(
            initialCameraPosition: CameraPosition(
              target: _userLocation ?? _fallbackCenter,
              zoom: 12,
            ),
            onMapCreated: (c) => _mapCtrl = c,
            onTap: (_) => setState(() => _selectedId = null),
            markers: _markers(agents),
            myLocationButtonEnabled: false,
            zoomControlsEnabled: false,
            mapToolbarEnabled: false,
            compassEnabled: false,
            minMaxZoomPreference: const MinMaxZoomPreference(4, 19),
          ),

          // ───────── Top floating bar (back + search + filters) ─────────
          _topBar(colors),

          // ───────── My-location FAB ─────────
          Positioned(
            right: 16,
            bottom: MediaQuery.of(context).size.height * 0.42 + 12,
            child: _circleButton(
              icon: _locating ? Iconsax.location : Iconsax.gps,
              onTap: _recenter,
              colors: colors,
              loading: _locating,
            ),
          ),

          // ───────── Draggable list of agents ─────────
          _agentSheet(agentsAsync, agents, colors),
        ],
      ),
    );
  }

  // ───────────────────────── Markers ─────────────────────────
  Set<Marker> _markers(List<AgentModel> agents) {
    if (_pinNormal == null || _pinSelected == null) return const <Marker>{};
    final markers = <Marker>{};

    if (_userLocation != null && _userDot != null) {
      markers.add(Marker(
        markerId: const MarkerId('me'),
        position: _userLocation!,
        icon: _userDot!,
        anchor: const Offset(0.5, 0.5),
        zIndexInt: 1,
      ));
    }

    for (final a in agents) {
      final selected = a.id == _selectedId;
      markers.add(Marker(
        markerId: MarkerId('agent_${a.id}'),
        position: LatLng(a.latitude, a.longitude),
        icon: selected ? _pinSelected! : _pinNormal!,
        anchor: const Offset(0.5, 1.0), // pin tip sits on the coordinate
        zIndexInt: selected ? 3 : 2,
        onTap: () => _onMarkerTap(a),
      ));
    }
    return markers;
  }

  // ───────────────────────── Top bar ─────────────────────────
  Widget _topBar(AppColorsTheme colors) {
    return Positioned(
      top: 0,
      left: 0,
      right: 0,
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
          child: Column(
            children: [
              Row(
                children: [
                  _circleButton(
                    icon: Iconsax.arrow_right_3,
                    onTap: () => context.canPop() ? context.pop() : context.go('/dashboard'),
                    colors: colors,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Container(
                      height: 50,
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      decoration: BoxDecoration(
                        color: colors.surface,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.08),
                            blurRadius: 14,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Row(
                        children: [
                          Icon(Iconsax.search_normal, size: 18, color: colors.textHint),
                          const SizedBox(width: 8),
                          Expanded(
                            child: TextField(
                              controller: _searchCtrl,
                              onChanged: (v) => setState(() => _query = v),
                              style: TextStyle(color: colors.textPrimary, fontSize: 14),
                              decoration: InputDecoration(
                                isDense: true,
                                border: InputBorder.none,
                                hintText: 'ابحث عن وكيل أو مدينة',
                                hintStyle: TextStyle(color: colors.textHint, fontSize: 13.5),
                              ),
                            ),
                          ),
                          if (_query.isNotEmpty)
                            GestureDetector(
                              onTap: () {
                                _searchCtrl.clear();
                                setState(() => _query = '');
                              },
                              child: Icon(Iconsax.close_circle, size: 18, color: colors.textHint),
                            ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  _filterChip('الكل', null, colors),
                  const SizedBox(width: 8),
                  _filterChip('سحب نقدي', 'cash_out', colors),
                  const SizedBox(width: 8),
                  _filterChip('إيداع نقدي', 'cash_in', colors),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _filterChip(String label, String? value, AppColorsTheme colors) {
    final selected = _service == value;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return GestureDetector(
      onTap: () => setState(() => _service = value),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
        decoration: BoxDecoration(
          color: selected ? colors.primary : colors.surface,
          borderRadius: BorderRadius.circular(30),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 8, offset: const Offset(0, 2)),
          ],
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: selected ? (isDark ? colors.background : Colors.white) : colors.textSecondary,
          ),
        ),
      ),
    );
  }

  // ───────────────────────── Bottom sheet (list) ─────────────────────────
  Widget _agentSheet(
    AsyncValue<List<AgentModel>> agentsAsync,
    List<AgentModel> agents,
    AppColorsTheme colors,
  ) {
    return DraggableScrollableSheet(
      initialChildSize: 0.42,
      minChildSize: 0.18,
      maxChildSize: 0.9,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
            boxShadow: [
              BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 24, offset: const Offset(0, -6)),
            ],
          ),
          child: Column(
            children: [
              const SizedBox(height: 10),
              Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: colors.textHint.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(3),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 6),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(Iconsax.shop, size: 20, color: colors.primary),
                              const SizedBox(width: 8),
                              Text(
                                'الوكلاء القريبون',
                                style: TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w800,
                                  color: colors.textPrimary,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 3),
                          Text(
                            'اختر وكيلاً معتمداً للسحب أو الإيداع نقداً',
                            style: TextStyle(fontSize: 12, color: colors.textSecondary),
                          ),
                        ],
                      ),
                    ),
                    if (agentsAsync.isLoading)
                      SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2, color: colors.primary),
                      )
                    else
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: colors.primaryLight,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          '${agents.length} وكيل',
                          style: TextStyle(
                              fontSize: 12.5, fontWeight: FontWeight.w700, color: colors.primary),
                        ),
                      ),
                  ],
                ),
              ),
              if (_userLocation == null && !_locating)
                _locationHint(colors),
              Expanded(
                child: agentsAsync.when(
                  loading: () => agents.isEmpty
                      ? SakkShimmer(
                          child: ListView(
                            controller: scrollController,
                            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                            children: const [
                              SkeletonListItem(),
                              SkeletonListItem(),
                              SkeletonListItem(),
                              SkeletonListItem(),
                              SkeletonListItem(),
                            ],
                          ),
                        )
                      : _list(scrollController, agents, colors),
                  error: (e, _) => _errorState(colors),
                  data: (list) => list.isEmpty
                      ? _emptyState(colors)
                      : _list(scrollController, list, colors),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _list(ScrollController controller, List<AgentModel> agents, AppColorsTheme colors) {
    return ListView.separated(
      controller: controller,
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
      itemCount: agents.length,
      separatorBuilder: (_, __) => const SizedBox(height: 12),
      itemBuilder: (_, i) {
        final a = agents[i];
        return AgentCard(
          agent: a,
          selected: a.id == _selectedId,
          onTap: () => context.push('/agents/${a.id}', extra: a),
          onLocate: () => _selectAgent(a),
        );
      },
    );
  }

  Widget _locationHint(AppColorsTheme colors) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 4, 16, 4),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: colors.warningLight,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(Iconsax.location_slash, size: 18, color: colors.warning),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'فعّل خدمة الموقع لعرض الوكلاء الأقرب إليك وحساب المسافات.',
              style: TextStyle(fontSize: 12.5, color: colors.textSecondary, height: 1.4),
            ),
          ),
          TextButton(
            onPressed: () {
              setState(() => _locating = true);
              _initLocation();
            },
            child: Text('تفعيل', style: TextStyle(color: colors.primary, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }

  Widget _emptyState(AppColorsTheme colors) {
    return EmptyState(
      icon: Iconsax.shop,
      title: 'لا يوجد وكلاء',
      subtitle: 'جرّب تغيير عامل التصفية أو البحث عن مدينة أخرى',
    );
  }

  Widget _errorState(AppColorsTheme colors) {
    return EmptyState(
      icon: Iconsax.warning_2,
      title: 'تعذّر تحميل الوكلاء',
      subtitle: 'تحقّق من اتصالك وحاول مجدداً',
      actionLabel: 'إعادة المحاولة',
      onAction: () => ref.invalidate(agentsProvider(_agentQuery)),
    );
  }

  Widget _circleButton({
    required IconData icon,
    required VoidCallback onTap,
    required AppColorsTheme colors,
    bool loading = false,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 50,
        height: 50,
        decoration: BoxDecoration(
          color: colors.surface,
          shape: BoxShape.circle,
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 12, offset: const Offset(0, 4)),
          ],
        ),
        child: loading
            ? Padding(
                padding: const EdgeInsets.all(15),
                child: CircularProgressIndicator(strokeWidth: 2, color: colors.primary),
              )
            : Icon(icon, color: colors.primary, size: 22),
      ),
    );
  }
}
