<?php

namespace Intaro\HStore\Tests\Doctrine;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;

class HStoreTestCase extends TestCase
{
    public $entityManager = null;

    protected function setUp(): void
    {
        if (!class_exists('\Doctrine\ORM\Configuration')) {
            $this->markTestSkipped('Doctrine is not available');
        }

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCache(new ArrayCachePool());
        $config->setQueryCache(new ArrayCachePool());
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Intaro\HStore\Tests\Proxies');
        $config->setAutoGenerateProxyClasses(true);
        $config->setMetadataDriverImpl(ORMSetup::createDefaultAnnotationDriver([__DIR__ . '/Entities']));
        $config->addEntityNamespace('E', 'Intaro\HStore\Tests\Doctrine\Entities');

        $config->setCustomStringFunctions(array(
            'akeys'            => 'Intaro\HStore\Doctrine\Query\AKeysFunction',
            'avals'            => 'Intaro\HStore\Doctrine\Query\AValsFunction',
            'contains'         => 'Intaro\HStore\Doctrine\Query\ContainsFunction',
            'defined'          => 'Intaro\HStore\Doctrine\Query\DefinedFunction',
            'existsAny'        => 'Intaro\HStore\Doctrine\Query\ExistsAnyFunction',
            'fetchval'         => 'Intaro\HStore\Doctrine\Query\FetchvalFunction',
            'hstoreDifference' => 'Intaro\HStore\Doctrine\Query\HstoreDifferenceFunction',
        ));

        $this->entityManager = \Doctrine\ORM\EntityManager::create(
            array('driver' => 'pdo_sqlite', 'memory' => true),
            $config
        );
    }

}


