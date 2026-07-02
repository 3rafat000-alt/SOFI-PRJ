<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Concerns/VerifiesTransactionAuth.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Concerns\VerifiesTransactionAuth
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-dd02bcb981acc26687b3d55fb85ffa46d983b9e35ad0912101daddd98e3aa3ed',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Concerns/VerifiesTransactionAuth.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Concerns',
    'name' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
    'shortName' => 'VerifiesTransactionAuth',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Second-factor authorization for money-moving endpoints.
 *
 * Historically these endpoints accepted a `biometric_token` field whose mere
 * presence skipped the PIN check entirely — but nothing ever validated that
 * token (the mobile client sent the literal string "biometric"). That made the
 * "second factor" a no-op: any holder of a session token could move funds with
 * `biometric_token=<anything>` and never prove possession of the device or PIN.
 *
 * This trait restores a real second factor that FAILS CLOSED:
 *   - If a biometric signature is presented, it is cryptographically verified
 *     against the user\'s registered device public key over a fresh, single-use,
 *     server-issued challenge (the same challenge BiometricController issues).
 *   - Otherwise a valid PIN is required.
 *   - If NEITHER is validly presented, the request is rejected — money never
 *     moves on an unverified factor.
 *
 * No new runtime dependencies: signature verification uses ext-openssl
 * (RSA / EC PEM keys) with an ext-sodium Ed25519 fallback, both bundled with PHP.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 178,
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
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'verifyTransactionFactor' => 
      array (
        'name' => 'verifyTransactionFactor',
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
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 48,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startColumn' => 66,
            'endColumn' => 75,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Verify that the request carries a valid second factor (verified biometric
 * signature OR correct PIN). Returns true on success.
 *
 * Fails closed: returns false unless exactly one factor is validly presented
 * and passes verification.
 */',
        'startLine' => 37,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Concerns',
        'declaringClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'implementingClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'currentClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'aliasName' => NULL,
      ),
      'verifyBiometricToken' => 
      array (
        'name' => 'verifyBiometricToken',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 45,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'token' => 
          array (
            'name' => 'token',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 57,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'deviceId' => 
          array (
            'name' => 'deviceId',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 73,
            'endColumn' => 89,
            'parameterIndex' => 2,
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
 * Verify a biometric authorization token: a base64/hex signature produced by
 * the device signing the server-issued challenge with the private key whose
 * public half was registered via BiometricController::registerDevice.
 *
 * The challenge is the one cached by BiometricController::challenge under
 * "biometric_challenge:{user_id}" (5-minute TTL). It is SINGLE-USE: a
 * successful verification consumes it, so a captured signature cannot be
 * replayed.
 *
 * Fails closed on any missing/invalid input — returns false, never throws.
 */',
        'startLine' => 69,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Concerns',
        'declaringClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'implementingClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'currentClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'aliasName' => NULL,
      ),
      'decodeSignature' => 
      array (
        'name' => 'decodeSignature',
        'parameters' => 
        array (
          'token' => 
          array (
            'name' => 'token',
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 38,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Decode a signature that may arrive base64-encoded (preferred) or hex.
 * Returns the raw binary signature, or null if it is neither.
 */',
        'startLine' => 117,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Concerns',
        'declaringClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'implementingClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'currentClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'aliasName' => NULL,
      ),
      'signatureMatches' => 
      array (
        'name' => 'signatureMatches',
        'parameters' => 
        array (
          'publicKey' => 
          array (
            'name' => 'publicKey',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 39,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'challenge' => 
          array (
            'name' => 'challenge',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 58,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'signature' => 
          array (
            'name' => 'signature',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 77,
            'endColumn' => 93,
            'parameterIndex' => 2,
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
 * Verify $signature over $challenge using $publicKey.
 *
 * Supports PEM public keys via ext-openssl (RSA + ECDSA) and raw 32-byte
 * Ed25519 keys (base64/hex) via ext-sodium. Constant-effort, fail-closed.
 */',
        'startLine' => 143,
        'endLine' => 177,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Concerns',
        'declaringClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'implementingClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
        'currentClassName' => 'App\\Http\\Controllers\\Concerns\\VerifiesTransactionAuth',
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