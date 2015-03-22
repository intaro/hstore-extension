<?php

namespace Intaro\HStore\Tests\Doctrine;

class HStoreTestCase extends \PHPUnit_Framework_TestCase
{
    public $entityManager = null;

    protected function setUp()
    {
        if (!class_exists('\Doctrine\ORM\Configuration')) {
            $this->markTestSkipped('Doctrine is not available');
        }

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Intaro\HStore\Tests\Proxies');
        $config->setAutoGenerateProxyClasses(true);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(__DIR__ . '/Entities'));
        $config->addEntityNamespace('E', 'Intaro\HStore\Tests\Doctrine\Entities');

        $config->setCustomStringFunctions(array(
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


