# AssetManager
By [Wesley Overdijk](http://blog.spoonx.nl/) and [Marco Pivetta](http://ocramius.github.com/)

Updated to laminas by [Johan van der Heide](https://jield.nl)


## Introduction
This module is intended for usage with a default directory structure of a
[LaminasSkeletonApplication](https://github.com/laminasframework/LaminasSkeletonApplication/). It provides functionality to load
assets and static files from your module directories through simple configuration.
This allows you to avoid having to copy your files over to the `public/` directory, and makes usage of assets very
similar to what already is possible with view scripts, which can be overridden by other modules.
In a nutshell, this module allows you to package assets with your module working *out of the box*.

## Installation

 1.  Require assetmanager:

```sh
./composer.phar require jield-webdev/laminas-assetmanager
#when asked for a version, type "3.*".
```

## Usage


**Sample module config:**

```php
<?php

return [
    'asset_manager' => [
        'resolver_configs' => [
            'collections' => [
                'js/d.js' => [
                    'js/a.js',
                    'js/b.js',
                    'js/c.js',
                ],
            ],
            'paths'       => [
                __DIR__ . '/some/particular/directory',
            ],
            'map'         => [
                'specific-path.css' => __DIR__ . '/some/particular/file.css',
            ],
        ],
        'filters'          => [
            'js/d.js' => [
                [
                    // Note: You will need to require the classes used for the filters yourself.
                    'filter' => 'JSMin',
                ],
            ],
        ],
        'view_helper'      => [
            'cache'            => 'Application\Cache\Redis', // You will need to require the factory used for the cache yourself.
            'append_timestamp' => true,                      // optional, if false never append a query param
            'query_string'     => '_',                       // optional
        ],
        'caching'          => [
            'js/d.js' => [
                'cache' => 'Apc',
            ],
        ],
    ],
];
```

*Please be careful, since this module will serve every file as-is, including PHP code.*

The task list has been slimmed down a lot lately. However, there are still a couple of things that should be done.

 * Renewing the cache
