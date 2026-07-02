<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Middleware/SecurityHeaders.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Middleware\SecurityHeaders
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-852b89fb439aaddd5cd9cb02e40891bb91173de9e83fa939945db7318409aed6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Middleware\\SecurityHeaders',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Middleware/SecurityHeaders.php',
      ),
    ),
    'namespace' => 'App\\Http\\Middleware',
    'name' => 'App\\Http\\Middleware\\SecurityHeaders',
    'shortName' => 'SecurityHeaders',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Adds defensive HTTP security headers to every response.
 *
 * Registered as a global middleware (web + api groups) in bootstrap/app.php.
 * The Content-Security-Policy keeps a `default-src \'self\'` baseline but allows
 * inline script/style because the admin Blade UI and error pages ship inline
 * <script>/<style>/style= and on* handlers; tightening those to \'self\' would
 * break existing behaviour. HSTS is only emitted over HTTPS so local HTTP dev
 * is never pinned to TLS.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 72,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'CSP' => 
      array (
        'declaringClassName' => 'App\\Http\\Middleware\\SecurityHeaders',
        'implementingClassName' => 'App\\Http\\Middleware\\SecurityHeaders',
        'name' => 'CSP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '"default-src \'self\'; " . "script-src \'self\' \'unsafe-inline\' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; " . "style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; " . "img-src \'self\' data: https:; " . "font-src \'self\' data: https://cdn.jsdelivr.net; " . "connect-src \'self\'; " . "base-uri \'self\'; " . "form-action \'self\'; " . "frame-ancestors \'none\'"',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 39,
            'startTokenPos' => 40,
            'startFilePos' => 889,
            'endTokenPos' => 72,
            'endFilePos' => 1618,
          ),
        ),
        'docComment' => '/**
 * Content-Security-Policy directives.
 *
 * `default-src \'self\'` is the baseline; script/style permit \'unsafe-inline\'
 * to preserve the existing inline-asset behaviour of the admin panel.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'PERMISSIONS_POLICY' => 
      array (
        'declaringClassName' => 'App\\Http\\Middleware\\SecurityHeaders',
        'implementingClassName' => 'App\\Http\\Middleware\\SecurityHeaders',
        'name' => 'PERMISSIONS_POLICY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'camera=(), microphone=(), geolocation=()\'',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 85,
            'startFilePos' => 1742,
            'endTokenPos' => 85,
            'endFilePos' => 1783,
          ),
        ),
        'docComment' => '/**
 * Permissions-Policy: disable powerful features by default.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 82,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'next' => 
          array (
            'name' => 'next',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 46,
            'endColumn' => 58,
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
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 46,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Middleware',
        'declaringClassName' => 'App\\Http\\Middleware\\SecurityHeaders',
        'implementingClassName' => 'App\\Http\\Middleware\\SecurityHeaders',
        'currentClassName' => 'App\\Http\\Middleware\\SecurityHeaders',
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