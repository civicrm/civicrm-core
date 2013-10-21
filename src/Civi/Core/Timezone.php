<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Timezone
 *
 * @ORM\Table(name="civicrm_timezone", indexes={@ORM\Index(name="FK_civicrm_timezone_country_id", columns={"country_id"})})
 * @ORM\Entity
 */
class Timezone extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="abbreviation", type="string", length=3, nullable=true)
     */
    private $abbreviation;

    /**
     * @var string
     *
     * @ORM\Column(name="gmt", type="string", length=64, nullable=true)
     */
    private $gmt;

    /**
     * @var integer
     *
     * @ORM\Column(name="offset", type="integer", nullable=true)
     */
    private $offset;

    /**
     * @var \Civi\Core\Country
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;



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
     * Set name
     *
     * @param string $name
     * @return Timezone
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set abbreviation
     *
     * @param string $abbreviation
     * @return Timezone
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    /**
     * Get abbreviation
     *
     * @return string 
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * Set gmt
     *
     * @param string $gmt
     * @return Timezone
     */
    public function setGmt($gmt)
    {
        $this->gmt = $gmt;

        return $this;
    }

    /**
     * Get gmt
     *
     * @return string 
     */
    public function getGmt()
    {
        return $this->gmt;
    }

    /**
     * Set offset
     *
     * @param integer $offset
     * @return Timezone
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get offset
     *
     * @return integer 
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Set country
     *
     * @param \Civi\Core\Country $country
     * @return Timezone
     */
    public function setCountry(\Civi\Core\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Civi\Core\Country 
     */
    public function getCountry()
    {
        return $this->country;
    }
}
