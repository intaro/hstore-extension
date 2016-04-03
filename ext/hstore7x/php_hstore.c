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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "zend_smart_str.h"
#include "php_hstore.h"
#include <zend_exceptions.h>

static PHP_MINFO_FUNCTION(hstore);
static PHP_FUNCTION(hstore_decode);
static PHP_FUNCTION(hstore_encode);

zend_class_entry *Coder_ce_ptr = NULL;

#define ERROR_UNEXPECTED_END 1
#define ERROR_SYNTAX 2
#define ERROR_UNKNOWN_STATE 3

#ifndef HASH_KEY_NON_EXISTENT
#define HASH_KEY_NON_EXISTENT HASH_KEY_NON_EXISTANT
#endif

/* {{{ arginfo */
ZEND_BEGIN_ARG_INFO_EX(arginfo_hstore_decode, 0, 0, 1)
    ZEND_ARG_INFO(0, hstore)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_hstore_encode, 0, 0, 1)
    ZEND_ARG_INFO(0, arr)
ZEND_END_ARG_INFO()

/* }}} */

/* {{{ Coder_class_functions
* Every 'Coder' class method has an entry in this table
*/
zend_function_entry Coder_class_functions[] = {
    ZEND_FENTRY( decode , ZEND_FN(hstore_decode) , arginfo_hstore_decode, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC )
    ZEND_FENTRY( encode , ZEND_FN(hstore_encode) , arginfo_hstore_encode, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC )
PHP_FE_END
};
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
*/
PHP_MINIT_FUNCTION( hstore )
{
    zend_class_entry ce;

    INIT_CLASS_ENTRY( ce, "Intaro\\HStore\\Coder", Coder_class_functions );
    ce.create_object = NULL;

    Coder_ce_ptr = zend_register_internal_class( &ce TSRMLS_CC );

    if( !Coder_ce_ptr ) {
        zend_error( E_ERROR,
            "HStore: Failed to register Coder class.");
        return;
    }
}

/* {{{ PHP_MINFO_FUNCTION */

/* {{{ hstore_module_entry
 */
zend_module_entry hstore_module_entry = {
    STANDARD_MODULE_HEADER,
    "hstore",
    NULL,
    PHP_MINIT(hstore),
    NULL,
    NULL,
    NULL,
    PHP_MINFO(hstore),
    PHP_HSTORE_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_HSTORE
ZEND_GET_MODULE(hstore)
#endif

/* }}} */

static PHP_MINFO_FUNCTION(hstore)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "HStore support", "enabled");
    php_info_print_table_row(2, "HStore version", PHP_HSTORE_VERSION);
    php_info_print_table_end();
}
/* }}} */


typedef struct
{
    char *ptr;
    char *ptr_max;

    zval value;
    int error;
} HSParser;

static zend_bool hstore_parse_value(HSParser *state, smart_str *buf, zend_bool ignoreeq)
{
    #define GV_IN_VAL      1
    #define GV_IN_ESC_VAL  2
    #define GV_WAIT_ESC_IN 3

    int st;

    char * value_start = state->ptr;

    ZSTR_LEN(buf->s) = 0;

    ZVAL_NULL(&state->value);

	zend_bool escaped     = 0;
    zend_bool use_buffer  = 0;
    zend_bool last_symbol = 1;

    register char ch = *(state->ptr);

    // we already skip leading spaces in hstore_parse
    if ('"' == ch) {
        escaped = 1;
        ++value_start;
        st = GV_IN_ESC_VAL;

        while (1) {
            ++state->ptr;

            if (state->ptr >= state->ptr_max) {
                state->error = ERROR_UNEXPECTED_END;
                return 0;
            }

            char ch = *(state->ptr);

            if (st == GV_IN_ESC_VAL) {
                if ('\\' == ch) {
                    st = GV_WAIT_ESC_IN;
                } else if ('"' == ch) {
                    last_symbol = 0;
                    break;
                } else if (use_buffer) {
                    smart_str_appendc(buf, ch);
                }
            } else if (st == GV_WAIT_ESC_IN) {
                if (!use_buffer) {
                    use_buffer = 1;
                    int nlen = state->ptr - value_start - 1;
                    if (nlen > 0) {
                        smart_str_appendl(buf, value_start, nlen);
                    }
                }

                smart_str_appendc(buf, ch);

                st = GV_IN_ESC_VAL;
            } else {
                state->error = ERROR_UNKNOWN_STATE;
                return 0;
            }
        }
    } else {
        if ('=' == ch && !ignoreeq) {
            state->error = ERROR_SYNTAX;
            return 0;
        } else if ('\\' == ch) {
            st = GV_WAIT_ESC_IN;
            ++state->ptr;
        }

        st == GV_IN_VAL;

        for (;; ++state->ptr) {
            if (state->ptr >= state->ptr_max) {
                if (st != GV_IN_VAL) {
                    state->error = ERROR_UNEXPECTED_END;
                    return 0;
                }

                last_symbol = 0;
                break;
            }

            ch = *(state->ptr);

            if (st == GV_IN_VAL) {
                if ('\\' == ch) {
                    st = GV_WAIT_ESC_IN;
                } else if ('=' == ch && !ignoreeq) {
                    --state->ptr;
                    break;
                } else if (',' == ch && ignoreeq) {
                    --state->ptr;
                    break;
                } else if (isspace((unsigned char) ch)) {
                    last_symbol = 0;
                    break;
                } else if (use_buffer) {
                    smart_str_appendc(buf, ch);
                }
            } else if (st == GV_WAIT_ESC_IN) {
                if (!use_buffer) {
                    use_buffer = 1;
                    int nlen = state->ptr - value_start - 1;
                    if (nlen > 0) {
                        smart_str_appendl(buf, value_start, nlen);
                    }
                }

                smart_str_appendc(buf, ch);

                st = GV_IN_VAL;
            } else {
                state->error = ERROR_UNKNOWN_STATE;
                return 0;
            }
        }
    }

	char * val_start;
	size_t val_len;

    // return parsed value
    if (use_buffer) {
        val_start = ZSTR_VAL(buf->s);
        val_len   = ZSTR_LEN(buf->s);
    } else {
        val_start = value_start;
        val_len = state->ptr - value_start + last_symbol;
    }

	#define isnull_keyword(s) ( \
        (('n' == s[0]) || ('N' == s[0])) && \
        (('u' == s[1]) || ('U' == s[1])) && \
        (('l' == s[2]) || ('L' == s[2])) && \
        (('l' == s[3]) || ('L' == s[3])) \
    )

	if (ignoreeq && !escaped && 4 == val_len && isnull_keyword(val_start)) {
        //
    } else {
        ZVAL_STRINGL(&state->value, val_start, val_len);
    }

    return 1;
}

static inline void hstore_parse(HSParser *state, zval *result) /* {{{ */
{
    #define WKEY  0
    #define WVAL  1
    #define WEQ   2
    #define WDEL  4

    state->error   = 0;

    int st = WKEY;

    smart_str buf = {};
    smart_str_alloc(&buf, 1024, 0);

	zend_string *key;

    while (state->ptr < state->ptr_max) {
        char ch = *(state->ptr);

        if (isspace((unsigned char) ch)) {
            ++state->ptr;
            continue;
        }

		if (st == WKEY) {
            if (!hstore_parse_value(state, &buf, 0)) {
                goto free;
            }
            key = zval_get_string(&state->value);

            st = WEQ;
        } else if (st == WEQ) {
            if (state->ptr + 2 > state->ptr_max) {
                state->error = ERROR_UNEXPECTED_END;
                goto free;
            }

            if ('=' == *(state->ptr) && '>' == *(state->ptr + 1)) {
                st          = WVAL;
                state->ptr += 2;

                continue;
            }

            state->error = ERROR_SYNTAX;
            return;
        } else if (st == WVAL) {
            if (!hstore_parse_value(state, &buf, 1)) {
                goto free;
            }

            zend_symtable_update(Z_ARRVAL_P(result), key, &state->value);
            zend_string_release(key);
            zend_string_release(key);

            st = WDEL;
        } else if (st == WDEL) {
            if (',' == ch) {
                st = WKEY;
            } else {
                state->error = ERROR_SYNTAX;
                goto free;
            }
        } else {
            state->error = ERROR_UNKNOWN_STATE;
            goto free;
        }

        state->ptr++;
    }

    if (st != WKEY && st != WDEL) {
        state->error = ERROR_UNEXPECTED_END;
    }

free:
    smart_str_free(&buf);
}

/* {{{ proto mixed hstore_decode(string hstore)
   Decodes the HStore string into a PHP array */
static PHP_FUNCTION(hstore_decode)
{
    char *str;
    size_t str_len;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &str, &str_len) == FAILURE) {
        return;
    }

    HSParser state;
    state.ptr     = str;
    state.ptr_max = str + str_len;

    array_init(return_value);

    hstore_parse(&state, return_value);

    if (0 == state.error) {
        //
    } else if (ERROR_UNKNOWN_STATE == state.error) {
        zend_throw_exception(NULL, "Unknown state", 0 TSRMLS_CC);
    } else if (ERROR_UNEXPECTED_END == state.error) {
        zend_throw_exception(NULL, "Unexpected end of string", 0 TSRMLS_CC);
    } else if (ERROR_SYNTAX == state.error) {
        zend_throw_exception(NULL, "Syntax error", 0 TSRMLS_CC);
    } else {
        zend_throw_exception(NULL, "Internal error", 0 TSRMLS_CC);
    }
}
/* }}} */

static void escape_string(smart_str *buf, zend_string *str) /* {{{ */
{
	size_t str_len = ZSTR_LEN(str);

    if (!str_len) {
        return;
    }

    // preallocate
    smart_str_alloc(buf, str_len + 2, 0);

    char *ptr = ZSTR_VAL(str);
    char *ptr_max = ptr + str_len;

    char *start = ptr;
    char ch = *ptr;

    for (; ptr < ptr_max; ptr++, ch = *ptr) {
        if ('"' == ch || '\\' == ch) {
            if (ptr > start) {
                smart_str_appendl(buf, start, ptr - start);
            }
            start = ptr;

            smart_str_appendc(buf, '\\');
        }
    }

    if (ptr > start) {
        smart_str_appendl(buf, start, ptr - start);
    }
}

/* {{{ proto mixed hstore_encode(array hstore)
   Decodes the hStore representation into a PHP value */
static PHP_FUNCTION(hstore_encode)
{
    zval *array, *value;
	zend_ulong num_idx;
    zend_string *str_idx;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "a", &array) == FAILURE) {
        return;
    }

    smart_str buf = {};
    smart_str_alloc(&buf, 2048, 0);

    zend_bool need_comma = 0;

    ZEND_HASH_FOREACH_KEY_VAL(Z_ARRVAL_P(array), num_idx, str_idx, value) {
        if (need_comma) {
            smart_str_appendl(&buf, ", ", 2);
        } else {
            need_comma = 1;
        }

        smart_str_appendc(&buf, '"');

        if (str_idx) {
            escape_string(&buf, str_idx);
        } else {
            smart_str_append_long(&buf, (long) num_idx);
        }

        smart_str_appendl(&buf, "\"=>", 3);

        if (Z_TYPE_P(value) == IS_NULL) {
            smart_str_appendl(&buf, "NULL", 4);
        } else {
            convert_to_string_ex(value);

            smart_str_appendc(&buf, '"');
            zend_string *str = zval_get_string(value);
            escape_string(&buf, str);
            zend_string_release(str);
            smart_str_appendc(&buf, '"');
        }
    } ZEND_HASH_FOREACH_END();

	smart_str_0(&buf); /* copy? */
	ZVAL_NEW_STR(return_value, buf.s);
}
/* }}} */
