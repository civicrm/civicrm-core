<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * StateProvince
 *
 * @ORM\Table(name="civicrm_state_province", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name_country_id", columns={"name", "country_id"})}, indexes={@ORM\Index(name="FK_civicrm_state_province_country_id", columns={"country_id"})})
 * @ORM\Entity
 */
class StateProvince extends \Civi\Core\Entity
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
     * @ORM\Column(name="abbreviation", type="string", length=4, nullable=true)
     */
    private $abbreviation;

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
     * @return StateProvince
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
     * @return StateProvince
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
     * Set country
     *
     * @param \Civi\Core\Country $country
     * @return StateProvince
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
