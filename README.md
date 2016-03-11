HStore Extension
==================

PostgreSQL module `hstore` allows to store sets of key/value pairs within a single PostgreSQL value. More about it [here](http://www.postgresql.org/docs/current/static/hstore.html).

The HStore Extension contains DBAL type `hstore` and registers Doctrine type `hstore`.

Installation
------------

To install this library, run the command below and you will get the latest version:

```sh
composer require intaro/hstore-extension
```

If you want to run the tests:

```sh
./vendor/bin/phpunit
```

You can find an example configuration for using HStore extension in Symfony2 in [config/hstore.yml](config/hstore.yml).
You can just include in you `config.yml`:

```yml
imports:
    - { resource: ../../vendor/intaro/hstore-extension/config/hstore.yml }
```

PHP extension
-------------

To speed up encoding/decoding of strings you can install C extension shipped in `ext` directory.
Use appropriate folder for you php version `hstore5x` for `5.x` and `hstore7x` for `7.x`.

To compile extension you must install php-dev package.

```bash
phpize
./configure
make
sudo make install
```

Finally, enable the extension in your `php.ini` configuration file:

```ini
extension = hstore.so
```
