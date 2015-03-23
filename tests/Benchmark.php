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

Bench::go();

//----------------------------------------------------------------------------------------------------------------------

use \Intaro\HStore\Coder;

class Bench
{
    private static $iterations = 50;
    private static $repeats = 50;

    private static $tests   = [];

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
        $results = [];

        for ($i = self::$repeats; $i >= 0; --$i) {
            usleep(0);

            foreach (self::$tests as $label => $data) {
                list($raw, $encoded) = $data;
                $json = json_encode($raw);

                $results[$label]['encode']['json'][]   = Bench::doBench('json_encode', [$raw]);
                $results[$label]['encode']['hstore'][] = Bench::doBench('\\Intaro\\HStore\\Coder::encode', [$raw]);

                $results[$label]['decode']['json'][]   = Bench::doBench('json_decode', [$json, true]);
                $results[$label]['decode']['hstore'][] = Bench::doBench('\\Intaro\\HStore\\Coder::decode', [$encoded]);
            }
        }

        // print results
        foreach ($results as $label => $ldata) {
            foreach ($ldata as $group => $gdata) {
                echo "=== {$label} ({$group})\n";

                $functions_results = [];

                foreach ($gdata as $function => $r) {
                    $min = min($r);
                    $max = max($r);
                    $avg = array_sum($r) / count($r);

                    $functions_results[$function] = $min;

                    echo sprintf(
                        "%8.3fμs min (%8.3fμs avg, %8.3fμs max) - %s\n",
                        $min, $avg, $max,
                        $function
                    );
                }
                $json   = $functions_results['json'];
                $hstore = $functions_results['hstore'];

                echo "+++ ";
                if ($json > $hstore) {
                    echo sprintf("hstore %.2f%% faster that json", 100 * $json / $hstore);
                } else {
                    echo sprintf("json %.2f%% faster that hstore", 100 * $hstore / $json);
                }

                echo "\n";
            }
        }
    }

    private static function doBench(callable $func, array $args)
    {
        gc_collect_cycles();
        gc_disable();
        usleep(0);

        $t = microtime(true);
        for ($i = self::$iterations; $i >= 0; --$i) {
            call_user_func_array($func, $args);
        }
        $t = microtime(true) - $t;

        gc_enable();
        gc_collect_cycles();
        usleep(0);

        return round($t * 1000000 / self::$iterations, 3);
    }
}

