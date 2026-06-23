<?php

declare(strict_types=1);

use MauticRector\UnserializeToSerializerDecodeRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\KnownMagicClassMethodTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictParamRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;

$extendableControllers = [
    __DIR__.'/app/bundles/CoreBundle/Controller/AbstractStandardFormController.php',
    __DIR__.'/app/bundles/CoreBundle/Controller/CommonController.php',
    __DIR__.'/app/bundles/CoreBundle/Controller/FormController.php',
];

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ])
    ->withPreparedSets(deadCode: true)
    ->withPhpSets(php80: true)
    ->withCache(__DIR__.'/var/cache/rector')
    ->withRules([
<<<<<<< HEAD
<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> ca5200203c (fix phpstan)
        Rector\TypeDeclaration\Rector\Empty_\EmptyOnNullableObjectToInstanceOfRector::class,
>>>>>>> aa468ca272 (Type coverage: register ParamTypeByParentCallTypeRector)
        Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector::class,
        AddParamTypeFromPropertyTypeRector::class,
        KnownMagicClassMethodTypeRector::class,
<<<<<<< HEAD
<<<<<<< HEAD
        // flips nested negated conditions to same-meaning clear ones
        Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector::class,
        Rector\TypeDeclaration\Rector\Empty_\EmptyOnNullableObjectToInstanceOfRector::class,
=======
=======
=======
=======
        ParamTypeByParentCallTypeRector::class,
>>>>>>> 72f0bc69f0 (Type coverage: register ParamTypeByParentCallTypeRector)
>>>>>>> eae9b7f93d (Type coverage: register ParamTypeByParentCallTypeRector)
<<<<<<< HEAD
>>>>>>> c375e8dbcf (Type coverage: register ParamTypeByParentCallTypeRector)
<<<<<<< HEAD
>>>>>>> aa468ca272 (Type coverage: register ParamTypeByParentCallTypeRector)
=======
=======
=======
        ParamTypeByParentCallTypeRector::class,
>>>>>>> 0a5a5dbc58 (fix phpstan)
>>>>>>> de77409813 (fix phpstan)
>>>>>>> ca5200203c (fix phpstan)
=======
        ParamTypeByParentCallTypeRector::class,
>>>>>>> 2c83503805 (rebase)
        ReturnTypeFromStrictTypedCallRector::class,
        TypedPropertyFromAssignsRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        ReturnTypeFromStrictParamRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        TypedPropertyFromStrictSetUpRector::class,
        SimplifyUselessVariableRector::class,
        UnserializeToSerializerDecodeRector::class,
    ])
    ->reportUnusedSkips()
    ->withTypeCoverageLevel(23)
    ->withCodingStyleLevel(3)
    ->withCodeQualityLevel(19)
    ->withSkip([
        // too many changes
        Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class,

        Rector\Renaming\Rector\FuncCall\RenameFunctionRector::class,
        '*/Test/*',
        '*/Tests/*',

        ReturnTypeFromReturnDirectArrayRector::class => [
            // require bit test update
            __DIR__.'/app/bundles/LeadBundle/Model/LeadModel.php',
        ],

        // Avoiding breaking BC breaks with forced return types in public methods
        ReturnTypeFromReturnNewRector::class => [
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Integration/ObjectChangeGenerator.php',
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Internal/ObjectChangeGenerator.php',
        ],

        // lets handle later, once we have more type declaratoins
        RecastingRemovalRector::class,

        // designed to be overriden by 3rd party, adding return type will break BC
        Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector::class => [
            ...$extendableControllers,
        ],
        ReturnTypeFromStrictTypedCallRector::class => [
            ...$extendableControllers,
        ],
        StringReturnTypeFromStrictStringReturnsRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Entity/FormEntity.php',
        ],
        ReturnTypeFromStrictTypedPropertyRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Controller/FormController.php',
            // handle mocks later
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/DAO/DateRange.php',
            __DIR__.'/app/bundles/CampaignBundle/Executioner/Scheduler/Mode/DAO/GroupExecutionDateDAO.php',
            __DIR__.'/app/bundles/CampaignBundle/Executioner/EventExecutioner.php',
        ],
        Rector\TypeDeclaration\Rector\ClassMethod\ReturnNullableTypeRector::class => [
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/DAO/DateRange.php',
            // can be overriden, BC
            ...$extendableControllers,
        ],

        TypedPropertyFromAssignsRector::class => [
            '*/Entity/*',
        ],

        // handle later with full PHP 8.0 upgrade
        OptionalParametersAfterRequiredRector::class,

        // handle later, case by case as lot of chnaged code
        RemoveAlwaysTrueIfConditionRector::class => [
            // watch out on this one - the variables are set magically via $$name
            // @see app/bundles/FormBundle/Form/Type/FieldType.php:99
            __DIR__.'/app/bundles/FormBundle/Form/Type/FieldType.php',
        ],
    ]);
