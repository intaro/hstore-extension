<?php

namespace Intaro\HStore\Tests;

use Intaro\HStore\Coder;

class CoderTest extends \PHPUnit_Framework_TestCase
{
    public function dataDecode()
    {
        return [
            ['', []],
            ['    ', []],
            ["\t \n \r", []],

            ['a=>b', ['a' => 'b']],
            [' a=>b', ['a' => 'b']],
            [' a =>b', ['a' => 'b']],
            [' a => b', ['a' => 'b']],
            [' a => b ', ['a' => 'b']],
            ['a => b ', ['a' => 'b']],
            ['a=> b ', ['a' => 'b']],
            ['a=>b ', ['a' => 'b']],

            ['"a"=>"b"', ['a' => 'b']],
            [' "a"=>"b"', ['a' => 'b']],
            [' "a" =>"b"', ['a' => 'b']],
            [' "a" => "b"', ['a' => 'b']],
            [' "a" => "b" ', ['a' => 'b']],
            ['"a" => "b" ', ['a' => 'b']],
            ['"a"=> "b" ', ['a' => 'b']],
            ['"a"=>"b" ', ['a' => 'b']],

            ['aa=>bb', ['aa' => 'bb']],
            [' aa=>bb', ['aa' => 'bb']],
            [' aa =>bb', ['aa' => 'bb']],
            [' aa => bb', ['aa' => 'bb']],
            [' aa => bb ', ['aa' => 'bb']],
            ['aa => bb ', ['aa' => 'bb']],
            ['aa=> bb ', ['aa' => 'bb']],
            ['aa=>bb ', ['aa' => 'bb']],

            ['"aa"=>"bb"', ['aa' => 'bb']],
            [' "aa"=>"bb"', ['aa' => 'bb']],
            [' "aa" =>"bb"', ['aa' => 'bb']],
            [' "aa" => "bb"', ['aa' => 'bb']],
            [' "aa" => "bb" ', ['aa' => 'bb']],
            ['"aa" => "bb" ', ['aa' => 'bb']],
            ['"aa"=> "bb" ', ['aa' => 'bb']],
            ['"aa"=>"bb" ', ['aa' => 'bb']],

            ['aa=>bb, cc=>dd', ['aa' => 'bb', 'cc' => 'dd']],
            ['aa=>bb , cc=>dd', ['aa' => 'bb', 'cc' => 'dd']],
            ['aa=>bb ,cc=>dd', ['aa' => 'bb', 'cc' => 'dd']],
            ['aa=>bb, "cc"=>dd', ['aa' => 'bb', 'cc' => 'dd']],
            ['aa=>bb , "cc"=>dd', ['aa' => 'bb', 'cc' => 'dd']],
            ['aa=>bb ,"cc"=>dd', ['aa' => 'bb', 'cc' => 'dd']],
            ['aa=>"bb", cc=>dd', ['aa' => 'bb', 'cc' => 'dd']],
            ['aa=>"bb" , cc=>dd', ['aa' => 'bb', 'cc' => 'dd']],
            ['aa=>"bb" ,cc=>dd', ['aa' => 'bb', 'cc' => 'dd']],

            ['aa=>null',   ['aa' => null]],
            ['aa=>NuLl',   ['aa' => null]],
            ['aa=>"NuLl"', ['aa' => "NuLl"]],
            ['aa=>nulla',  ['aa' => "nulla"]],

            ['a=>5',   ['a' => '5']],
            ['a=>5.5', ['a' => '5.5']],
            ['5=>1',   [5 => "1"]],

            ['"a"=>"==>,\\""', ['a' => '==>,"']],

            ['a=>b,',   ['a' => 'b']],
            ['a=>b ,',  ['a' => 'b']],
            ['a=>b, ',  ['a' => 'b']],
            ['a=>b , ', ['a' => 'b']],

            ['a=>""', ['a' => '']],
            ['""=>"\\""', ['' => '"']],
            ['\"a=>q"w',   ['"a' => 'q"w']],

            // TODO
            /*
            ['>,=>q=w,',   ['>,' => 'q=w']],
            ['>, =>q=w,',   ['>,' => 'q=w']],
            ['>, =>q=w ,',   ['>,' => 'q=w']],
            ['>, =>q=w , ',   ['>,' => 'q=w']],
            ['>,=>q=w , ',   ['>,' => 'q=w']],
            ['>,=>q=w, ',   ['>,' => 'q=w']],

            ['\=a=>q=w',   ['=a' => 'q=w']],
            ['"=a"=>q\=w', ['=a' => 'q=w']],
            ['"\"a"=>q>w', ['"a' => 'q>w']],
            */
        ];
    }

    /**
     * @dataProvider dataDecode
     *
     * @param string $data
     * @param array  $expected
     */
    public function testDecode($data, array $expected)
    {
        $this->assertSame($expected, Coder::decode($data));
    }

    public function dataEncode()
    {
        return [
            [[], ''],
            [['a' => ''], '"a"=>""'],
            [['' => 'a'], '""=>"a"'],
            [['' => '"'], '""=>"\\""'],

            [['a' => 'b"'], '"a"=>"b\\""'],

            [['a' => 'b', 'c' => 'd'], '"a"=>"b", "c"=>"d"'],

            [['a' => null], '"a"=>NULL'],
            [['a"' => '"b\\'], '"a\\""=>"\\"b\\\\"'],

            [['a' => 5], '"a"=>"5"'],
            [['a' => 5.5], '"a"=>"5.5"'],
            [['a', "b"], '"0"=>"a", "1"=>"b"'],
        ];
    }

    /**
     * @dataProvider dataEncode
     *
     * @param array  $data
     * @param string $expected
     */
    public function testEncode(array $data, $expected)
    {
        $this->assertSame($expected, Coder::encode($data));
    }

    public function testExtension()
    {
        if (!extension_loaded('hstore')) {
            return;
        }

        $r = new \ReflectionExtension('hstore');

        $this->assertContains('Intaro\\HStore\\Coder', $r->getClassNames());
    }

    public function testMemoryUsage()
    {
        $var = [
            'a' => 'b',
            'c' => null,
            'd' => 'e"e',
            'f' => str_repeat('0', 1024),
        ];

        // allocate zval's
        $before = 0;
        $after  = 0;
        $real_before = 0;
        $real_after  = 0;

        $before = memory_get_usage();
        $real_before = memory_get_usage(true);

        for ($i = 0; $i < 10000; $i++) {
            Coder::decode(Coder::encode($var));
        }

        unset($i);
        gc_collect_cycles();

        $after = memory_get_usage();
        $real_after = memory_get_usage(true);

        if ($after > $before) {
            $this->fail(sprintf("Memory is corrupted (%d bytes not cleared)", $after - $before));
        }

        if ($real_after > $real_before) {
            $this->fail(sprintf("Real memory is corrupted (%d bytes not cleared)", $real_after - $real_before));
        }
    }
}
