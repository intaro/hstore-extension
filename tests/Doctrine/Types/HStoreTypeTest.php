<?php

namespace Cent\HStore\Tests\Doctrine\Types;

/**
 * @runInSeparateProcess true
 * @runTestsInSeparateProcesses true
 */
class HStoreTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testPHP2DB()
    {
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $this->assertNull($type->convertToPHPValue(null, $platform));
        $this->assertSame(['a' => 'b'], $type->convertToPHPValue('"a"=>"b"', $platform));
    }

    /**
     * @expectedException \Exception
     */
    public function testPHP2DBWrong()
    {
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $type->convertToPHPValue("123", $platform);
    }

    public function testDB2PHP()
    {
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $this->assertNull($type->convertToDatabaseValue(null, $platform));
        $this->assertSame('"a"=>"b"', $type->convertToDatabaseValue(['a' => 'b'], $platform));
    }

    /**
     * @expectedException \Exception
     */
    public function testDB2PHPWrong()
    {
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $type->convertToDatabaseValue("123", $platform);
    }

    public function testDeclaration()
    {
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $this->assertSame("hstore", $type->getName());
        $this->assertSame("hstore", $type->getSQLDeclaration([], $platform));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected function getPlatform()
    {
        return $this->getMockBuilder('Doctrine\DBAL\Platforms\PostgreSqlPlatform')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function setUp()
    {
        if (!class_exists('\Doctrine\ORM\Configuration')) {
            $this->markTestSkipped('Doctrine is not available');
        }

        \Doctrine\DBAL\Types\Type::addType('hstore', 'Intaro\HStore\Doctrine\Types\HStoreType');
    }
}
