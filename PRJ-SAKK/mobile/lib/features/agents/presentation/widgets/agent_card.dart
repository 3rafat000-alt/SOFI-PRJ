import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/agent_model.dart';

/// A rich, tappable agent card: name, withdrawal code (copyable), distance,
/// rating and the supported cash services.
class AgentCard extends StatelessWidget {
  final AgentModel agent;
  final bool selected;
  final VoidCallback onTap;
  final VoidCallback? onLocate;

  const AgentCard({
    super.key,
    required this.agent,
    this.selected = false,
    required this.onTap,
    this.onLocate,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.lg),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: BorderRadius.circular(AppRadius.lg),
            border: Border.all(
              color: selected ? colors.primary : colors.inputBackground,
              width: selected ? 1.6 : 1,
            ),
            boxShadow: [
              BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10, offset: const Offset(0, 4)),
            ],
          ),
          child: Column(
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Brand avatar
                  Container(
                    width: 52,
                    height: 52,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(colors: colors.cardGradientVisa),
                      borderRadius: BorderRadius.circular(15),
                    ),
                    child: const Icon(Iconsax.shop, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Flexible(
                              child: Text(
                                agent.name,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
                                  color: colors.textPrimary,
                                ),
                              ),
                            ),
                            if (agent.isVerified) ...[
                              const SizedBox(width: 4),
                              Icon(Iconsax.verify5, size: 15, color: colors.info),
                            ],
                          ],
                        ),
                        const SizedBox(height: 3),
                        Row(
                          children: [
                            Icon(Iconsax.location, size: 13, color: colors.textHint),
                            const SizedBox(width: 4),
                            Expanded(
                              child: Text(
                                '${agent.address}، ${agent.city}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(fontSize: 12, color: colors.textSecondary),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Icon(Iconsax.star1, size: 13, color: colors.warning),
                            const SizedBox(width: 3),
                            Text(
                              agent.rating.toStringAsFixed(1),
                              style: TextStyle(
                                  fontSize: 12, fontWeight: FontWeight.w700, color: colors.textPrimary),
                            ),
                            Text(
                              ' (${agent.reviewsCount})',
                              style: TextStyle(fontSize: 11, color: colors.textHint),
                            ),
                            const SizedBox(width: 10),
                            if (agent.supportsCashOut) _serviceTag('سحب', colors.success, colors),
                            if (agent.supportsCashIn) ...[
                              const SizedBox(width: 6),
                              _serviceTag('إيداع', colors.info, colors),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                  // Distance + locate
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      if (agent.distanceLabel != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: colors.primaryLight,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Iconsax.routing, size: 12, color: colors.primary),
                              const SizedBox(width: 3),
                              Text(
                                agent.distanceLabel!,
                                style: TextStyle(
                                    fontSize: 11.5, fontWeight: FontWeight.w700, color: colors.primary),
                              ),
                            ],
                          ),
                        ),
                      if (onLocate != null) ...[
                        const SizedBox(height: 8),
                        GestureDetector(
                          onTap: onLocate,
                          child: Icon(Iconsax.gps, size: 20, color: colors.textHint),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 12),
              // Withdrawal code row (copyable)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                decoration: BoxDecoration(
                  color: colors.inputBackground,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    Icon(Iconsax.barcode, size: 16, color: colors.textSecondary),
                    const SizedBox(width: 8),
                    Text('كود الوكيل:',
                        style: TextStyle(fontSize: 12, color: colors.textSecondary)),
                    const SizedBox(width: 6),
                    Text(
                      agent.agentCode,
                      textDirection: TextDirection.ltr,
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w800,
                        fontFamily: 'monospace',
                        letterSpacing: 1,
                        color: colors.textPrimary,
                      ),
                    ),
                    const Spacer(),
                    GestureDetector(
                      onTap: () {
                        Clipboard.setData(ClipboardData(text: agent.agentCode));
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
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _serviceTag(String label, Color color, AppColorsTheme colors) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: color),
      ),
    );
  }
}
