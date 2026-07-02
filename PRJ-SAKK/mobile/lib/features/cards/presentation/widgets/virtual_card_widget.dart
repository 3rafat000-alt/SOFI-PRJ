import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/damascene_pattern.dart';
import '../../../../core/utils/arabic_latin.dart';
import '../../../auth/data/repositories/auth_repository.dart';
import '../../data/models/card_model.dart';

/// بطاقة افتراضية موحّدة — تدرّج indigo→violet واحد لكل العلامات
/// (متناسق مع الشاشة الرئيسية، بلا تشتت ألوان). اسم حامل البطاقة يتفاعل
/// مع تغيّر اسم المستخدم الحالي (currentUserProvider).
class VirtualCardWidget extends ConsumerWidget {
  final CardModel card;
  final bool showDetails;
  final CardDetails? cardDetails;

  const VirtualCardWidget({
    super.key,
    required this.card,
    this.showDetails = false,
    this.cardDetails,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bool dimmed = card.isCancelled;
    // Cardholder name reacts to the live user name (updates instantly when the
    // user edits their profile); falls back to the value stored on the card.
    final liveName = ref.watch(currentUserProvider)?.fullName ?? '';
    final holderName =
        liveName.trim().isNotEmpty ? liveName : card.cardholderName;
    final colors = context.appColors;

    return Container(
      width: double.infinity,
      height: 200,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: colors.cardGradientVisa,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.30),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      foregroundDecoration: dimmed
          ? BoxDecoration(
              color: Colors.black.withValues(alpha: 0.25),
              borderRadius: BorderRadius.circular(20),
            )
          : null,
      // Render the card at its design text size regardless of the device's
      // font-scale setting. The card is a fixed-size visual, so any upscaling
      // (plus the tall Cairo line-height) would overflow the 200px height.
      child: MediaQuery.withClampedTextScaling(
        maxScaleFactor: 1.0,
        // A credit card reads left-to-right (number, expiry, brand) regardless
        // of the app's RTL locale — force LTR so the number is never reversed.
        child: Directionality(
        textDirection: TextDirection.ltr,
        child: Stack(
        children: [
          // ختم البحرة الدمشقية — ميدالية ذهبية واحدة نازفة عن الحافة
          const DamasceneWatermark(
            color: Color(0xFFD9B978),
            opacity: 0.18,
            radius: 20,
            alignment: Alignment(1.18, -0.04),
            medallionRadius: 150,
          ),
          // Decorative blobs
          Positioned(
            top: -30,
            right: -30,
            child: _blob(120, 0.10),
          ),
          Positioned(
            bottom: -50,
            left: -20,
            child: _blob(150, 0.08),
          ),
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Top row: brand name + chip
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('صكك',
                            style: TextStyle(
                                color: Colors.white,
                                fontSize: 22,
                                fontWeight: FontWeight.bold)),
                        const SizedBox(height: 2),
                        Text(card.label,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.8),
                                fontSize: 12)),
                      ],
                    ),
                    // Brand mark
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.18),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        card.brand.toUpperCase(),
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 1),
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                // Card number
                Text(
                  showDetails && cardDetails != null
                      ? cardDetails!.formattedNumber
                      : card.maskedNumber,
                  textDirection: TextDirection.ltr,
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 2),
                ),
                if (holderName.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    latinizeName(holderName),
                    textDirection: TextDirection.ltr,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.95),
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1.5,
                    ),
                  ),
                  const SizedBox(height: 8),
                ] else
                  const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Row(
                      children: [
                        _miniField(
                          'صالحة حتى',
                          showDetails && cardDetails != null
                              ? cardDetails!.expiryDate
                              : card.expiryDate,
                        ),
                        if (showDetails && cardDetails != null) ...[
                          const SizedBox(width: 24),
                          _miniField('CVV', cardDetails!.cvv),
                        ],
                      ],
                    ),
                    Text(card.formattedBalance,
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 22,
                            fontWeight: FontWeight.bold)),
                  ],
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

  Widget _miniField(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 10)),
        const SizedBox(height: 2),
        Text(value,
            textDirection: TextDirection.ltr,
            style: const TextStyle(
                color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600)),
      ],
    );
  }

  static Widget _blob(double size, double opacity) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.white.withValues(alpha: opacity),
        ),
      );
}
