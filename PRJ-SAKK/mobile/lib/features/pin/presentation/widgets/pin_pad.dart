import 'package:flutter/material.dart';

import '../../../../core/theme/app_colors.dart';

/// Row of [total] dots showing how many PIN digits are entered.
class PinDots extends StatelessWidget {
  final int filled;
  final int total;
  final Color? color;
  const PinDots({super.key, required this.filled, this.total = 6, this.color});

  @override
  Widget build(BuildContext context) {
    final c = color ?? AppColors.accent;
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(total, (i) {
        final on = i < filled;
        return AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          margin: const EdgeInsets.symmetric(horizontal: 9),
          width: on ? 16 : 13,
          height: on ? 16 : 13,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: on ? c : Colors.transparent,
            border: Border.all(
              color: c.withValues(alpha: on ? 1 : 0.4),
              width: 1.6,
            ),
          ),
        );
      }),
    );
  }
}

/// Numeric keypad (0-9, backspace, optional biometric key).
class PinKeypad extends StatelessWidget {
  final ValueChanged<String> onDigit;
  final VoidCallback onBackspace;
  final VoidCallback? onBiometric;
  final Color textColor;
  const PinKeypad({
    super.key,
    required this.onDigit,
    required this.onBackspace,
    this.onBiometric,
    this.textColor = Colors.white,
  });

  Widget _key(Widget child, VoidCallback? onTap) => Expanded(
        child: AspectRatio(
          aspectRatio: 1.5,
          child: InkResponse(
            onTap: onTap,
            radius: 44,
            child: Center(child: child),
          ),
        ),
      );

  Widget _digit(String d) => _key(
        Text(
          d,
          style: TextStyle(
            fontSize: 28,
            fontWeight: FontWeight.w600,
            color: textColor,
          ),
        ),
        () => onDigit(d),
      );

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(children: [_digit('1'), _digit('2'), _digit('3')]),
        Row(children: [_digit('4'), _digit('5'), _digit('6')]),
        Row(children: [_digit('7'), _digit('8'), _digit('9')]),
        Row(children: [
          onBiometric != null
              ? _key(Icon(Icons.fingerprint, size: 30, color: textColor), onBiometric)
              : _key(const SizedBox.shrink(), null),
          _digit('0'),
          _key(Icon(Icons.backspace_outlined, size: 24, color: textColor), onBackspace),
        ]),
      ],
    );
  }
}
