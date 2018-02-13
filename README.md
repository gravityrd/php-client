# php-client

[![Latest Stable Version](https://poser.pugx.org/gravityrd/php-client/v/stable)](https://packagist.org/packages/gravityrd/php-client)

[![Total Downloads](https://poser.pugx.org/gravityrd/php-client/downloads)](https://packagist.org/packages/gravityrd/php-client)


PHP client API to access [Gravity Research and Developments](https://www.gravityrd.com), for documentation please refer to this [link](https://developers.gravityrd.com/wiki/display/RECO/PHP).


## Installation

Preferred installation is with composer:

```
$ composer require gravityrd/php-client
```

Without composer:

```php
<?php
require 'path/to/GravityClient.php';

use Gravity\GravityClient;
use Gravity\GravityClientConfig;

$client = new GravityClient(new GravityClientConfig());
```

# Development

For development please use composer with development dependencies.