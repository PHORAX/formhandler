<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\PostRector\Rector\NameImportingPostRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Rector\Composer\ExtensionComposerRector;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        Typo3SetList::TYPO3_76,
        Typo3SetList::TYPO3_87,
        Typo3SetList::TYPO3_95,
        Typo3SetList::TYPO3_104,
        // Typo3SetList::TYPO3_11,
    ]);

    // FQN classes are not imported by default. If you don't do it manually after every Rector run, enable it by:
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);

    // this will not import root namespace classes, like \DateTime or \Exception
    $parameters->set(Option::IMPORT_SHORT_CLASSES, false);

    // this will not import classes used in PHP DocBlocks, like in /** @var \Some\Class */
    $parameters->set(Option::IMPORT_DOC_BLOCKS, false);

    // Define your target version which you want to support
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_72);

    // If you would like to see the changelog url when a rector is applied
    $parameters->set(Typo3Option::OUTPUT_CHANGELOG, true);

    $parameters->set(Option::PATHS, [
        __DIR__ . '/Configuration',
        __DIR__ . '/pi1',
        __DIR__ . '/ext_emconf.php',
        __DIR__ . '/ext_localconf.php',
        __DIR__ . '/ext_tables.php',
        __DIR__ . '/ext_tables.php',
    ]);
    // If you set option Option::AUTO_IMPORT_NAMES to true, you should consider excluding some TYPO3 files.
    // If you use the option --config change __DIR__ to getcwd()
    $parameters->set(Option::SKIP, [
        NameImportingPostRector::class => [
            __DIR__ . '/**/Configuration/AjaxRoutes.php',
            __DIR__ . '/**/Configuration/Commands.php',
            __DIR__ . '/**/Configuration/Extbase/Persistence/Classes.php',
            __DIR__ . '/**/Configuration/RequestMiddlewares.php',
            __DIR__ . '/**/TCA/*',
            __DIR__ . '/ClassAliasMap.php',
            __DIR__ . '/Configuration/Extbase/Persistence/Classes.php',
            __DIR__ . '/Configuration/TCA/*',
            __DIR__ . '/Configuration/TCA/*',
            __DIR__ . '/ext_emconf.php',
            __DIR__ . '/ext_localconf.php',
            __DIR__ . '/ext_tables.php',
        ],
    ]);

    // If you have trouble that rector cannot run because some TYPO3 constants are not defined add an additional constants file
    // Have a look at https://github.com/sabbelasichon/typo3-rector/blob/master/typo3.constants.php
    // $parameters->set(Option::AUTOLOAD_PATHS, [
    //    __DIR__ . '/typo3.constants.php'
    // ]);

    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    // register a single rule
    $services->set(ExtensionComposerRector::class);
};
