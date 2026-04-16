# Struktal-DEV-Core

DEV-Core library for the Struktal PHP framework

> [!NOTE]
> The Struktal-DEV-Core library provides tools and utilities for developers working with the Struktal PHP framework.
Right now, it wraps the [Pest testing framework](https://pestphp.com/) and [Playwright for PHP](https://playwright-php.dev/), which could also both be included in the project directly to be able to use the latest features.
However, if you choose to require this wrapper "library", you will receive less update notifications for those libraries and with each update for this DEV-Core library, you will be informed if there are any breaking changes connected to the Struktal framework, and how they can be resolved.

## Installation

To install this library, include it in your project using Composer:

```bash
composer require --dev struktal/struktal-dev-core
```

## Dependencies

- **Pest**: GitHub: [pestphp/pest](https://github.com/pestphp/pest), licensed under [MIT license](https://github.com/pestphp/pest/blob/4.x/LICENSE.md)
- **Playwright for PHP**: GitHub: [playwright-php/playwright](https://github.com/playwright-php/playwright), licensed under [MIT license](https://github.com/playwright-php/playwright/blob/main/LICENSE)

## License

This software is licensed under the MIT license.
See the [LICENSE](LICENSE) file for more information.
