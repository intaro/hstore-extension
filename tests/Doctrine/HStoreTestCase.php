<?php

namespace Cent\HStore\Tests\Doctrine;

/**
 * Class HStoreTestCase
 */
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
        $config->setProxyNamespace('Cent\HStore\Tests\Proxies');
        $config->setAutoGenerateProxyClasses(true);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(__DIR__ . '/Entities'));
        $config->addEntityNamespace('E', 'Cent\HStore\Tests\Doctrine\Entities');

        $config->setCustomStringFunctions(array(
            'contains'         => 'Cent\HStore\Doctrine\Query\ContainsFunction',
            'defined'          => 'Cent\HStore\Doctrine\Query\DefinedFunction',
            'existsAny'        => 'Cent\HStore\Doctrine\Query\ExistsAnyFunction',
            'fetchval'         => 'Cent\HStore\Doctrine\Query\FetchvalFunction',
            'hstoreDifference' => 'Cent\HStore\Doctrine\Query\HstoreDifferenceFunction',
        ));

        $this->entityManager = \Doctrine\ORM\EntityManager::create(
            array('driver' => 'pdo_sqlite', 'memory' => true),
            $config
        );
    }

}


