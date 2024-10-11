<?php

namespace Intaro\HStore\Doctrine\Types;

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

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return self::HSTORE;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if (\function_exists('hstore_decode')) {
            $return = \hstore_decode($value);
            if (false === $return) {
                throw ConversionException::conversionFailed($value, $this->getName());
            }

            return $return;
        } else {
            try {
                return Coder::decode($value);
            } catch (\Exception $e) {
                throw ConversionException::conversionFailed($value, $this->getName());
            }
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        if (\function_exists('hstore_encode')) {
            $return = \hstore_encode($value);
            if (false === $return) {
                throw ConversionException::conversionFailed($value, $this->getName());
            }

            return $return;
        }

        return Coder::encode($value);
    }

    public function getName(): string
    {
        return self::HSTORE;
    }
}
