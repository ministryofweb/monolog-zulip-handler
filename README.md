# Zulip Handler for Monolog

This is a handler class for the [Monolog logging library][monolog] which allows it to send log messages to a [Zulip] instance. 

## Requirements

PHP 7.0 with enabled OpenSSL extension.

## Installation

```
composer require ministryofweb/monolog-zulip-handler
```

## Usage

Just push a `ZulipHandler` instance to your logger:

```php
<?php

use Monolog\Logger;
use MinistryOfWeb\Monolog\Handler\ZulipHandler;

require_once 'vendor/autoload.php';

$logger = new Logger('zulip');
$logger->pushHandler(
    new ZulipHandler(
        'zulip.example.com',
        'logging-bot@zulip.example.com',
        'flokquomwaysufdipsoccupeojajipjeoliv',
        'Application Errors',
        'appname',
        Logger::ERROR
    )
);
```

Constructor arguments in order:

- hostname of the destination Zulip instance
- Username of the Zulip bot
- API token for the Zulip bot
- Stream name or recipient username
- optional topic, it's possible to pass an empty string for no topic (which is the default)
- minimum level required for handling log messages

[monolog]: https://seldaek.github.io/monolog/
[Zulip]: https://zulipchat.com/
