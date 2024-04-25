Zimbra Upload
=============
PHP wrapper library around the Zimbra upload service.

## Requirement
* PHP 8.1.x or later,
* [PSR Discovery](https://github.com/psr-discovery) library for searching installed http clients and http message factories,
* (optional) PHPUnit to run tests,

## Installation
Via Composer
```bash
$ composer require zimbra-api/upload-api
```
or just add it to your `composer.json` file directly.

```javascript
{
    "require": {
        "zimbra-api/upload-api": "*"
    }
}
```

This package using [PSR-17: HTTP Factories](https://www.php-fig.org/psr/psr-17/), [PSR-18: HTTP Client](https://www.php-fig.org/psr/psr-18/) for creating multipart stream & sending HTTP requests to Zimbra upload service.
Make sure to install package(s) providing ["http client implementation"](https://packagist.org/providers/psr/http-client-implementation) & ["http factory implementation"](https://packagist.org/providers/psr/http-factory-implementation).
The recommended package is [Guzzle](https://docs.guzzlephp.org) which provide both PSR-17 & PSR-18.
```bash
$ composer require guzzlehttp/guzzle
```

## Basic usage of `zimbra` upload client
```php
<?php

require_once 'vendor/autoload.php';

use Zimbra\Upload\Client;
use Zimbra\Upload\Request;

$file = new \SplFileInfo($filePath);
$request = new Request([$file], $requestId, $authToken);
$client = new Client('https://zimbra.server/service/upload');
$attachments = $client->upload($request);
```
`$authToken` is user authentication token, it can obtain from zimbra soap api.
```php
<?php

require_once 'vendor/autoload.php';

use Zimbra\Account\AccountApi;

$api = new AccountApi('https://zimbra.server/service/soap');
$auth = $api->authByName('username', 'password');
$authToken = $auth->getAuthToken();
```

## Licensing
[BSD 3-Clause](LICENSE)

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
