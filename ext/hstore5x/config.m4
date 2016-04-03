dnl config.m4 for extension hstore

PHP_ARG_ENABLE(hstore, whether to enable hstore support,
[ --enable-hstore Enable hstore support])

if test "$PHP_HSTORE" != "no"; then
  PHP_NEW_EXTENSION(hstore, php_hstore.c, $ext_shared)
fi
