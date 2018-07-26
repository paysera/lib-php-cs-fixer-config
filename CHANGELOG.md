# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Unreleased

### Changed

- `Paysera/php_basic_feature_unnecessary_variables` now is not so aggressive as previously and handles only
some of previous cases. Previous behavior in many cases made the code less readable and/or changed the execution
order of function calls etc.

### Fixed

- `Paysera/php_basic_code_style_namespaces_and_use_statements` was fixed for cases where comma is after the class name,
like when implementing a few interfaces.

## 1.7.4

### Changed

- Downgraded `doctrine/inflector` from `^1.1` to `~1.0.0`

## 1.7.3

### Changed

- Include autoload in `.php_cs*` config files
- Lock `friendsofphp/php-cs-fixer` version on `2.11.1`

## 1.7.2

### Changed

- Require `friendsofphp/php-cs-fixer` only in dev

## 1.7.1
### Fixed
- Changed `friendsofphp/php-cs-fixer` required version to be below `2.11` since it introduced breaking changes causing tests to fail.

## 1.7.0

### Added
- `Paysera/php_basic_comment_php_doc_necessity` removed doc blocks which doesn't reflect any used parameters, return types or thrown exceptions.

## 1.6.1
### Fixed
- `Paysera/php_basic_code_style_comparison_order` now respects blocks, i.e. `(false === someFunction())`

## 1.6.0

### Added
- `Paysera/php_basic_code_style_doc_block_whitespace` removes extra whitespaces from doc block annotations.

## 1.5.0

### Added
- More permissive `friendsofphp/php-cs-fixer` dependency constraint.
- Future compatibility added to `PayseraConventionsConfig`.


## 1.4.0

### Changed
- `Paysera/php_basic_code_style_namespaces_and_use_statements`:
  * changes all occurrences FQCNs to imported use statements.
  * does not import namespaces without root prefix `\Some\Name\Space` vs `Some\Name\Space`.
  * properly imports global classes i.e.: `\DateTime`

## 1.3.0

### Changed
- `Paysera/php_basic_feature_checking_explicitly` fixer now changes `isset` if `empty` checks array key, i.e.:
`!empty($a['b']) -> isset($a['b'])`
`!empty($a) -> count($a) > 0`
`empty($a) -> count($a) === 0`


## 1.2.0

### Fixed
- `Paysera/php_basic_comment_php_doc_on_properties` fixer now supports `array` typehint. 
- `Paysera/php_basic_feature_type_hinting_arguments` fixer now properly handles primitive nullable DocBlocks i.e. `@param int|null`
- `Paysera/php_basic_feature_void_result` fixer now recognizes return block inside callback.
- `Paysera/php_basic_code_style_namespaces_and_use_statements` fixer now adds `Base` if extended class has same name as its parent.

### Added
- `Paysera/php_basic_feature_return_and_argument_types` now supports configuration of whitelisted classes,
 which presence in `@return` or `@param` DocBlock will not trigger this fixer:
```php
'Paysera/php_basic_feature_return_and_argument_types' => [
    'whitelist' => ['ArrayCollection'],
],
```
Old configuration is still valid: `'Paysera/php_basic_feature_return_and_argument_types' => true`, 
this will result in same configuration as above.

### Changed
- `\Paysera\PhpCsFixerConfig\Config\PayseraConventionsConfig` now provides methods for predefined configurations.
- `Paysera/php_basic_code_style_method_naming` fixer now ignores `process` and `handle` patterns.
- `Paysera/php_basic_feature_type_hinting` fixer now skips if typehint starts with `Repository`

## 1.1.0

### Added
- `Paysera/psr_1_file_side_effects` now supports configuration of forbidden functions and tokens, i.e.:
```php
'Paysera/psr_1_file_side_effects' => [
    'functions' => ['print_r', 'var_dump', 'ini_set'],
    'tokens' => [T_ECHO, T_INCLUDE]
],
```
Old configuration is still valid: `'Paysera/psr_1_file_side_effects' => true`, 
this will result in same configuration as above.

- `Paysera/php_basic_feature_visibility_properties` now supports configuration of Parent class names
where in Children classes it is allowed to use `public` or `protected` properties.
This is useful in Symfony Validation Constraints, i.e.:
```php
'Paysera/php_basic_feature_visibility_properties' => [
    'Constraint'
],
```
Old configuration is still valid: `'Paysera/php_basic_feature_visibility_properties' => true`, 
this will result in same configuration as above.

- `Paysera/php_basic_code_style_class_naming` now supports valid and invalid Class suffixes, i.e.:
```php
'Paysera/php_basic_code_style_class_naming' => [
    'valid' => [
        'Registry',
        'Factory',
        'Client',
        'Plugin',
        'Proxy',
        'Interface',
        'Repository',
    ],
    'invalid' => [
        'Service'
    ]
],
```
Old configuration is still valid: `'Paysera/php_basic_code_style_class_naming' => true`, 
this will result in same configuration as above.

### Changed
- `Paysera/php_basic_feature_type_hinting` now supports configuration of exceptions which usage does not require the narrowest scope.
This is useful in Symfony Validation Constraints, i.e.:
```php
'Paysera/php_basic_feature_type_hinting' => [
    'EntityManager'
],
```
Old configuration is still valid: `'Paysera/php_basic_feature_type_hinting' => true`, 
this will result in same configuration as above.

- `Paysera/php_basic_feature_unnecessary_variables` now supports configuration of methods, call of which will disable the fix.
This is useful in Symfony Validation Constraints, i.e.:
```php
'Paysera/php_basic_feature_unnecessary_variables' => [
    'flush'
],
```
Old configuration is still valid: `'Paysera/php_basic_feature_unnecessary_variables' => true`, 
this will result in same configuration as above.

- `Paysera/php_basic_code_style_namespaces_and_use_statements` now parses namespaces in docBlocks only if preceded by:
  - `@throws`
  - `@param`
  - `@return`
  - `@returns`
  - `@var`


### Removed
- Support of `PHP5.5`
