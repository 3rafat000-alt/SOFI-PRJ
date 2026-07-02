<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Admin/SecureFileController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\SecureFileController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-94609f634c74f6f373fda6e490b2095648715ca9d660bbd61a64e88fdfb8a52b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Admin/SecureFileController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin',
    'name' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
    'shortName' => 'SecureFileController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Gated streaming of sensitive KYC / partner identity documents.
 *
 * Identity documents (national ID, passport, driver\'s licence, selfies, proof of
 * address) live on the PRIVATE \'local\' disk (storage/app/private) and are NEVER
 * exposed through the public storage symlink. This controller is the single
 * authorised egress: admin views build links with
 *   route(\'admin.secure-file\', [\'path\' => encrypt($relativeStoragePath)])
 * and this action decrypts, validates and streams the file.
 *
 * The route is already registered behind [\'auth\',\'admin\'] (routes/web.php). We
 * re-assert the admin ability in-controller (defence in depth — a future route
 * change must not silently de-gate identity PII) and constrain the served path to
 * the known document directories so this endpoint can never be coerced into an
 * arbitrary-file reader for anything else under storage/app/private.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 116,
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
      'ALLOWED_PREFIXES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
        'name' => 'ALLOWED_PREFIXES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'kyc/\', \'kyc-documents/\', \'partner-documents/\']',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 43,
            'startTokenPos' => 64,
            'startFilePos' => 1657,
            'endTokenPos' => 75,
            'endFilePos' => 1735,
          ),
        ),
        'docComment' => '/**
 * Relative-path prefixes this endpoint is permitted to serve.
 *
 * Mirrors every place identity documents are written:
 *  - KycService            -> "kyc/{userId}/{id|selfie|address}/..."
 *  - Admin\\KycController    -> "kyc-documents/..."
 *  - API\\PartnerApplication -> "partner-documents/..."
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'show' => 
      array (
        'name' => 'show',
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
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 26,
            'endColumn' => 41,
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
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 45,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
        'aliasName' => NULL,
      ),
      'isSafeRelativePath' => 
      array (
        'name' => 'isSafeRelativePath',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 41,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * A path is safe only when it is a clean relative key under one of the known
 * document directories: no traversal (..), no null byte, no backslash, not
 * absolute, and prefix-allowlisted.
 */',
        'startLine' => 97,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecureFileController',
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