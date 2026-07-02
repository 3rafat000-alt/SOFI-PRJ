# Sentry Setup — TaskSync Pro (SAAS-001)

> **Gate:** 8 · **Owner:** Naomi Brooks (Observability SRE) · **Date:** 2026-06-25
> **Purpose:** Error tracking, performance tracing, release monitoring across all platforms

---

## 1. Backend (Laravel 11)

### 1.1 Install

```bash
composer require sentry/laravel
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

### 1.2 Environment Variables

```env
# .env / .env.production
SENTRY_LARAVEL_DSN=https://xxx@sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTLY_PROFILES_SAMPLE_RATE=0.1   # PHP 8.3 JIT profiling
```

### 1.3 Configuration

```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    // Capture release from git
    'release' => trim(exec('git log --pretty="%H" -n1 HEAD')),

    // Performance tracing
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Profiling (PHP 8.3+)
    'profiles_sample_rate' => (float) env('SENTLY_PROFILES_SAMPLE_RATE', 0.1),

    // Send breadcrumbs for Laravel logs
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => false,  // Enable in staging only
        'sql_bindings' => false,
        'queue_info' => true,
        'command_info' => true,
        'http_client_requests' => true,
    ],
];
```

### 1.4 Exception Handler Integration

```php
// bootstrap/app.php
use Sentry\Laravel\Integration;
use Illuminate\Foundation\Configuration\Exceptions;

->withExceptions(function (Exceptions $exceptions) {
    // Sentry integration
    $exceptions->report(function (Throwable $e) {
        if (app()->bound('sentry')) {
            Integration::captureUnhandledException($e);
        }
    });

    // Don't report HTTP client 4xx as exceptions
    $exceptions->dontReport([
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class,
        Illuminate\Validation\ValidationException::class,
        Illuminate\Auth\AuthenticationException::class,
    ]);
});
```

### 1.5 Console & Queue Monitoring

```php
// config/sentry.php — auto for Laravel 11
// Horizon failed jobs captured automatically
// Artisan command failures captured automatically
```

---

## 2. Frontend (Vue 3 + Vite)

### 2.1 Install

```bash
npm install @sentry/vue @sentry/vite-plugin
```

### 2.2 Vite Config

```js
// vite.config.js
import { sentryVitePlugin } from "@sentry/vite-plugin";

export default defineConfig({
  plugins: [
    vue(),
    sentryVitePlugin({
      org: "tasksyncpro",
      project: "dashboard",
      authToken: process.env.SENTRY_AUTH_TOKEN,
      // Upload source maps (release matching)
      release: { name: process.env.npm_package_version },
      sourcemaps: { assets: ['./dist/assets/**'] },
    }),
  ],
  build: {
    sourcemap: true,  // Sentry needs source maps
  },
});
```

### 2.3 Init

```js
// src/main.js
import * as Sentry from "@sentry/vue";
import { createRouter } from "vue-router";

const app = createApp(App);
const router = createRouter({...});

Sentry.init({
  app,
  dsn: import.meta.env.VITE_SENTRY_DSN,
  environment: import.meta.env.MODE,
  release: import.meta.env.VITE_APP_VERSION,

  // Performance
  integrations: [
    Sentry.browserTracingIntegration({ router }),
    Sentry.replayIntegration({
      maskAllText: false,       // Show Arabic text
      blockAllMedia: false,     // Allow avatar display
      maskAllInputs: true,      // Mask password/forms
    }),
  ],
  tracesSampleRate: 0.2,
  replaysSessionSampleRate: 0.1,     // 10% of sessions
  replaysOnErrorSampleRate: 1.0,     // 100% on error

  // Vue error handler
  errorHandler: (err, instance, info) => {
    Sentry.captureException(err, {
      contexts: { vue: { component: instance?.$options?.name, info } },
    });
  },
});

app.use(router);
app.mount("#app");
```

### 2.4 Navigation Tracking (router.beforeEach)

```js
// src/router/index.js
import * as Sentry from "@sentry/vue";

export const router = createRouter({
  // ... routes
});

router.beforeEach((to, from, next) => {
  // Sentry automatically tracks navigation via browserTracingIntegration
  // Add custom breadcrumb for RTL locale tracking
  Sentry.addBreadcrumb({
    category: 'navigation',
    message: `Route: ${from.path} → ${to.path}`,
    level: 'info',
    data: { locale: to.params.locale || 'en' },
  });
  next();
});
```

---

## 3. Mobile (Flutter)

### 3.1 Install

```yaml
# pubspec.yaml
dependencies:
  sentry_flutter: ^8.0.0
```

### 3.2 Init

```dart
// lib/main.dart
import 'package:sentry_flutter/sentry_flutter.dart';

Future<void> main() async {
  await SentryFlutter.init(
    (options) {
      options.dsn = const String.fromEnvironment('SENTRY_DSN');
      options.environment = const String.fromEnvironment('APP_ENV');
      options.release = const String.fromEnvironment('APP_VERSION');
      options.enableOutOfMemoryTracking = true;
      options.tracesSampleRate = 0.2;  // matches frontend
      options.enableAppHangTracking = true;
      options.appHangTimeoutInterval = Duration(seconds: 5);
      options.attachScreenshot = true;    // capture screenshot on error
      options.attachViewHierarchy = true; // widget tree on error
      options.maxBreadcrumbs = 100;

      // Ignore network errors from offline mode
      options.ignoreErrorsForType.add('DioException');
    },
    // Run app after init
    appRunner: () => runApp(const TaskSyncApp()),
  );
}
```

### 3.3 Bloc Error Tracking

```dart
// lib/core/bloc_observer.dart
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

class SentryBlocObserver extends BlocObserver {
  @override
  void onError(BlocBase bloc, Object error, StackTrace stackTrace) {
    Sentry.captureException(
      error,
      stackTrace: stackTrace,
      hint: Hint.withMap({'bloc': bloc.runtimeType.toString()}),
    );
    super.onError(bloc, error, stackTrace);
  }
}

// Register in main.dart after SentryFlutter.init
Bloc.observer = SentryBlocObserver();
```

---

## 4. Release Tracking

### 4.1 Associate Releases

```bash
# Backend
sentry-cli releases new -p tasksync-backend $APP_VERSION
sentry-cli releases set-commits -p tasksync-backend --auto

# Frontend
sentry-cli releases new -p tasksync-dashboard $APP_VERSION
sentry-cli releases set-commits -p tasksync-dashboard --auto

# Mobile
sentry-cli releases new -p tasksync-mobile $APP_VERSION
sentry-cli releases set-commits -p tasksync-mobile --auto
```

### 4.2 Deploy Tracking

```bash
sentry-cli releases deploys $APP_VERSION new -e production
```

---

## 5. Alert Rules

### 5.1 Error Threshold Alerts

| Rule | Condition | Action | Silence |
|------|-----------|--------|---------|
| **High Error Rate** | >10 errors in 5min | → Slack #alerts-errors | 10min |
| **Spike Detection** | >2x baseline in 15min | → Slack #alerts-urgent + Page SRE | — |
| **New Error Type** | First occurrence of error group | → Slack #alerts-new | 1h |
| **Crash Rate** | >0.1% crash rate in 1h | → Slack #alerts-urgent + GitHub issue | — |

### 5.2 Performance Alerts

| Rule | Condition | Action |
|------|-----------|--------|
| **API P95 Latency** | >500ms for 5min | → Slack #alerts-perf |
| **API P99 Latency** | >2s for 5min | → Slack #alerts-urgent |
| **Apdex Score** | <0.9 for 10min | → Slack #alerts-perf |

### 5.3 Slack Integration

Create Slack alert channel `#alerts` and add Sentry webhook:

1. Sentry → Settings → Integrations → Slack
2. Add workspace → #alerts
3. Default alert: "on any of {error, warning, info}"

---

## 6. Sampling Rationale

| Platform | Traces Sample Rate | Reason |
|----------|--------------------|--------|
| **Backend (Laravel)** | 0.1 (10%) | High request volume; 10% sufficient for latency distributions |
| **Frontend (Vue)** | 0.2 (20%) | Fewer page loads than API calls; 20% captures edge cases |
| **Mobile (Flutter)** | 0.2 (20%) | Matches frontend; screenshots + view hierarchy on error is heavier |

---

*Generated by Naomi Brooks (Observability SRE) · Gate 8 · 2026-06-25*
*Integrates with: docker-compose.prod.yml (SENTRY_LARAVEL_DSN env var), .env.production*
