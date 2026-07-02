<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Services/FileValidationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\FileValidationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-ebdbc09726204a2c8fc915f93012b456ac5442629198bf5924ccbce2686d3df5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\FileValidationService',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Services/FileValidationService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\FileValidationService',
    'shortName' => 'FileValidationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 4,
    'endLine' => 55,
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
      'ALLOWED_MIMES' => 
      array (
        'declaringClassName' => 'App\\Services\\FileValidationService',
        'implementingClassName' => 'App\\Services\\FileValidationService',
        'name' => 'ALLOWED_MIMES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'image/jpeg\', \'image/png\', \'image/webp\', \'application/pdf\', \'application/msword\', \'application/vnd.openxmlformats-officedocument.wordprocessingml.document\']',
          'attributes' => 
          array (
            'startLine' => 6,
            'endLine' => 13,
            'startTokenPos' => 20,
            'startFilePos' => 95,
            'endTokenPos' => 40,
            'endFilePos' => 306,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 6,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'BLOCKED_MIMES' => 
      array (
        'declaringClassName' => 'App\\Services\\FileValidationService',
        'implementingClassName' => 'App\\Services\\FileValidationService',
        'name' => 'BLOCKED_MIMES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'image/svg+xml\', \'text/html\', \'application/x-php\', \'application/x-javascript\']',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 20,
            'startTokenPos' => 51,
            'startFilePos' => 344,
            'endTokenPos' => 65,
            'endFilePos' => 461,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'validateUpload' => 
      array (
        'name' => 'validateUpload',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\UploadedFile',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 36,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'additionalMimes' => 
          array (
            'name' => 'additionalMimes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 22,
                'endLine' => 22,
                'startTokenPos' => 85,
                'startFilePos' => 562,
                'endTokenPos' => 86,
                'endFilePos' => 563,
              ),
            ),
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 73,
            'endColumn' => 99,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\FileValidationService',
        'implementingClassName' => 'App\\Services\\FileValidationService',
        'currentClassName' => 'App\\Services\\FileValidationService',
        'aliasName' => NULL,
      ),
      'sanitizeFilename' => 
      array (
        'name' => 'sanitizeFilename',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 38,
            'endColumn' => 53,
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
        'docComment' => NULL,
        'startLine' => 49,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\FileValidationService',
        'implementingClassName' => 'App\\Services\\FileValidationService',
        'currentClassName' => 'App\\Services\\FileValidationService',
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