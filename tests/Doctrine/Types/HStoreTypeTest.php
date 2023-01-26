<?php

namespace Intaro\HStore\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @runInSeparateProcess true
 * @runTestsInSeparateProcesses true
 */
class HStoreTypeTest extends TestCase
{
    public function testPHP2DB(): void
    {
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $this->assertNull($type->convertToPHPValue(null, $platform));
        $this->assertSame(['a' => 'b'], $type->convertToPHPValue('"a"=>"b"', $platform));
    }

    public function testPHP2DBWrong(): void
    {
        $this->expectException(\Exception::class);
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $type->convertToPHPValue("123", $platform);
    }

    public function testDB2PHP(): void
    {
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $this->assertNull($type->convertToDatabaseValue(null, $platform));
        $this->assertSame('"a"=>"b"', $type->convertToDatabaseValue(['a' => 'b'], $platform));
    }
    
    public function testDB2PHPWrong(): void
    {
        $this->expectException(\Exception::class);

        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $type->convertToDatabaseValue("123", $platform);
    }

    public function testDeclaration(): void
    {
        $platform = $this->getPlatform();
        $type = \Doctrine\DBAL\Types\Type::getType('hstore');

        $this->assertSame("hstore", $type->getName());
        $this->assertSame("hstore", $type->getSQLDeclaration([], $platform));
    }

    /**
     * @return MockObject|\Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected function getPlatform()
    {
        return $this->getMockBuilder(PostgreSQLPlatform::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function setUp(): void
    {
        if (!class_exists('\Doctrine\ORM\Configuration')) {
            $this->markTestSkipped('Doctrine is not available');
        }

        \Doctrine\DBAL\Types\Type::addType('hstore', 'Intaro\HStore\Doctrine\Types\HStoreType');
    }
}
