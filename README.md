# Discord Provider

## Installation

```
composer require socialite-manager/discord-provider
```

## Usage

```php
use Socialite\Provider\DiscordProvider;
use Socialite\Socialite;

Socialite::driver(DiscordProvider::class, $config);
```
