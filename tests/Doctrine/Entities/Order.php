<?php

namespace Intaro\HStore\Tests\Doctrine\Entities;

/**
 * @Entity
 */
class Order
{
    /** @Id @Column(type="string") @GeneratedValue */
    public $id;

    /** @Column(type="hstore") */
    public $attrs;
}
