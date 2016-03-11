/*
+----------------------------------------------------------------------+
| HStore Extension                                                     |
+----------------------------------------------------------------------+
| Copyright (c) 2015 Sergey Linnik                                     |
+----------------------------------------------------------------------+
| Redistribution and use in source and binary forms, with or without   |
| modification, are permitted provided that the conditions mentioned   |
| in the accompanying LICENSE file are met.                            |
+----------------------------------------------------------------------+
| Author: Sergey Linnik <linniksa@gmail.com>                           |
+----------------------------------------------------------------------+
*/

#ifndef PHP_HSTORE_H
#define PHP_HSTORE_H

#define PHP_HSTORE_VERSION "1.0.0"

#include "ext/standard/php_smart_str.h"

extern zend_module_entry hstore_module_entry;
#define phpext_hstore_ptr &hstore_module_entry

#endif  /* PHP_HSTORE_H */
