# Laraman

Have you ever been building a huge API and you're sitting there with Postman to quickly look at your requests? It's tedious, right? So much copy-pasting routes and keeping track of them. Maybe at one point you started making some Postman collections? What if there was a better way, an easier way? There is. Laraman allows you to export all the routes that are registered in your application to a json file that can be imported into Postman.

## Installation
Install via composer:
```
composer require --dev rl-studio/laraman
```

Add the service provider to your `providers` array in `config/app.php`

```php
RLStudio\Laraman\ServiceProvider::class,
```

That's all!

## Usage

To run the command, simply use

```
php artisan laraman:export
```

This will place a `laraman-export.json` inside your `storage/app` folder. You are free to change the name of the file by specifying the filename as follows:

```
php artisan laraman:export --name=my-app
```

You can also specify the route types as follows:

Only `API` routes
```
php artisan laraman:export --api
```
Only `Web` routes
```
php artisan laraman:export --web
```
`All` available routes (default)
```
php artisan laraman:export
```

If you need to change the default port (8000), then specify the port as follows:

```
// This will set the port to 9000
// Example: http://localhost:9000/users

php artisan laraman:export --port=9000
```
