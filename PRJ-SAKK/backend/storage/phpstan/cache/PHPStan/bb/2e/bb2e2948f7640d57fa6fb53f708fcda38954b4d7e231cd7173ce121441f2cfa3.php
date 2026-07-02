<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Services/ReferralService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\ReferralService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-79ec4c1e11de8ae685dcd8b61ce439bb943342ddd7b932446606984cee4c84c4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\ReferralService',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Services/ReferralService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\ReferralService',
    'shortName' => 'ReferralService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Referral program: invite people who don\'t have an account yet. When an
 * invited user registers (with the referrer\'s code) and completes KYC, the
 * referrer earns a configurable reward (default $1, set by admin).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 171,
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
      'SETTING_KEY' => 
      array (
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'name' => 'SETTING_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'referral_bonus_referrer\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 73,
            'startFilePos' => 606,
            'endTokenPos' => 73,
            'endFilePos' => 630,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 57,
      ),
      'DEFAULT_REWARD' => 
      array (
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'name' => 'DEFAULT_REWARD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5.0',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 84,
            'startFilePos' => 667,
            'endTokenPos' => 84,
            'endFilePos' => 669,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 38,
      ),
      'REWARD_CURRENCY' => 
      array (
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'name' => 'REWARD_CURRENCY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'USD\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 95,
            'startFilePos' => 707,
            'endTokenPos' => 95,
            'endFilePos' => 711,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'DEPOSIT_THRESHOLD' => 
      array (
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'name' => 'DEPOSIT_THRESHOLD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '100.0',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 108,
            'startFilePos' => 838,
            'endTokenPos' => 108,
            'endFilePos' => 842,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'rewardAmount' => 
      array (
        'name' => 'rewardAmount',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Admin-configurable reward amount (USD). */',
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'currentClassName' => 'App\\Services\\ReferralService',
        'aliasName' => NULL,
      ),
      'attachReferrer' => 
      array (
        'name' => 'attachReferrer',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 36,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'referralCode' => 
          array (
            'name' => 'referralCode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 48,
            'endColumn' => 68,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Link a newly registered user to their referrer via a referral code. */',
        'startLine' => 37,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'currentClassName' => 'App\\Services\\ReferralService',
        'aliasName' => NULL,
      ),
      'grantOnKycVerified' => 
      array (
        'name' => 'grantOnKycVerified',
        'parameters' => 
        array (
          'referredUser' => 
          array (
            'name' => 'referredUser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 40,
            'endColumn' => 57,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Backward-compatible trigger — called when KYC becomes verified. */',
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'currentClassName' => 'App\\Services\\ReferralService',
        'aliasName' => NULL,
      ),
      'referredQualifies' => 
      array (
        'name' => 'referredQualifies',
        'parameters' => 
        array (
          'referredUser' => 
          array (
            'name' => 'referredUser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 39,
            'endColumn' => 56,
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
        'docComment' => '/** True when the referred user is verified AND deposited >= $100 (USD). */',
        'startLine' => 60,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'currentClassName' => 'App\\Services\\ReferralService',
        'aliasName' => NULL,
      ),
      'maybeGrant' => 
      array (
        'name' => 'maybeGrant',
        'parameters' => 
        array (
          'referredUser' => 
          array (
            'name' => 'referredUser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 32,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Grant the referral reward to the referrer once the referred user BOTH
 * verifies their identity AND deposits their first $100. Called from the
 * KYC-verified and deposit triggers; idempotent (pays out once).
 */',
        'startLine' => 78,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'currentClassName' => 'App\\Services\\ReferralService',
        'aliasName' => NULL,
      ),
      'info' => 
      array (
        'name' => 'info',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 26,
            'endColumn' => 35,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Stats + share payload for the referral screen. */',
        'startLine' => 153,
        'endLine' => 170,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\ReferralService',
        'implementingClassName' => 'App\\Services\\ReferralService',
        'currentClassName' => 'App\\Services\\ReferralService',
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