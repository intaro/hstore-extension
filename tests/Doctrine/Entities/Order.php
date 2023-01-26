<?php

namespace Intaro\HStore\Tests\Doctrine\Entities;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 */
class Order
{
    /** @ORM\Id @ORM\Column(type="string") @ORM\GeneratedValue */
    public $id;

    /** @ORM\Column(type="hstore") */
    public $attrs;
}
