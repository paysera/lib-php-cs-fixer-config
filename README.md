# lib-php-cs-fixer-config
Library helps to fix PHP code to conform Paysera coding standards.

## Installation

#### Prerequisite

* Add `Paysera\\PhpCsFixerConfig\\Composer\\PhpCsFixerConfigProvider::copyPhpCs` script to `post-install-cmd` and `post-update-cmd`
 or other `scipts` - just make sure this script is executed on `composer install`.
* Add `"config": {"bin-dir": "bin/"}` to your `composer.json`

#### Install and check
* `composer require --dev paysera/lib-php-cs-fixer-config`.
* Make sure `.php_cs` file is in project directory.

###### .php_cs files
* `.php_cs` - all Paysera recommended fixers.
* `.php_cs_risky` - all risky fixers except recommendations ( comment warnings ).
* `.php_cs_safe` - all non risky fixers.

## Usage

Run in project directory by command: `{your-bin-dir}/php-cs-fixer fix /path/to/code --verbose --dry-run --diff`

Use `--config=.php_cs` flag for custom configuration.

If `/path/to/code` is not defined `php-cs-fixer` will run files from default `src` directory excluding `Test` folders.

`--verbose` - show the applied rules. When using the txt format it will also displays progress notifications.

A combination of `--dry-run` and `--diff` will display a summary of proposed fixes, leaving your files unchanged.

`--format` option for the output format. Supported formats are `txt` (default one), `json`, `xml` and `junit`.


More information: [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
