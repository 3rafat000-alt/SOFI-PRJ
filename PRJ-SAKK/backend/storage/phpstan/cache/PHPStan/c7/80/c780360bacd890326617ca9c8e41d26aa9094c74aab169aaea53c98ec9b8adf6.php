<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/API/ContactController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\API\ContactController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-029aa89ce0a1e5c86c6e52ce30641403b85c91c69aced09be031e3dd40312308',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\API\\ContactController',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/API/ContactController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\API',
    'name' => 'App\\Http\\Controllers\\API\\ContactController',
    'shortName' => 'ContactController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Match a user\'s phone contacts against registered SAKK users so they can
 * transfer to people they know. Privacy-preserving: only returns matches,
 * never reveals which of the submitted numbers exist beyond the matched set.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 72,
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
      'MATCH_DIGITS' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\API\\ContactController',
        'implementingClassName' => 'App\\Http\\Controllers\\API\\ContactController',
        'name' => 'MATCH_DIGITS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '9',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 49,
            'startFilePos' => 563,
            'endTokenPos' => 49,
            'endFilePos' => 563,
          ),
        ),
        'docComment' => '/** How many trailing digits to compare (ignores country-code differences). */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'match' => 
      array (
        'name' => 'match',
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
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 27,
            'endColumn' => 42,
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
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\API',
        'declaringClassName' => 'App\\Http\\Controllers\\API\\ContactController',
        'implementingClassName' => 'App\\Http\\Controllers\\API\\ContactController',
        'currentClassName' => 'App\\Http\\Controllers\\API\\ContactController',
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