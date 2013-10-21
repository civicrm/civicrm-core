<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Address
 *
 * @ORM\Table(name="civicrm_address", indexes={@ORM\Index(name="index_location_type", columns={"location_type_id"}), @ORM\Index(name="index_is_primary", columns={"is_primary"}), @ORM\Index(name="index_is_billing", columns={"is_billing"}), @ORM\Index(name="index_street_name", columns={"street_name"}), @ORM\Index(name="index_city", columns={"city"}), @ORM\Index(name="FK_civicrm_address_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_address_county_id", columns={"county_id"}), @ORM\Index(name="FK_civicrm_address_state_province_id", columns={"state_province_id"}), @ORM\Index(name="FK_civicrm_address_country_id", columns={"country_id"}), @ORM\Index(name="FK_civicrm_address_master_id", columns={"master_id"})})
 * @ORM\Entity
 */
class Address extends \Civi\Core\Entity
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
     * @var integer
     *
     * @ORM\Column(name="location_type_id", type="integer", nullable=true)
     */
    private $locationTypeId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_primary", type="boolean", nullable=true)
     */
    private $isPrimary = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_billing", type="boolean", nullable=true)
     */
    private $isBilling = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="street_address", type="string", length=96, nullable=true)
     */
    private $streetAddress;

    /**
     * @var integer
     *
     * @ORM\Column(name="street_number", type="integer", nullable=true)
     */
    private $streetNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="street_number_suffix", type="string", length=8, nullable=true)
     */
    private $streetNumberSuffix;

    /**
     * @var string
     *
     * @ORM\Column(name="street_number_predirectional", type="string", length=8, nullable=true)
     */
    private $streetNumberPredirectional;

    /**
     * @var string
     *
     * @ORM\Column(name="street_name", type="string", length=64, nullable=true)
     */
    private $streetName;

    /**
     * @var string
     *
     * @ORM\Column(name="street_type", type="string", length=8, nullable=true)
     */
    private $streetType;

    /**
     * @var string
     *
     * @ORM\Column(name="street_number_postdirectional", type="string", length=8, nullable=true)
     */
    private $streetNumberPostdirectional;

    /**
     * @var string
     *
     * @ORM\Column(name="street_unit", type="string", length=16, nullable=true)
     */
    private $streetUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="supplemental_address_1", type="string", length=96, nullable=true)
     */
    private $supplementalAddress1;

    /**
     * @var string
     *
     * @ORM\Column(name="supplemental_address_2", type="string", length=96, nullable=true)
     */
    private $supplementalAddress2;

    /**
     * @var string
     *
     * @ORM\Column(name="supplemental_address_3", type="string", length=96, nullable=true)
     */
    private $supplementalAddress3;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=64, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_code_suffix", type="string", length=12, nullable=true)
     */
    private $postalCodeSuffix;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_code", type="string", length=12, nullable=true)
     */
    private $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="usps_adc", type="string", length=32, nullable=true)
     */
    private $uspsAdc;

    /**
     * @var float
     *
     * @ORM\Column(name="geo_code_1", type="float", precision=10, scale=0, nullable=true)
     */
    private $geoCode1;

    /**
     * @var float
     *
     * @ORM\Column(name="geo_code_2", type="float", precision=10, scale=0, nullable=true)
     */
    private $geoCode2;

    /**
     * @var boolean
     *
     * @ORM\Column(name="manual_geo_code", type="boolean", nullable=true)
     */
    private $manualGeoCode = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=8, nullable=true)
     */
    private $timezone;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * })
     */
    private $contact;

    /**
     * @var \Civi\Core\County
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\County")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="county_id", referencedColumnName="id")
     * })
     */
    private $county;

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
     * @var \Civi\Core\Country
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;

    /**
     * @var \Civi\Core\Address
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Address")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="master_id", referencedColumnName="id")
     * })
     */
    private $master;



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
     * Set locationTypeId
     *
     * @param integer $locationTypeId
     * @return Address
     */
    public function setLocationTypeId($locationTypeId)
    {
        $this->locationTypeId = $locationTypeId;

        return $this;
    }

    /**
     * Get locationTypeId
     *
     * @return integer 
     */
    public function getLocationTypeId()
    {
        return $this->locationTypeId;
    }

    /**
     * Set isPrimary
     *
     * @param boolean $isPrimary
     * @return Address
     */
    public function setIsPrimary($isPrimary)
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    /**
     * Get isPrimary
     *
     * @return boolean 
     */
    public function getIsPrimary()
    {
        return $this->isPrimary;
    }

    /**
     * Set isBilling
     *
     * @param boolean $isBilling
     * @return Address
     */
    public function setIsBilling($isBilling)
    {
        $this->isBilling = $isBilling;

        return $this;
    }

    /**
     * Get isBilling
     *
     * @return boolean 
     */
    public function getIsBilling()
    {
        return $this->isBilling;
    }

    /**
     * Set streetAddress
     *
     * @param string $streetAddress
     * @return Address
     */
    public function setStreetAddress($streetAddress)
    {
        $this->streetAddress = $streetAddress;

        return $this;
    }

    /**
     * Get streetAddress
     *
     * @return string 
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * Set streetNumber
     *
     * @param integer $streetNumber
     * @return Address
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * Get streetNumber
     *
     * @return integer 
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * Set streetNumberSuffix
     *
     * @param string $streetNumberSuffix
     * @return Address
     */
    public function setStreetNumberSuffix($streetNumberSuffix)
    {
        $this->streetNumberSuffix = $streetNumberSuffix;

        return $this;
    }

    /**
     * Get streetNumberSuffix
     *
     * @return string 
     */
    public function getStreetNumberSuffix()
    {
        return $this->streetNumberSuffix;
    }

    /**
     * Set streetNumberPredirectional
     *
     * @param string $streetNumberPredirectional
     * @return Address
     */
    public function setStreetNumberPredirectional($streetNumberPredirectional)
    {
        $this->streetNumberPredirectional = $streetNumberPredirectional;

        return $this;
    }

    /**
     * Get streetNumberPredirectional
     *
     * @return string 
     */
    public function getStreetNumberPredirectional()
    {
        return $this->streetNumberPredirectional;
    }

    /**
     * Set streetName
     *
     * @param string $streetName
     * @return Address
     */
    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * Get streetName
     *
     * @return string 
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * Set streetType
     *
     * @param string $streetType
     * @return Address
     */
    public function setStreetType($streetType)
    {
        $this->streetType = $streetType;

        return $this;
    }

    /**
     * Get streetType
     *
     * @return string 
     */
    public function getStreetType()
    {
        return $this->streetType;
    }

    /**
     * Set streetNumberPostdirectional
     *
     * @param string $streetNumberPostdirectional
     * @return Address
     */
    public function setStreetNumberPostdirectional($streetNumberPostdirectional)
    {
        $this->streetNumberPostdirectional = $streetNumberPostdirectional;

        return $this;
    }

    /**
     * Get streetNumberPostdirectional
     *
     * @return string 
     */
    public function getStreetNumberPostdirectional()
    {
        return $this->streetNumberPostdirectional;
    }

    /**
     * Set streetUnit
     *
     * @param string $streetUnit
     * @return Address
     */
    public function setStreetUnit($streetUnit)
    {
        $this->streetUnit = $streetUnit;

        return $this;
    }

    /**
     * Get streetUnit
     *
     * @return string 
     */
    public function getStreetUnit()
    {
        return $this->streetUnit;
    }

    /**
     * Set supplementalAddress1
     *
     * @param string $supplementalAddress1
     * @return Address
     */
    public function setSupplementalAddress1($supplementalAddress1)
    {
        $this->supplementalAddress1 = $supplementalAddress1;

        return $this;
    }

    /**
     * Get supplementalAddress1
     *
     * @return string 
     */
    public function getSupplementalAddress1()
    {
        return $this->supplementalAddress1;
    }

    /**
     * Set supplementalAddress2
     *
     * @param string $supplementalAddress2
     * @return Address
     */
    public function setSupplementalAddress2($supplementalAddress2)
    {
        $this->supplementalAddress2 = $supplementalAddress2;

        return $this;
    }

    /**
     * Get supplementalAddress2
     *
     * @return string 
     */
    public function getSupplementalAddress2()
    {
        return $this->supplementalAddress2;
    }

    /**
     * Set supplementalAddress3
     *
     * @param string $supplementalAddress3
     * @return Address
     */
    public function setSupplementalAddress3($supplementalAddress3)
    {
        $this->supplementalAddress3 = $supplementalAddress3;

        return $this;
    }

    /**
     * Get supplementalAddress3
     *
     * @return string 
     */
    public function getSupplementalAddress3()
    {
        return $this->supplementalAddress3;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Address
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set postalCodeSuffix
     *
     * @param string $postalCodeSuffix
     * @return Address
     */
    public function setPostalCodeSuffix($postalCodeSuffix)
    {
        $this->postalCodeSuffix = $postalCodeSuffix;

        return $this;
    }

    /**
     * Get postalCodeSuffix
     *
     * @return string 
     */
    public function getPostalCodeSuffix()
    {
        return $this->postalCodeSuffix;
    }

    /**
     * Set postalCode
     *
     * @param string $postalCode
     * @return Address
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get postalCode
     *
     * @return string 
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set uspsAdc
     *
     * @param string $uspsAdc
     * @return Address
     */
    public function setUspsAdc($uspsAdc)
    {
        $this->uspsAdc = $uspsAdc;

        return $this;
    }

    /**
     * Get uspsAdc
     *
     * @return string 
     */
    public function getUspsAdc()
    {
        return $this->uspsAdc;
    }

    /**
     * Set geoCode1
     *
     * @param float $geoCode1
     * @return Address
     */
    public function setGeoCode1($geoCode1)
    {
        $this->geoCode1 = $geoCode1;

        return $this;
    }

    /**
     * Get geoCode1
     *
     * @return float 
     */
    public function getGeoCode1()
    {
        return $this->geoCode1;
    }

    /**
     * Set geoCode2
     *
     * @param float $geoCode2
     * @return Address
     */
    public function setGeoCode2($geoCode2)
    {
        $this->geoCode2 = $geoCode2;

        return $this;
    }

    /**
     * Get geoCode2
     *
     * @return float 
     */
    public function getGeoCode2()
    {
        return $this->geoCode2;
    }

    /**
     * Set manualGeoCode
     *
     * @param boolean $manualGeoCode
     * @return Address
     */
    public function setManualGeoCode($manualGeoCode)
    {
        $this->manualGeoCode = $manualGeoCode;

        return $this;
    }

    /**
     * Get manualGeoCode
     *
     * @return boolean 
     */
    public function getManualGeoCode()
    {
        return $this->manualGeoCode;
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     * @return Address
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string 
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Address
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Address
     */
    public function setContact(\Civi\Contact\Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set county
     *
     * @param \Civi\Core\County $county
     * @return Address
     */
    public function setCounty(\Civi\Core\County $county = null)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * Get county
     *
     * @return \Civi\Core\County 
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Set stateProvince
     *
     * @param \Civi\Core\StateProvince $stateProvince
     * @return Address
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

    /**
     * Set country
     *
     * @param \Civi\Core\Country $country
     * @return Address
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

    /**
     * Set master
     *
     * @param \Civi\Core\Address $master
     * @return Address
     */
    public function setMaster(\Civi\Core\Address $master = null)
    {
        $this->master = $master;

        return $this;
    }

    /**
     * Get master
     *
     * @return \Civi\Core\Address 
     */
    public function getMaster()
    {
        return $this->master;
    }
}
