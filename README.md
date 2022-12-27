# Laravel SQL for described relations Generator

**Scrap production data and fill it on develop environent**

This package generates SQL commands (MySQL syntax for now only) can be used to fill development enviroment with real (but anonymized) data.

It supports Laravel 7+ and PHP 7.4+

- [Installation](#installation)
- [Usage](#usage)
- [License](#license)

## Installation

Require this package with composer using the following command:

```bash
composer require kickenhio/laravel-sql-snapshot
```

And then, for service registration purposes run command:

```bash
php artisan vendor:publish --provider="Kickenhio\LaravelSqlSnapshot\SnapshotServiceProvider"
```

## Usage

This package allows to generate SQL snapshot for entrypoint models base on prepared manifest file.
Additionaly can be used with another repository of mine - "Snapshot APP" - can be used among not technical QA employees.
Also with encryption support, preserving data mining if API credentials compromised.

## License

Didn't think about it.
