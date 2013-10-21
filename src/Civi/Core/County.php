<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * County
 *
 * @ORM\Table(name="civicrm_county", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name_state_id", columns={"name", "state_province_id"})}, indexes={@ORM\Index(name="FK_civicrm_county_state_province_id", columns={"state_province_id"})})
 * @ORM\Entity
 */
class County extends \Civi\Core\Entity
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
     * @var \Civi\Core\StateProvince
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\StateProvince")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_province_id", referencedColumnName="id")
     * })
     */
    private $stateProvince;



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
     * @return County
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
     * @return County
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
     * Set stateProvince
     *
     * @param \Civi\Core\StateProvince $stateProvince
     * @return County
     */
    public function setStateProvince(\Civi\Core\StateProvince $stateProvince = null)
    {
        $this->stateProvince = $stateProvince;

        return $this;
    }

    /**
     * Get stateProvince
     *
     * @return \Civi\Core\StateProvince 
     */
    public function getStateProvince()
    {
        return $this->stateProvince;
    }
}
