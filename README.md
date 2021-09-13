# Iutrace Dashboard

### Installation
1. `composer require iutrace/laravel-dashboard`
3. Publish the config file by running `php artisan vendor:publish --provider="Iutrace\Dashboard\DashboardServiceProvider"`. The config file will indicate the directory where the metrics are located.

### Testing

```bash
docker run -it --rm --name iutrace-dashboard -e PHP_EXTENSIONS="" -v "$PWD":/usr/src/app thecodingmachine/php:7.4-v4-cli bash
composer test
```