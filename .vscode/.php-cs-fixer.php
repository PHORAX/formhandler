<?php

// liste der Regeln: https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/rules/index.rst

$finder = PhpCsFixer\Finder::create()
  ->exclude('vendor')
  ->exclude('Vendor')
  ->ignoreVCSIgnored(true)
;

return (new PhpCsFixer\Config())
  ->setRules([
    '@PhpCsFixer' => true,
    'braces' => [
      'position_after_functions_and_oop_constructs' => 'same',
    ],
    'no_whitespace_before_comma_in_array' => true,
    'whitespace_after_comma_in_array' => true,
    'blank_line_between_import_groups' => false,
    'ordered_class_elements' => [
      'sort_algorithm' => 'alpha',
    ],
    'ordered_imports' => [
      'sort_algorithm' => 'alpha',
    ],
    'class_attributes_separation' => [
      'elements' => [
        'const' => 'one',
        'method' => 'one',
        'property' => 'one',
        'trait_import' => 'one',
      ],
    ],
  ])
  ->setFinder($finder)
  ->setLineEnding("\n")
  ->setIndent('  ')
;
