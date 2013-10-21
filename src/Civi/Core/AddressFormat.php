<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * AddressFormat
 *
 * @ORM\Table(name="civicrm_address_format")
 * @ORM\Entity
 */
class AddressFormat extends \Civi\Core\Entity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="format", type="text", nullable=true)
     */
    private $format;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set format
     *
     * @param string $format
     * @return AddressFormat
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format
     *
     * @return string 
     */
    public function getFormat()
    {
        return $this->format;
    }
}
