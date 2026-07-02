import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/support_repository.dart';

/// Open a new support ticket. The description becomes the first message;
/// on success we refresh the list and jump straight into the new thread.
class NewTicketPage extends ConsumerStatefulWidget {
  const NewTicketPage({super.key});

  @override
  ConsumerState<NewTicketPage> createState() => _NewTicketPageState();
}

class _NewTicketPageState extends ConsumerState<NewTicketPage> {
  final _formKey = GlobalKey<FormState>();
  final _subject = TextEditingController();
  final _description = TextEditingController();

  String _category = 'general';
  String _priority = 'medium';
  bool _submitting = false;

  static const _priorities = {
    'low': 'منخفضة',
    'medium': 'متوسطة',
    'high': 'عالية',
    'urgent': 'عاجلة',
  };

  @override
  void dispose() {
    _subject.dispose();
    _description.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _submitting) return;
    setState(() => _submitting = true);
    try {
      final ticket = await ref.read(supportRepositoryProvider).createTicket(
            subject: _subject.text.trim(),
            description: _description.text.trim(),
            category: _category,
            priority: _priority,
          );
      ref.invalidate(ticketsProvider);
      if (!mounted) return;
      // Replace the form with the thread so back returns to the list.
      context.pushReplacement('/support-tickets/${ticket.uuid}');
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(e.message),
        behavior: SnackBarBehavior.floating,
        backgroundColor: context.appColors.error,
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final categories = ref.watch(ticketCategoriesProvider);

    return AppScaffold(
      title: 'تذكرة دعم جديدة',
      subtitle: 'صف مشكلتك وسيردّ عليك الفريق',
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.xxxl),
          children: [
            _label('الموضوع', colors),
            TextFormField(
              controller: _subject,
              maxLength: 160,
              decoration: _dec(colors, 'مثال: مشكلة في عملية تحويل'),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'العنوان مطلوب' : null,
            ),
            const SizedBox(height: AppSpacing.md),
            _label('التصنيف', colors),
            categories.when(
              loading: () => _dropdown(colors, const [
                DropdownMenuItem(value: 'general', child: Text('استفسار عام')),
              ]),
              error: (_, __) => _dropdown(colors, const [
                DropdownMenuItem(value: 'general', child: Text('استفسار عام')),
              ]),
              data: (cats) => _dropdown(
                colors,
                cats
                    .map((c) => DropdownMenuItem(
                        value: c.value, child: Text(c.label)))
                    .toList(),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            _label('الأولوية', colors),
            _priorityChips(colors),
            const SizedBox(height: AppSpacing.md),
            _label('تفاصيل المشكلة', colors),
            TextFormField(
              controller: _description,
              maxLines: 6,
              maxLength: 4000,
              decoration: _dec(colors, 'اشرح المشكلة بالتفصيل…'),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'الوصف مطلوب' : null,
            ),
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              label: 'إرسال التذكرة',
              loading: _submitting,
              onPressed: _submit,
            ),
          ],
        ),
      ),
    );
  }

  Widget _label(String text, AppColorsTheme colors) => Padding(
        padding: const EdgeInsets.only(bottom: AppSpacing.sm),
        child: Text(text,
            style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: colors.textPrimary)),
      );

  InputDecoration _dec(AppColorsTheme colors, String hint) => InputDecoration(
        hintText: hint,
        filled: true,
        fillColor: colors.inputBackground,
        contentPadding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.md, vertical: AppSpacing.md),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide.none,
        ),
      );

  Widget _dropdown(AppColorsTheme colors, List<DropdownMenuItem<String>> items) {
    final values = items.map((e) => e.value).toList();
    final value = values.contains(_category) ? _category : values.first;
    return DropdownButtonFormField<String>(
      initialValue: value,
      isExpanded: true,
      decoration: _dec(colors, ''),
      items: items,
      onChanged: (v) => setState(() => _category = v ?? 'general'),
    );
  }

  Widget _priorityChips(AppColorsTheme colors) {
    return Wrap(
      spacing: AppSpacing.sm,
      children: _priorities.entries.map((e) {
        final selected = _priority == e.key;
        return ChoiceChip(
          label: Text(e.value),
          selected: selected,
          onSelected: (_) => setState(() => _priority = e.key),
          labelStyle: TextStyle(
              fontSize: 12.5,
              fontWeight: FontWeight.w700,
              color: selected ? Colors.white : colors.textSecondary),
          selectedColor: colors.primary,
          backgroundColor: colors.inputBackground,
          showCheckmark: false,
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.pill)),
        );
      }).toList(),
    );
  }
}
