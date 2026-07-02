<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Webhooks/StripeIssuingWebhookController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Webhooks\StripeIssuingWebhookController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-c691d896ef59879b9fee53b2d7fd75a8ae42ffdc4c767d934b3e7d2d9b243ea2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Webhooks/StripeIssuingWebhookController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Webhooks',
    'name' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
    'shortName' => 'StripeIssuingWebhookController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Stripe Issuing Webhook Controller
 * 
 * CRITICAL: issuing_authorization.request has 2-second timeout
 * Must approve/decline within 2 seconds or Stripe auto-declines
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 288,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'App\\Http\\Controllers\\Controller',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'stripeService' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'name' => 'stripeService',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\StripeIssuingService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 9,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'stripeService' => 
          array (
            'name' => 'stripeService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\StripeIssuingService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 9,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle all Stripe Issuing webhooks
 */',
        'startLine' => 27,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleAuthorizationRequest' => 
      array (
        'name' => 'handleAuthorizationRequest',
        'parameters' => 
        array (
          'authorization' => 
          array (
            'name' => 'authorization',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 51,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle real-time authorization request
 * CRITICAL: Must respond within 2 seconds
 */',
        'startLine' => 79,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleAuthorizationCreated' => 
      array (
        'name' => 'handleAuthorizationCreated',
        'parameters' => 
        array (
          'authorization' => 
          array (
            'name' => 'authorization',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 51,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle authorization created (after approval/decline)
 */',
        'startLine' => 98,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleAuthorizationUpdated' => 
      array (
        'name' => 'handleAuthorizationUpdated',
        'parameters' => 
        array (
          'authorization' => 
          array (
            'name' => 'authorization',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 51,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle authorization updated (capture, reversal, etc.)
 */',
        'startLine' => 119,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleTransactionCreated' => 
      array (
        'name' => 'handleTransactionCreated',
        'parameters' => 
        array (
          'transaction' => 
          array (
            'name' => 'transaction',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 49,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle transaction created
 */',
        'startLine' => 140,
        'endLine' => 152,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleTransactionUpdated' => 
      array (
        'name' => 'handleTransactionUpdated',
        'parameters' => 
        array (
          'transaction' => 
          array (
            'name' => 'transaction',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 49,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle transaction updated (refunds, corrections)
 */',
        'startLine' => 157,
        'endLine' => 164,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleCardCreated' => 
      array (
        'name' => 'handleCardCreated',
        'parameters' => 
        array (
          'card' => 
          array (
            'name' => 'card',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 42,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle card created
 */',
        'startLine' => 169,
        'endLine' => 177,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleCardUpdated' => 
      array (
        'name' => 'handleCardUpdated',
        'parameters' => 
        array (
          'card' => 
          array (
            'name' => 'card',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 42,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle card updated (status changes)
 */',
        'startLine' => 182,
        'endLine' => 211,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleCardholderCreated' => 
      array (
        'name' => 'handleCardholderCreated',
        'parameters' => 
        array (
          'cardholder' => 
          array (
            'name' => 'cardholder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 216,
            'endLine' => 216,
            'startColumn' => 48,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle cardholder created
 */',
        'startLine' => 216,
        'endLine' => 223,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleCardholderUpdated' => 
      array (
        'name' => 'handleCardholderUpdated',
        'parameters' => 
        array (
          'cardholder' => 
          array (
            'name' => 'cardholder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 48,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle cardholder updated
 */',
        'startLine' => 228,
        'endLine' => 236,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleDisputeCreated' => 
      array (
        'name' => 'handleDisputeCreated',
        'parameters' => 
        array (
          'dispute' => 
          array (
            'name' => 'dispute',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle dispute created
 */',
        'startLine' => 241,
        'endLine' => 257,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'handleDisputeUpdated' => 
      array (
        'name' => 'handleDisputeUpdated',
        'parameters' => 
        array (
          'dispute' => 
          array (
            'name' => 'dispute',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 262,
            'endLine' => 262,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle dispute updated
 */',
        'startLine' => 262,
        'endLine' => 270,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
      'mapDeclineReason' => 
      array (
        'name' => 'mapDeclineReason',
        'parameters' => 
        array (
          'reason' => 
          array (
            'name' => 'reason',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 275,
            'endLine' => 275,
            'startColumn' => 41,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Map internal decline reasons to Stripe decline reasons
 */',
        'startLine' => 275,
        'endLine' => 287,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Webhooks',
        'declaringClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'implementingClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'currentClassName' => 'App\\Http\\Controllers\\Webhooks\\StripeIssuingWebhookController',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));