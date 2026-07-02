import 'package:flutter/material.dart';

/// Shared transaction-confirmation PIN prompt.
///
/// Money-moving actions (P2P transfer, pay/accept a payment request, savings,
/// gold, withdraw) now require a second factor server-side (SEC C2/H1). This
/// collects the user's 6-digit PIN and returns it, or `null` if the user
/// cancels. Pass the result as the request's `pin` field.
Future<String?> askTransactionPin(
  BuildContext context, {
  String title = 'تأكيد العملية برمز PIN',
}) {
  final controller = TextEditingController();
  return showDialog<String>(
    context: context,
    builder: (c) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      title: Text(title),
      content: TextField(
        controller: controller,
        keyboardType: TextInputType.number,
        obscureText: true,
        autofocus: true,
        maxLength: 6,
        textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 24, letterSpacing: 8),
        decoration: const InputDecoration(counterText: '', hintText: '••••••'),
        onSubmitted: (v) => Navigator.pop(c, v.trim()),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(c),
          child: const Text('إلغاء'),
        ),
        ElevatedButton(
          onPressed: () => Navigator.pop(c, controller.text.trim()),
          child: const Text('تأكيد'),
        ),
      ],
    ),
  );
}
