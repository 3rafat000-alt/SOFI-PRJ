<?php declare(strict_types = 1);

// ftm-/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Services/StripeIssuingService.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      '4027874878d53258b5cf17fc7450a7ea' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '23c955a06cf509147069e6db0524a0e3' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '4e401242d542442782a8ff5348ccfee9' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'loadConfig',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '6cd880426aaa717bc2f25b440aff946d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'isConfigured',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'df19d9d898de7e114ad07d99f2162a6a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'createCardholder',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '926b701902a7b90676578bc68441d72d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'updateCardholder',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '92c7fee7aac8e01eba1bc997906b5758' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'issueVirtualCard',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f077b0d92dbbbe5997a85d88c3ff7fdc' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'getCardDetails',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f9287e6e36bad74dd56aa87a5c2aac0d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'handleAuthorizationRequest',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '4fb0e104ca76922abc94827ef9dbce27' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'handleAuthorizationCapture',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'bdb80cdd3af6eb8f26c9dc8fb6e46b3d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'handleAuthorizationReversal',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '79f8b485922ffe2b333368958f004b00' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'freezeCard',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '8444658547f1e66e321384a852a0c1be' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'unfreezeCard',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '09c9ce3c9d71360fb881266770433d44' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'cancelCard',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '2a32812978a9d9dc1077e7380140fbb2' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'verifyWebhookSignature',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '24e257e72fff2105fd875b1ed7df0960' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'declineAuthorization',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f7600f7f9cab8806cb153d26ebd12fea' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'checkSpendingLimits',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'e4c148729f2f638bc93b426b43a21359' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'checkMerchantAllowed',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '9aebe5ac74c555eb83546bdd1ff3ffca' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'isInternationalTransaction',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'ee7afd9742226d7ed750c9ba1b80ba58' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'getCardLimitForKycLevel',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'c9608b2ba74e3a45d1ee6a7c4cf98a47' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Services',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'wallet' => 'App\\Models\\Wallet',
          'virtualcard' => 'App\\Models\\VirtualCard',
          'transaction' => 'App\\Models\\Transaction',
          'transactiontype' => 'App\\Enums\\TransactionType',
          'transactioncategory' => 'App\\Enums\\TransactionCategory',
          'transactionstatus' => 'App\\Enums\\TransactionStatus',
          'cardstatus' => 'App\\Enums\\CardStatus',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'str' => 'Illuminate\\Support\\Str',
          'stripeclient' => 'Stripe\\StripeClient',
          'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
        ),
         'className' => 'App\\Services\\StripeIssuingService',
         'functionName' => 'translateStripeError',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Services',
           'uses' => 
          array (
            'user' => 'App\\Models\\User',
            'wallet' => 'App\\Models\\Wallet',
            'virtualcard' => 'App\\Models\\VirtualCard',
            'transaction' => 'App\\Models\\Transaction',
            'transactiontype' => 'App\\Enums\\TransactionType',
            'transactioncategory' => 'App\\Enums\\TransactionCategory',
            'transactionstatus' => 'App\\Enums\\TransactionStatus',
            'cardstatus' => 'App\\Enums\\CardStatus',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'cache' => 'Illuminate\\Support\\Facades\\Cache',
            'str' => 'Illuminate\\Support\\Str',
            'stripeclient' => 'Stripe\\StripeClient',
            'apierrorexception' => 'Stripe\\Exception\\ApiErrorException',
          ),
           'className' => 'App\\Services\\StripeIssuingService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      '/home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Services/StripeIssuingService.php' => '7de54708d0496b21e8ed178ce735292384c0b4e4d8ceafcb6682e9a2cea01fab',
    ),
  ),
));