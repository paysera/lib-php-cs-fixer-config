# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

Semantic Versioning is maintained only for the following:
- available fixer IDs and their configuration;
- any classes or methods with `@api` annotation.

The fixers themselves can change their behavior on any update.
New fixers could be added with minor releases, this would require changes in configuration if migration mode is used.

## 2.4.0

### Added

- added support for php 7.4 typed properties declarations
- added support for php 7.4 constants with access modifiers

## 2.3.0

### Changed

- `php-cs-fixer` is updated to `2.16.3` version, including binary `paysera-php-cs-fixer` that's distributed with this
library.

## 2.2.3

### Removed

- Some functions without substitutions in PHP Date/Time classes were removed from DateTimeFixer

## 2.2.2

### Added

- `@php-cs-fixer-ignore` annotation to ignore fixer for specific file
- Abstraction layer added for parsing specific parts of classes and methods. Also
abstract fixer added to ease fixing – uses linked list of tokens instead of index-based array

### Changed

- `Paysera/php_basic_comment_php_doc_on_properties` does not require PhpDoc on properties
that are assigned a new instance of some class inside the constructor. Leaves the comment
if it contains description of the property.
- `Paysera/php_basic_code_style_namespaces_and_use_statements` imports all classes with
still unused short class name. Previously used black-list was removed (for example, current
fixer correctly imports `\Exception` class). Also supports classes with import aliases.
- `Paysera/php_basic_feature_type_hinting` supports more use-cases with aliased imports
or without them. It changes the type-hint to the suggested one. Fixer moved from recommendation
to risky fixers group.

### Removed

- `Paysera/php_basic_comment_php_doc_necessity` was completely removed as it clears descriptions
of classes and/or methods, additional annotations and is not defined by any style guide rule.

### Fixed

- `Paysera/php_basic_code_style_default_values_in_constructor` was producing invalid PHP code
with some cases, most importantly when using `strict_types` declaration.

## 2.2.1

### Added

- This library now brings php-cs-fixer binary with itself named `paysera-php-cs-fixer`.
This binary is kept compatible with current version of fixer config and is installed with
this library – no need to install php-cs-fixer separately as a library or globally.

## 2.2.0

### Changed

- Requirement of PHP CS Fixer was raised to 2.14.2

## 2.1.0

### Added

- `Paysera/php_basic_code_style_default_values_in_constructor` to move default property
values initialization to contructor.

## 2.0.3

### Changed
- Allow multi-line implements according to [PSR-2 Coding Style Guide][1], which will make the following code acceptable:
```php
use ArrayAccess;
use Countable;
use Serializable;

class ClassName extends ParentClass implements
    ArrayAccess,
    Countable,
    Serializable
{
    // constants, properties, methods
}
```
[1]: https://www.php-fig.org/psr/psr-2/#41-extends-and-implements

## 2.0.2

### Fixed
- `TypeHintingFixer` validation exceptions
- Updates exceptions list

## 2.0.1

### Fixed
- Count of null on PHP 7.2 (#5)

## 2.0.0

### Added
- Migration mode was added for enabling only some of the rules. This allows to change the code style gradually
rule-by-rule.

### Changed

- `Paysera/php_basic_feature_return_and_argument_types` does not allow to pass any configuration options.
- Default configuration files were refactored to be based on single file (`.php_cs`) and to follow the style guide.
- API is changed for semantic versioning - only the fixer configuration will be maintained for backward compatibility,
you should not extend or use any of the fixers (or other classes) directly.
If you do, please test after every update of this library.

### Removed
- `Paysera/php_basic_comment_php_doc_on_methods` fixer (with `PhpDocOnMethodsFixer` class) was removed.
The fixer did not perform the fixes needed and performed some that were not intended.

### Fixed

- `Paysera/php_basic_code_style_namespaces_and_use_statements` was fixed for cases where comma is after the class name,
like when implementing a few interfaces.
- `Paysera/php_basic_feature_unnecessary_variables` now is not so aggressive as previously and handles only
some of previous cases. Previous behavior in many cases made the code less readable and/or changed the execution
order of function calls etc.
- `Paysera/php_basic_code_style_method_naming` changed to correspond with the convention - for question-type
functions it's required to return boolean. If we're not sure of the return type, don't add the warning. Also
entity function prefix checks were removed, as conventions does not state that Entity cannot have any other
function than with defined prefixes.
- `Paysera/php_basic_code_style_splitting_in_several_lines` was completely refactored and remade to follow current
conventions and to handle much more cases.
- `no_unneeded_control_parentheses` was disabled as it works too aggressively in some cases.
- `self_accessor` rule disabled.
- `Paysera/psr_2_line_length` allows lines with long constant tokens (like strings),
also adds comments before the line that's too long;
- `Paysera/php_basic_feature_return_and_argument_types` was improved to handle cases with iterables and generators.
- `Paysera/php_basic_feature_comparing_to_null` was improved to allow checking boolean values that can also be null.
- `Paysera/php_basic_code_style_doc_block_whitespace` allows DocBlock to have indentation in description.
- `curly_bbrace_block` in `no_extra_blank_lines` was disabled in default configuration to allow empty lines between
`elseif` statements.
- `Paysera/php_basic_feature_calling_parent_constructor` allows statements before calling parent constructor
if they do not modify the state of the object. This could be needed for readability or modifying constructor
parameters beforehand.
- `braces` fixer was exchanged for patched version in `Paysera/php_basic_braces` which fixes cases with `elseif`
statements.
- `Paysera/php_basic_feature_comparing_to_boolean` now only fixes variables which are defined as boolean-only
in function arguments with type-hint or in DocBlock. It's also marked as risky.
- Disabled risky fixers from `.php_cs_safe` to be able to run it.

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
