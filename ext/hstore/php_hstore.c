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
#include "ext/standard/php_smart_str.h"
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
    char *begin;
    int begin_len;

    char *key;
    int key_len;

    char *ptr;
    char *ptr_max;

    char *word;
    int word_len;
    smart_str *buf;

    zval *z;
    int error;
} HSParser;

static inline zend_bool hstore_parse_value(HSParser *state, zend_bool ignoreeq, zend_bool *escaped)
{
    #define GV_WAIT_VAL    0
    #define GV_IN_VAL      1
    #define GV_IN_ESC_VAL  2
    #define GV_WAIT_ESC_IN 3

    int st = GV_WAIT_VAL;
    char * value_start = state->ptr;

    *escaped   = 0;
    state->buf->len = 0;

    zend_bool use_buffer  = 0;
    zend_bool last_symbol = 1;

    while (1) {
        char ch = *(state->ptr);

        if (st == GV_WAIT_VAL) {
            if ('"' == ch) {
                *escaped = 1;
                ++value_start;
                st = GV_IN_ESC_VAL;
            } else if ('=' == ch && !ignoreeq) {
                state->error = ERROR_SYNTAX;
                return 0;
            } else if ('\\' == ch) {
                st = GV_WAIT_ESC_IN;
            } else if (!isspace((unsigned char) ch)) {
                st = GV_IN_VAL;
            } else {
                ++value_start;
            }
        } else if (st == GV_IN_VAL) {
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
                smart_str_appendc(state->buf, ch);
            }
        } else if (st == GV_IN_ESC_VAL) {
            if ('\\' == ch) {
                st = GV_WAIT_ESC_IN;
            } else if ('"' == ch) {
                last_symbol = 0;
                break;
            } else if (use_buffer) {
                smart_str_appendc(state->buf, ch);
            }
        } else if (st == GV_WAIT_ESC_IN) {
            if (!use_buffer) {
                use_buffer = 1;
                int nlen = state->ptr - value_start - 1;
                if (nlen > 0) {
                    smart_str_appendl(state->buf, value_start, nlen);
                }
            }

            smart_str_appendc(state->buf, ch);

            if (*escaped) {
                st = GV_IN_ESC_VAL;
            } else {
                st = GV_IN_VAL;
            }
        } else {
            state->error = ERROR_UNKNOWN_STATE;
            return 0;
        }

        ++state->ptr;

        if (state->ptr >= state->ptr_max) {
            if (st != GV_IN_VAL) {
                state->error = ERROR_UNEXPECTED_END;
                return 0;
            }

            last_symbol = 0;
            break;
        }
    }

    // return parsed value
    if (use_buffer) {
        state->word     = state->buf->c;
        state->word_len = state->buf->len;
    } else {
        state->word     = value_start;
        state->word_len = state->ptr - value_start + last_symbol;
    }

    return 1;
}

static inline void hstore_parse(HSParser *state) /* {{{ */
{
    #define WKEY  0
    #define WVAL  1
    #define WEQ   2
    #define WDEL  4

    #define isnull_keyword(s) ( \
        (('n' == s[0]) || ('N' == s[0])) && \
        (('u' == s[1]) || ('U' == s[1])) && \
        (('l' == s[2]) || ('L' == s[2])) && \
        (('l' == s[3]) || ('L' == s[3])) \
    )

    state->ptr     = state->begin;
    state->ptr_max = state->begin + state->begin_len;
    state->error   = 0;

    int st = WKEY;
    zend_bool escaped;

    while (state->ptr < state->ptr_max) {
        char ch = *(state->ptr);

        if (isspace((unsigned char) ch)) {
            state->ptr++;
            continue;
        }

        if (st == WKEY) {
            if (!hstore_parse_value(state, 0, &escaped)) {
                return;
            }

            state->key     = state->word;
            state->key_len = state->word_len;

            st = WEQ;
        } else if (st == WEQ) {
            if (state->ptr + 2 > state->ptr_max) {
                state->error = ERROR_UNEXPECTED_END;
                return;
            }

            if ('=' == *(state->ptr) && '>' == *(state->ptr + 1)) {
                st          = WVAL;
                state->ptr += 2;

                continue;
            }

            state->error = ERROR_SYNTAX;
            return;
        } else if (st == WVAL) {
            char *key = estrndup(state->key, state->key_len);

            if (!hstore_parse_value(state, 1, &escaped)) {
                efree(key);
                return;
            }

            if (!escaped && 4 == state->word_len && isnull_keyword(state->word)) {
                add_assoc_null_ex(state->z, key, state->key_len + 1);
            } else {
                add_assoc_stringl_ex(state->z, key, state->key_len + 1, state->word, state->word_len, 1);
            }

            efree(key);

            st = WDEL;
        } else if (st == WDEL) {
            if (',' == *(state->ptr)) {
                st = WKEY;
            } else {
                state->error = ERROR_SYNTAX;
                return;
            }
        } else {
            state->error = ERROR_UNKNOWN_STATE;
            return;
        }

        state->ptr++;
    }

    if (st != WKEY && st != WDEL) {
        state->error = ERROR_UNEXPECTED_END;
    }
}

/* {{{ proto mixed hstore_decode(string hstore)
   Decodes the HStore string into a PHP array */
static PHP_FUNCTION(hstore_decode)
{
    char *str;
    int str_len;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &str, &str_len) == FAILURE) {
        return;
    }

    smart_str buf = {0};

    HSParser state;
    state.begin = str;
    state.begin_len = str_len;
    state.buf = &buf;

    ALLOC_INIT_ZVAL(state.z);

    array_init(state.z);

    // for smart_str_alloc macro
    size_t newlen;
    smart_str_alloc(state.buf, 1024, 0);

    hstore_parse(&state);

    smart_str_free(&buf);

    if (0 == state.error) {
        *return_value = *state.z;
    } else if (ERROR_UNKNOWN_STATE == state.error) {
        zend_throw_exception(NULL, "Unknown state", 0 TSRMLS_CC);
    } else if (ERROR_UNEXPECTED_END == state.error) {
        zend_throw_exception(NULL, "Unexpected end of string", 0 TSRMLS_CC);
    } else if (ERROR_SYNTAX == state.error) {
        zend_throw_exception(NULL, "Syntax error", 0 TSRMLS_CC);
    } else {
        zend_throw_exception(NULL, "Internal error", 0 TSRMLS_CC);
    }
    FREE_ZVAL(state.z);

    //    elog(ERROR, "Syntax error near '%c' at position %d", *(state->ptr), (int32) (state->ptr - state->begin));
    // elog(ERROR, "Unknown state %d at line %d in file '%s'", st, __LINE__, __FILE__);
}
/* }}} */

static inline void escape_string(smart_str *buf, char *str, int str_len) /* {{{ */
{
    char *ptr = str;
    char *ptr_max = str + str_len;

    while (ptr < ptr_max) {
        char ch = *ptr;

        if ('"' == ch || '\\' == ch) {
            smart_str_appendc(buf, '\\');
        }
        smart_str_appendc(buf, ch);

        ptr++;
    }
}

/* {{{ proto mixed hstore_encode(array hstore)
   Decodes the hStore representation into a PHP value */
static PHP_FUNCTION(hstore_encode)
{
    zval *arr;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "a", &arr) == FAILURE) {
        return;
    }

    smart_str buf = {0};

    // for smart_str_alloc macro
    size_t newlen;
    smart_str_alloc(&buf, 1024, 0);

    HashTable *myht = HASH_OF(arr);
    HashPosition pos;

    if (zend_hash_num_elements(myht) > 0) {
        char *key;
        zval **data;
        ulong index;
        uint key_len;
        zend_bool need_comma = 0;

        zend_hash_internal_pointer_reset_ex(myht, &pos);
        for (;; zend_hash_move_forward_ex(myht, &pos)) {
            int i = zend_hash_get_current_key_ex(myht, &key, &key_len, &index, 0, &pos);

            if (i == HASH_KEY_NON_EXISTENT) {
                break;
            }

            if (zend_hash_get_current_data_ex(myht, (void **) &data, &pos) != SUCCESS) {
                break;
            }

            if (need_comma) {
                smart_str_appendc(&buf, ',');
                smart_str_appendc(&buf, ' ');
            } else {
                need_comma = 1;
            }

            smart_str_appendc(&buf, '"');

            if (i == HASH_KEY_IS_STRING) {
                escape_string(&buf, key, key_len - 1);
            } else {
                smart_str_append_long(&buf, (long) index);
            }

            smart_str_appendc(&buf, '"');
            smart_str_appendc(&buf, '=');
            smart_str_appendc(&buf, '>');

            if (Z_TYPE_PP(data) == IS_NULL) {
                smart_str_appendl(&buf, "NULL", 4);
            } else {
                convert_to_string_ex(data);

                smart_str_appendc(&buf, '"');
                escape_string(&buf, Z_STRVAL_PP(data), Z_STRLEN_PP(data));
                smart_str_appendc(&buf, '"');
            }
        }
    }

    ZVAL_STRINGL(return_value, buf.c, buf.len, 1);
    smart_str_free(&buf);
}
/* }}} */
