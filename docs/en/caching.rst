Caching
=======

DoctrineModule provides some pre-configured `PSR-6 <https://www.php-fig.org/psr/psr-6/>`__
cache pools, implemented by `symfony/cache <https://github.com/symfony/cache>`__, which can
be utilized by either Doctrine ORM or Doctrine ODM.

Every ``doctrine.cache.*`` service resolves to a ``Psr\Cache\CacheItemPoolInterface``.

The following caches are available by default:

In-Memory
~~~~~~~~~

Backed by ``Symfony\Component\Cache\Adapter\ArrayAdapter``, you can pull this cache from
the container under the key ``doctrine.cache.array``. It does not persist anything beyond
the current request and suits merely as a proof of concept or for cases, where you do not
want to have caching.

Filesystem
~~~~~~~~~~

Backed by ``Symfony\Component\Cache\Adapter\FilesystemAdapter``, you can pull this cache
from the container under the key ``doctrine.cache.filesystem``. To override the location
for the cache storage folder, use the following configuration:

.. code:: php

    return [
        'doctrine' => [
            'cache' => [
                'filesystem' => [
                    'directory' => './data/cache/',
                ],
            ],
        ],
    ];

APCu
~~~~

Backed by ``Symfony\Component\Cache\Adapter\ApcuAdapter``. This cache requires the ``apcu``
extension to be installed and enabled.

You can pull the cache from the container using the key ``doctrine.cache.apcu``. To change
the namespace all cache ids are prefixed with, use the following config:

.. code:: php

    return [
        'doctrine' => [
            'cache' => [
                'apcu' => [
                    'namespace' => 'DoctrineModule',
                ],
            ],
        ],
    ];

Memcached
~~~~~~~~~

Backed by ``Symfony\Component\Cache\Adapter\MemcachedAdapter``. This cache requires the
``memcached`` extension to be installed and enabled.

The adapter is built on top of a connected ``\Memcached`` client, which only your
application can provide. Register that client in the container and point the ``instance``
option at it:

.. code:: php

    return [
        'dependencies' => [
            'factories' => [
                'doctrinemodule.cache.memcached_client' => MyMemcachedClientFactory::class,
            ],
        ],
        'doctrine' => [
            'cache' => [
                'memcached' => [
                    'instance' => 'doctrinemodule.cache.memcached_client',
                    'namespace' => 'DoctrineModule',
                ],
            ],
        ],
    ];

You can pull the cache from the container using the key ``doctrine.cache.memcached``.

Redis
~~~~~

Backed by ``Symfony\Component\Cache\Adapter\RedisAdapter``. This cache requires the
``redis`` extension to be installed and enabled.

As with Memcached, the adapter needs a connected ``\Redis`` client supplied by your
application:

.. code:: php

    return [
        'dependencies' => [
            'factories' => [
                'doctrinemodule.cache.redis_client' => MyRedisClientFactory::class,
            ],
        ],
        'doctrine' => [
            'cache' => [
                'redis' => [
                    'instance' => 'doctrinemodule.cache.redis_client',
                    'namespace' => 'DoctrineModule',
                ],
            ],
        ],
    ];

You can pull the cache from the container using the key ``doctrine.cache.redis``.

Using your own cache pool
~~~~~~~~~~~~~~~~~~~~~~~~~

Any PSR-6 implementation can be used, not just the adapters shipped by ``symfony/cache``.
Register the pool as a service and point the ``instance`` option at it; when the service
already implements ``Psr\Cache\CacheItemPoolInterface`` it is used as-is and the ``class``
option is ignored:

.. code:: php

    return [
        'dependencies' => [
            'factories' => [
                'my.cache.pool' => MyPsr6CachePoolFactory::class,
            ],
        ],
        'doctrine' => [
            'cache' => [
                'my_cache' => [
                    'instance' => 'my.cache.pool',
                ],
            ],
        ],
    ];

The pool is then available from the container as ``doctrine.cache.my_cache``.
