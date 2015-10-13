<?php

require_once __DIR__ . '/../vendor/autoload.php';

Bench::addArray(
    "Huge amount of data",
    [
        str_repeat('long_key', 100) => str_repeat('long_value', 200),
        'a' => null,
    ]
);

Bench::addArray(
    "Huge amount of data need to be escaped",
    [
        str_repeat('long"\\_key', 100) => str_repeat('long_"\\value', 200),
        'a' => null,
    ]
);

Bench::addArray(
    "100 keys",
    array_fill_keys(
        array_map(function ($m) { return "aaa_" . $m;}, range(0, 99)),
        "123"
    )
);

Bench::addHStore(
    "Real life example",
    '"pol"=>"15", "a_aid"=>NULL, "a_bid"=>NULL, "kurier"=>NULL, "shtrih"=>NULL, "country"=>NULL, "why_not"=>NULL, "form_type"=>NULL, "birth_date"=>"", "money_date"=>"", "order_date"=>"2014-07-25", "utm_medium"=>NULL, "utm_source"=>NULL, "utm_content"=>NULL, "any_currency"=>NULL, "dop_telefone"=>"83833464526", "pap_order_id"=>NULL, "utm_campaign"=>NULL, "transaction_id"=>NULL, "date_of_sending"=>"", "date_of_delivery"=>"", "kliet_teperature"=>NULL, "nomer_otpravleniya"=>NULL, "form_type"=>"Заявка на консультацию специалиста"'
);

//----------------------------------------------------------------------------------------------------------------------

Bench::calibrate();
Bench::go();

//----------------------------------------------------------------------------------------------------------------------

use \Cent\HStore\Coder;

class Bench
{
    private static $iterations = 1000;
    private static $net_time = 0;

    private static $tests = [];

    public static function calibrate()
    {
        $t = microtime(true);
        for ($i = self::$iterations; $i >= 0; --$i) {
            call_user_func_array('\Bench::noop', []);
        }
        $t = microtime(true) - $t;

        self::$net_time = $t;
    }

    public static function addArray($label, $value)
    {
        self::$tests[$label] = [
            $value,
            Coder::encode($value),
        ];
    }

    public static function addHStore($label, $value)
    {
        self::$tests[$label] = [
            Coder::decode($value),
            $value,
        ];
    }

    public static function go()
    {
        foreach (self::$tests as $label => $data) {
            list($raw, $encoded) = $data;
            $json = json_encode($raw);

            echo "=== {$label} (encode)\n";
            Bench::doBench("json", 'json_encode', [$raw]);
            Bench::doBench("hstore", '\\Cent\\HStore\\Coder::encode', [$raw]);
            echo "\n";

            echo "=== {$label} (decode)\n";
            Bench::doBench("json", 'json_decode', [$json, true]);
            Bench::doBench("hstore", '\\Cent\\HStore\\Coder::decode', [$encoded]);
            echo "\n";
        }
    }

    public static function noop() {}

    private static function doBench($label, callable $func, array $args) {
        $t = microtime(true);
        for ($i = self::$iterations; $i >= 0; --$i) {
            call_user_func_array($func, $args);
        }
        $t = microtime(true) - $t;

        echo sprintf(
            "%9.3f ms - %s\n",
            1000 * ($t - self::$net_time),
            $label
        );
    }
}

