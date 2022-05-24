# Laravel Media Manager

## Install

Download package

`composer require zencoreitservices\laravel-media-manager`

Add provider to you `app.php` config file

`Zencoreitservices\MediaManager\MediaManagerProvider::class`

Publish config file

`php artisan vendor:publish --tag=media-manager-config`

Migrate database

`php artisan migrate`
