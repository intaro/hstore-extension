<?php

namespace Cent\HStore\Doctrine\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Intaro\HStore\Coder;

/**
 * HStore data type
 */
class HStoreType extends Type
{
    const HSTORE = 'hstore';

    /**
     * @param array            $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return self::HSTORE;
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return array|null
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        try {
            return Coder::decode($value);
        } catch (\Exception $e) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return null|string
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return Coder::encode($value);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::HSTORE;
    }
}
