# Pest Plugin Watch

A Pest plugin to watch files and restart Pest when they change.

This is a Pest Watch plugin that works with Pest v4 and differs from the official Pest Watch plugin which became unsupported. It's now built on the `spatie/file-system-watcher` package which uses the node package `chokidar` under the hood for efficient file watching.

## Installation

You can install the package via composer:

```bash
composer require petecoop/pest-plugin-watch --dev
```

In your project, you should have the JavaScript package [`chokidar`](https://github.com/paulmillr/chokidar) installed. You can install it via npm

```bash
npm install chokidar --save-dev
```

If you are using [Bun](https://bun.sh/), you can install it via:

```bash
bun a -d chokidar
```

The watcher will automatically use `Bun` if there is a `bun.lock` file in your project.

## Usage

To start Pest in watch mode, run the following command:

```bash
pest --watch
```

This will restart Pest whenever a file in `src`, `app` or `tests` changes.

You can ovverride the default paths by providing your own paths, for example inside your `Pest.php` file:

```php
Petecoop\PestWatch\Plugin::directories(['src', 'tests', 'custom-dir']);
```
