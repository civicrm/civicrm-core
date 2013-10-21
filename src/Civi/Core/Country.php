<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country
 *
 * @ORM\Table(name="civicrm_country", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name_iso_code", columns={"name", "iso_code"})}, indexes={@ORM\Index(name="FK_civicrm_country_address_format_id", columns={"address_format_id"}), @ORM\Index(name="FK_civicrm_country_region_id", columns={"region_id"})})
 * @ORM\Entity
 */
class Country extends \Civi\Core\Entity
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
     * @ORM\Column(name="iso_code", type="string", length=2, nullable=true)
     */
    private $isoCode;

    /**
     * @var string
     *
     * @ORM\Column(name="country_code", type="string", length=4, nullable=true)
     */
    private $countryCode;

    /**
     * @var string
     *
     * @ORM\Column(name="idd_prefix", type="string", length=4, nullable=true)
     */
    private $iddPrefix;

    /**
     * @var string
     *
     * @ORM\Column(name="ndd_prefix", type="string", length=4, nullable=true)
     */
    private $nddPrefix;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_province_abbreviated", type="boolean", nullable=true)
     */
    private $isProvinceAbbreviated = '0';

    /**
     * @var \Civi\Core\AddressFormat
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\AddressFormat")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="address_format_id", referencedColumnName="id")
     * })
     */
    private $addressFormat;

    /**
     * @var \Civi\Core\Worldregion
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Worldregion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="region_id", referencedColumnName="id")
     * })
     */
    private $region;



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
     * @return Country
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
     * Set isoCode
     *
     * @param string $isoCode
     * @return Country
     */
    public function setIsoCode($isoCode)
    {
        $this->isoCode = $isoCode;

        return $this;
    }

    /**
     * Get isoCode
     *
     * @return string 
     */
    public function getIsoCode()
    {
        return $this->isoCode;
    }

    /**
     * Set countryCode
     *
     * @param string $countryCode
     * @return Country
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Get countryCode
     *
     * @return string 
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Set iddPrefix
     *
     * @param string $iddPrefix
     * @return Country
     */
    public function setIddPrefix($iddPrefix)
    {
        $this->iddPrefix = $iddPrefix;

        return $this;
    }

    /**
     * Get iddPrefix
     *
     * @return string 
     */
    public function getIddPrefix()
    {
        return $this->iddPrefix;
    }

    /**
     * Set nddPrefix
     *
     * @param string $nddPrefix
     * @return Country
     */
    public function setNddPrefix($nddPrefix)
    {
        $this->nddPrefix = $nddPrefix;

        return $this;
    }

    /**
     * Get nddPrefix
     *
     * @return string 
     */
    public function getNddPrefix()
    {
        return $this->nddPrefix;
    }

    /**
     * Set isProvinceAbbreviated
     *
     * @param boolean $isProvinceAbbreviated
     * @return Country
     */
    public function setIsProvinceAbbreviated($isProvinceAbbreviated)
    {
        $this->isProvinceAbbreviated = $isProvinceAbbreviated;

        return $this;
    }

    /**
     * Get isProvinceAbbreviated
     *
     * @return boolean 
     */
    public function getIsProvinceAbbreviated()
    {
        return $this->isProvinceAbbreviated;
    }

    /**
     * Set addressFormat
     *
     * @param \Civi\Core\AddressFormat $addressFormat
     * @return Country
     */
    public function setAddressFormat(\Civi\Core\AddressFormat $addressFormat = null)
    {
        $this->addressFormat = $addressFormat;

        return $this;
    }

    /**
     * Get addressFormat
     *
     * @return \Civi\Core\AddressFormat 
     */
    public function getAddressFormat()
    {
        return $this->addressFormat;
    }

    /**
     * Set region
     *
     * @param \Civi\Core\Worldregion $region
     * @return Country
     */
    public function setRegion(\Civi\Core\Worldregion $region = null)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return \Civi\Core\Worldregion 
     */
    public function getRegion()
    {
        return $this->region;
    }
}
