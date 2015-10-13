<?php

namespace Cent\HStore\Tests\Doctrine\Query;

use Cent\HStore\Tests\Doctrine\HStoreTestCase;

/**
 * Class FunctionsTest
 */
class FunctionsTest extends HStoreTestCase
{
    public function testContains()
    {
        $q = $this
            ->entityManager
            ->createQuery("SELECT contains(o.attrs, 'a') from E:Order o");

        $this->assertEquals(
            "SELECT contains(o0_.attrs, 'a') AS sclr0 FROM Order o0_",
            $q->getSql()
        );
    }

    public function testDefined()
    {
        $q = $this
            ->entityManager
            ->createQuery("SELECT defined(o.attrs, 'a') from E:Order o");

        $this->assertEquals(
            "SELECT defined(o0_.attrs, 'a') AS sclr0 FROM Order o0_",
            $q->getSql()
        );
    }

    public function testExistsAny()
    {
        $q = $this
            ->entityManager
            ->createQuery("SELECT existsAny(o.attrs, 'a') from E:Order o");

        $this->assertEquals(
            "SELECT exists_any(o0_.attrs, ARRAY['a']) AS sclr0 FROM Order o0_",
            $q->getSql()
        );
    }

    public function testFetchVal()
    {
        $q = $this
            ->entityManager
            ->createQuery("SELECT fetchval(o.attrs, 'a') from E:Order o");

        $this->assertEquals(
            "SELECT fetchval(o0_.attrs, 'a') AS sclr0 FROM Order o0_",
            $q->getSql()
        );
    }

    public function testHStoreDifference()
    {
        $q = $this
            ->entityManager
            ->createQuery("SELECT hstoreDifference(o.attrs, 'a') from E:Order o");

        $this->assertEquals(
            "SELECT o0_.attrs - ARRAY['a'] AS sclr0 FROM Order o0_",
            $q->getSql()
        );
    }
}
