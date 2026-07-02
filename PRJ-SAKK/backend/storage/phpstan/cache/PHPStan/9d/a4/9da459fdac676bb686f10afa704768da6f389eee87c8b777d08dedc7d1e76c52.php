<?php declare(strict_types = 1);

// odsl-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Console/Commands/BackfillKycPrivateFiles.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Console\Commands\BackfillKycPrivateFiles
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.5.4-391ea10316dee24e548fd74d25f59ff032c942b1b4d98b9cf7361be5ce05c5ac',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'filename' => '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Console/Commands/BackfillKycPrivateFiles.php',
      ),
    ),
    'namespace' => 'App\\Console\\Commands',
    'name' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
    'shortName' => 'BackfillKycPrivateFiles',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * One-shot remediation: relocate legacy partner (agent/merchant) KYC documents
 * off the PUBLIC storage disk and onto the PRIVATE disk.
 *
 * Background / why this exists
 * ----------------------------
 * Identity documents must never sit on the publicly served disk
 * (storage/app/public, exposed through the /storage symlink + asset()). The
 * current upload path — API\\PartnerApplicationController::uploadDocument — was
 * historically written with `->store(\'partner-documents\', \'public\')`, so legacy
 * rows in `agent_documents` / `merchant_documents` may point at public-disk
 * files that are world-readable (a Lirat-class exposure). User-level KYC docs
 * (KycService, key prefix "kyc/{userId}/...") already use the private disk and
 * are NOT in scope here. Avatars ("avatars/..."), card-imports and DB backups
 * are explicitly out of scope and are defended against by a prefix allow-check.
 *
 * What this command does
 * ----------------------
 * For every AgentDocument and MerchantDocument whose `file_path` still lives on
 * the public disk under the "partner-documents/" prefix, it copies the bytes to
 * the private disk at the SAME relative key, verifies the private copy, then
 * deletes the public original. Because the destination disk (\'private\') and the
 * read disk used by Admin\\SecureFileController (\'local\') share the same root
 * (storage/app/private) and SecureFileController already allow-lists the
 * "partner-documents/" prefix, the document becomes immediately serveable
 * through the gated egress with no path rewrite required.
 *
 * Disk-agnostic key: `file_path` stores a RELATIVE key (e.g.
 * "partner-documents/AbC123.pdf"), not a disk-qualified URL. The same key is
 * valid on both disks, so the only change needed is the physical byte location —
 * there is NO column value to mutate. The command therefore does not write the
 * model back when the key is unchanged; its job reduces to relocating bytes
 * idempotently and removing the public copy.
 *
 * Cross-disk move: Laravel\'s FilesystemAdapter::move()/copy() operate within a
 * single disk only and cannot move public -> private. We stream the bytes
 * manually: readStream(\'public\') -> writeStream(\'private\') -> verify exists on
 * private -> delete from \'public\'. This ordering guarantees the private copy is
 * durably written (and the DB pointer, which is unchanged, already references
 * the shared key) before the public original is removed, so a crash mid-run can
 * never destroy the only copy.
 *
 * Safety contract
 * ---------------
 *  - DEFAULT is a DRY RUN: with no flags (or with --dry-run) it only reports what
 *    WOULD move and mutates nothing — no Storage writes, no Storage deletes, no
 *    model saves.
 *  - A real run requires BOTH --force AND an interactive confirmation. --force
 *    alone (no confirm) aborts. --dry-run always wins if both are passed.
 *  - Idempotent: re-running after a successful run moves 0 files and mutates
 *    nothing. Per-file failures are isolated (logged + counted, loop continues);
 *    the public original is never deleted unless the private copy is verified.
 *
 * Internal operator tooling -> English output (per project convention; Arabic is
 * reserved for end-user-facing UI strings).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 71,
    'endLine' => 356,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'PARTNER_PREFIX' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'name' => 'PARTNER_PREFIX',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'partner-documents/\'',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 90,
            'startFilePos' => 4264,
            'endTokenPos' => 90,
            'endFilePos' => 4283,
          ),
        ),
        'docComment' => '/**
 * Only keys under this prefix are ever touched. Anything else (avatars/,
 * card-imports/, ...) is skipped defensively so this command can never
 * relocate a non-KYC file.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 56,
      ),
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'kyc:backfill-private
        {--dry-run : Report what would move, mutate nothing (this is also the default)}
        {--force : Actually move files on disk and rewrite storage location (destructive: deletes public copies)}\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 75,
            'startTokenPos' => 68,
            'startFilePos' => 3644,
            'endTokenPos' => 68,
            'endFilePos' => 3867,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 115,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Move legacy public-disk partner (agent/merchant) KYC documents to the private disk (idempotent, safe; dry-run by default).\'',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 77,
            'startFilePos' => 3900,
            'endTokenPos' => 77,
            'endFilePos' => 4023,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 154,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'counters' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'name' => 'counters',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[
    \'moved\' => 0,
    // bytes copied public -> private, public original deleted
    \'reconciled\' => 0,
    // already on private but a stale public dup existed -> public dup deleted
    \'skipped_private\' => 0,
    // already private (or empty path) -> nothing to do
    \'skipped_foreign\' => 0,
    // path outside partner-documents/ prefix -> never touched
    \'orphaned\' => 0,
    // path missing on BOTH disks -> warn, do nothing
    \'failed\' => 0,
]',
          'attributes' => 
          array (
            'startLine' => 87,
            'endLine' => 94,
            'startTokenPos' => 103,
            'startFilePos' => 4351,
            'endTokenPos' => 159,
            'endFilePos' => 4901,
          ),
        ),
        'docComment' => '/** @var array<string,int> */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reportRows' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'name' => 'reportRows',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 97,
            'endLine' => 97,
            'startTokenPos' => 172,
            'startFilePos' => 5027,
            'endTokenPos' => 173,
            'endFilePos' => 5028,
          ),
        ),
        'docComment' => '/** Rows collected for the dry-run report table. @var array<int,array<int,string>> */',
        'attributes' => 
        array (
        ),
        'startLine' => 97,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 35,
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
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 99,
        'endLine' => 132,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'currentClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'aliasName' => NULL,
      ),
      'processModel' => 
      array (
        'name' => 'processModel',
        'parameters' => 
        array (
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Builder',
                'isIdentifier' => false,
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
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'dryRun' => 
          array (
            'name' => 'dryRun',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
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
            'startColumn' => 66,
            'endColumn' => 77,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Stream every candidate row of one model through the classify/move pipeline.
 *
 * chunkById(100) keeps memory flat for large tables and is safe to combine
 * with row mutation (it pages by ascending id, not by offset).
 */',
        'startLine' => 140,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'currentClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'aliasName' => NULL,
      ),
      'processRow' => 
      array (
        'name' => 'processRow',
        'parameters' => 
        array (
          'doc' => 
          array (
            'name' => 'doc',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Model',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 33,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 45,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'dryRun' => 
          array (
            'name' => 'dryRun',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 60,
            'endColumn' => 71,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Classify a single row and (unless dry-run) perform the relocation.
 *
 * Classification precedence:
 *   1. empty path                       -> skipped_private
 *   2. not under partner-documents/      -> skipped_foreign (never touched)
 *   3. exists on public                  -> MOVE (or, if also on private, RECONCILE)
 *   4. exists on private only            -> skipped_private (already migrated)
 *   5. missing on both disks             -> orphaned (warn, do nothing)
 */',
        'startLine' => 161,
        'endLine' => 229,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'currentClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'aliasName' => NULL,
      ),
      'movePublicToPrivate' => 
      array (
        'name' => 'movePublicToPrivate',
        'parameters' => 
        array (
          'public' => 
          array (
            'name' => 'public',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Filesystem\\Filesystem',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 9,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'private' => 
          array (
            'name' => 'private',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Filesystem\\Filesystem',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 9,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'id' => 
          array (
            'name' => 'id',
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 9,
            'endColumn' => 18,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 9,
            'endColumn' => 20,
            'parameterIndex' => 4,
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
 * Stream the bytes public -> private, verify the private copy, then delete the
 * public original. Any failure is isolated: it is logged, counted, and the
 * loop continues. The public original is deleted ONLY after the private copy
 * is confirmed to exist, so the file can never be lost.
 */',
        'startLine' => 237,
        'endLine' => 283,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'currentClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'aliasName' => NULL,
      ),
      'reconcileDuplicate' => 
      array (
        'name' => 'reconcileDuplicate',
        'parameters' => 
        array (
          'public' => 
          array (
            'name' => 'public',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Filesystem\\Filesystem',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 9,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'id' => 
          array (
            'name' => 'id',
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
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 9,
            'endColumn' => 18,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
            'startLine' => 293,
            'endLine' => 293,
            'startColumn' => 9,
            'endColumn' => 20,
            'parameterIndex' => 3,
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
 * Reconcile a partial prior run: the private copy already exists, so only the
 * stale public duplicate needs removing. No DB change (key is unchanged).
 */',
        'startLine' => 289,
        'endLine' => 306,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'currentClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'aliasName' => NULL,
      ),
      'addReportRow' => 
      array (
        'name' => 'addReportRow',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 308,
            'endLine' => 308,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'id' => 
          array (
            'name' => 'id',
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
            'startLine' => 308,
            'endLine' => 308,
            'startColumn' => 50,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 308,
            'endLine' => 308,
            'startColumn' => 62,
            'endColumn' => 73,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 308,
            'endLine' => 308,
            'startColumn' => 76,
            'endColumn' => 89,
            'parameterIndex' => 3,
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
        'docComment' => NULL,
        'startLine' => 308,
        'endLine' => 311,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'currentClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'aliasName' => NULL,
      ),
      'report' => 
      array (
        'name' => 'report',
        'parameters' => 
        array (
          'dryRun' => 
          array (
            'name' => 'dryRun',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 318,
            'endLine' => 318,
            'startColumn' => 29,
            'endColumn' => 40,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Emit the dry-run table (if any) and the always-on summary, then map the
 * outcome to an exit code: FAILURE when any per-file failure occurred so the
 * caller / CI notices, SUCCESS otherwise.
 */',
        'startLine' => 318,
        'endLine' => 355,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'implementingClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
        'currentClassName' => 'App\\Console\\Commands\\BackfillKycPrivateFiles',
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