<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Admin/IntegrationController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\IntegrationController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-85653bc18bd96aa0b52e140434c7198b2297d1f57ca9a5c8ab01d2640cd0b6cd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Admin/IntegrationController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin',
    'name' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
    'shortName' => 'IntegrationController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 100,
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
      'CATEGORIES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'name' => 'CATEGORIES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'payment\' => [\'label\' => \'بوابات الدفع\', \'icon\' => \'payments\', \'color\' => \'#059669\', \'desc\' => \'CCPayment والعملات الرقمية\'], \'messaging\' => [\'label\' => \'الرسائل\', \'icon\' => \'chat\', \'color\' => \'#2563eb\', \'desc\' => \'SMS والبريد الإلكتروني\'], \'notifications\' => [\'label\' => \'الإشعارات\', \'icon\' => \'notifications\', \'color\' => \'#d97706\', \'desc\' => \'FCM والإشعارات الفورية\'], \'location\' => [\'label\' => \'الموقع\', \'icon\' => \'map\', \'color\' => \'#6366f1\', \'desc\' => \'Google Maps وخدمات الموقع\'], \'cards\' => [\'label\' => \'البطاقات\', \'icon\' => \'credit_card\', \'color\' => \'#7c3aed\', \'desc\' => \'البطاقات الافتراضية\']]',
          'attributes' => 
          array (
            'startLine' => 12,
            'endLine' => 18,
            'startTokenPos' => 45,
            'startFilePos' => 254,
            'endTokenPos' => 217,
            'endFilePos' => 1022,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'overview' => 
      array (
        'name' => 'overview',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** عرض لوحة التكاملات — بسيطة، جميلة، مركزة على الاتصال */',
        'startLine' => 21,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'aliasName' => NULL,
      ),
      'show' => 
      array (
        'name' => 'show',
        'parameters' => 
        array (
          'integration' => 
          array (
            'name' => 'integration',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Integration',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 26,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** صفحة تكامل واحد — فقط الربط والإعدادات الأساسية */',
        'startLine' => 31,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'aliasName' => NULL,
      ),
      'update' => 
      array (
        'name' => 'update',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'integration' => 
          array (
            'name' => 'integration',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Integration',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 46,
            'endColumn' => 69,
            'parameterIndex' => 1,
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
        'docComment' => '/** حفظ الإعدادات الأساسية */',
        'startLine' => 39,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'aliasName' => NULL,
      ),
      'test' => 
      array (
        'name' => 'test',
        'parameters' => 
        array (
          'integration' => 
          array (
            'name' => 'integration',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Integration',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 26,
            'endColumn' => 49,
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
        'docComment' => '/** اختبار الاتصال */',
        'startLine' => 66,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'aliasName' => NULL,
      ),
      'toggle' => 
      array (
        'name' => 'toggle',
        'parameters' => 
        array (
          'integration' => 
          array (
            'name' => 'integration',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Integration',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 28,
            'endColumn' => 51,
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
        'docComment' => '/** تفعيل/تعطيل */',
        'startLine' => 88,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\IntegrationController',
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