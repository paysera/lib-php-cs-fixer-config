# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

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
