import 'dart:async';
import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';

class TimerWidget extends StatefulWidget {
  final bool isRunning;
  final Duration elapsed;
  final VoidCallback onStart;
  final VoidCallback onStop;

  const TimerWidget({
    super.key,
    required this.isRunning,
    required this.elapsed,
    required this.onStart,
    required this.onStop,
  });

  @override
  State<TimerWidget> createState() => _TimerWidgetState();
}

class _TimerWidgetState extends State<TimerWidget>
    with SingleTickerProviderStateMixin {
  AnimationController? _pulseController;

  @override
  void initState() {
    super.initState();
    if (widget.isRunning) {
      _startPulse();
    }
  }

  @override
  void didUpdateWidget(TimerWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.isRunning && !oldWidget.isRunning) {
      _startPulse();
    } else if (!widget.isRunning && oldWidget.isRunning) {
      _stopPulse();
    }
  }

  @override
  void dispose() {
    _pulseController?.dispose();
    super.dispose();
  }

  void _startPulse() {
    _pulseController?.dispose();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1000),
    );
    _pulseController!.repeat(reverse: true);
  }

  void _stopPulse() {
    _pulseController?.stop();
    _pulseController?.dispose();
    _pulseController = null;
  }

  String _formatDuration(Duration d) {
    final hours = d.inHours.toString().padLeft(2, '0');
    final minutes = (d.inMinutes % 60).toString().padLeft(2, '0');
    final seconds = (d.inSeconds % 60).toString().padLeft(2, '0');
    return '$hours:$minutes:$seconds';
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Circular timer display
        AnimatedBuilder(
          listenable: _pulseController ?? AlwaysStoppedAnimation(0),
          builder: (context, child) {
            final scale = widget.isRunning && _pulseController != null
                ? 1.0 + (_pulseController!.value * 0.02)
                : 1.0;
            return Transform.scale(
              scale: scale,
              child: child,
            );
          },
          child: Container(
            width: 120,
            height: 120,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: widget.isRunning
                  ? AppColors.primary.withValues(alpha: 0.1)
                  : AppColors.neutral100,
              border: Border.all(
                color: widget.isRunning ? AppColors.primary : AppColors.neutral300,
                width: 4,
              ),
            ),
            child: Center(
              child: Text(
                _formatDuration(widget.elapsed),
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.w700,
                  fontFamily: 'monospace',
                  color: widget.isRunning
                      ? AppColors.primary
                      : AppColors.textPrimary,
                ),
              ),
            ),
          ),
        ),
        const SizedBox(height: AppDimensions.spacing16),

        // Play/Pause button
        SizedBox(
          width: 56,
          height: 56,
          child: FloatingActionButton(
            onPressed: widget.isRunning ? widget.onStop : widget.onStart,
            backgroundColor:
                widget.isRunning ? AppColors.error : AppColors.primary,
            child: Icon(
              widget.isRunning ? Icons.stop : Icons.play_arrow,
              color: Colors.white,
              size: 28,
            ),
          ),
        ),
      ],
    );
  }
}

/// AnimatedBuilder — Flutter built-in since 3.x
class AnimatedBuilder extends AnimatedWidget {
  final Widget Function(BuildContext context, Widget? child) builder;
  final Widget? child;

  const AnimatedBuilder({
    super.key,
    required super.listenable,
    required this.builder,
    this.child,
  });

  @override
  Widget build(BuildContext context) {
    return builder(context, child);
  }
}
