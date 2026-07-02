/// Maps an incoming deep link to an in-app route, or null when it isn't one we
/// handle. Pure + synchronous so it can be unit-tested without platform plugins.
///
/// Handles both shapes:
///   • App Links  — https://sakk.zanjour.com/invite/{code} · /pay/{uuid}
///   • Custom URI — sakk://invite/{code} · sakk://pay/{uuid}
String? routeForDeepLink(Uri uri) {
  final segments = uri.pathSegments.where((s) => s.isNotEmpty).toList();

  String? kind;
  String? value;

  if (uri.scheme == 'sakk') {
    // Custom scheme: the host carries the kind, first path segment the value.
    // (sakk://invite/ABC123 → host=invite, segments=[ABC123])
    kind = uri.host;
    value = segments.isNotEmpty ? segments.first : null;
  } else if (uri.scheme == 'https' || uri.scheme == 'http') {
    if (segments.length >= 2) {
      kind = segments[0];
      value = segments[1];
    }
  }

  if (value == null || value.isEmpty) return null;

  switch (kind) {
    case 'invite':
      return '/register?ref=${Uri.encodeComponent(value)}';
    case 'pay':
      return '/pay/$value';
  }
  return null;
}
