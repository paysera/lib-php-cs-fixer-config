# lib-php-cs-fixer-config ![](https://travis-ci.org/paysera/lib-php-cs-fixer-config.svg?branch=master)
Library helps to fix PHP code to conform [Paysera PHP style guide](https://github.com/paysera/php-style-guide).

## Installation

### Prerequisite

* Add `Paysera\\PhpCsFixerConfig\\Composer\\PhpCsFixerConfigProvider::copyPhpCs` script to `post-install-cmd` and `post-update-cmd`
 or other `scripts` - just make sure this script is executed on `composer install`.

### Install and check
* `composer require --dev paysera/lib-php-cs-fixer-config`.
* Make sure `.php_cs` file is in project directory.

No need to install php-cs-fixer itself as this library comes with binary version of the fixer.
This avoids requiring it's dependencies inside your project, which could clash with existing ones.

To avoid duplication with php-cs-fixer library, it's named `paysera-php-cs-fixer`.

##### .php_cs files
* `.php_cs` - all Paysera recommended fixers.
* `.php_cs_risky` - all risky fixers except recommendations (comment warnings).
* `.php_cs_safe` - all non risky fixers.

### Migration mode

For new projects you can just use all the rules as usual.

For existing projects we recommend turning on the migration mode:
1. Add call to `enableMigrationMode([])` to `PayseraConventionsConfig` instance in your `.php_cs` file.
2. Run `{your-bin-dir}/php-cs-fixer` - it will give error with initial rule configuration to pass into that method.
Just copy-and-paste it to your `.php_cs` file.
3. Enable one of the rules, apply fixes in the project, review and test them.
4. Repeat with each new rule.

This allows to control which rules are enabled in the project thus letting manually tune the fixes already applied
in the repository and forced for the new code. Also your commits will be more focused as each of them will include only
changes from a single fixer.

All rules are to be configured to allow easily spotting new rules in case they would be added (or removed) into the core.

### Running fixer with tests

For comments or suggestions for developers you should use default `.php_cs` file with all the rules.

For automatic checks there might be some false-positives so `.php_cs_risky` should be used in such cases.

You can look at `.travis.yml` file in this repository for integration with travis on each pull request of your repository
(this will run the checks only for changed files).

## Usage

Run in project directory by command: `{your-bin-dir}/php-cs-fixer fix /path/to/code --verbose --dry-run --diff`

Use `--config=.php_cs` flag for custom configuration.

If `/path/to/code` is not defined `php-cs-fixer` will run files from default `src` directory excluding `Test` folders.

`--verbose` - show the applied rules. When using the txt format it will also displays progress notifications.

A combination of `--dry-run` and `--diff` will display a summary of proposed fixes, leaving your files unchanged.

`--format` option for the output format. Supported formats are `txt` (default one), `json`, `xml` and `junit`.

More information: [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
