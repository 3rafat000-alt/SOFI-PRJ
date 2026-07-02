<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/PRJ-SAKK/backend/app/Models/AdminNotification.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\AdminNotification
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-d60a4b24277a97cd97b939f43bcc194e3bbf0ceef60c30bed3ab14114cb7ca90',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\AdminNotification',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/PRJ-SAKK/backend/app/Models/AdminNotification.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\AdminNotification',
    'shortName' => 'AdminNotification',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 50,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
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
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Models\\AdminNotification',
        'implementingClassName' => 'App\\Models\\AdminNotification',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'admin_id\', \'title\', \'body\', \'type\', \'user_ids\', \'status\', \'sent_count\', \'failed_count\', \'scheduled_at\', \'sent_at\']',
          'attributes' => 
          array (
            'startLine' => 10,
            'endLine' => 21,
            'startTokenPos' => 33,
            'startFilePos' => 191,
            'endTokenPos' => 65,
            'endFilePos' => 393,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 10,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\AdminNotification',
        'implementingClassName' => 'App\\Models\\AdminNotification',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 74,
            'startFilePos' => 422,
            'endTokenPos' => 75,
            'endFilePos' => 423,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'casts' => 
      array (
        'declaringClassName' => 'App\\Models\\AdminNotification',
        'implementingClassName' => 'App\\Models\\AdminNotification',
        'name' => 'casts',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'user_ids\' => \'array\', \'scheduled_at\' => \'datetime\', \'sent_at\' => \'datetime\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 29,
            'startTokenPos' => 84,
            'startFilePos' => 450,
            'endTokenPos' => 107,
            'endFilePos' => 558,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'admin' => 
      array (
        'name' => 'admin',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\AdminNotification',
        'implementingClassName' => 'App\\Models\\AdminNotification',
        'currentClassName' => 'App\\Models\\AdminNotification',
        'aliasName' => NULL,
      ),
      'getTargetUsers' => 
      array (
        'name' => 'getTargetUsers',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get target users based on type
 */',
        'startLine' => 39,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\AdminNotification',
        'implementingClassName' => 'App\\Models\\AdminNotification',
        'currentClassName' => 'App\\Models\\AdminNotification',
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