<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Phone
 *
 * @ORM\Table(name="civicrm_phone", indexes={@ORM\Index(name="index_location_type", columns={"location_type_id"}), @ORM\Index(name="index_is_primary", columns={"is_primary"}), @ORM\Index(name="index_is_billing", columns={"is_billing"}), @ORM\Index(name="UI_mobile_provider_id", columns={"mobile_provider_id"}), @ORM\Index(name="index_phone_numeric", columns={"phone_numeric"}), @ORM\Index(name="FK_civicrm_phone_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class Phone extends \Civi\Core\Entity
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
     * @var integer
     *
     * @ORM\Column(name="mobile_provider_id", type="integer", nullable=true)
     */
    private $mobileProviderId;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=32, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_ext", type="string", length=16, nullable=true)
     */
    private $phoneExt;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_numeric", type="string", length=32, nullable=true)
     */
    private $phoneNumeric;

    /**
     * @var integer
     *
     * @ORM\Column(name="phone_type_id", type="integer", nullable=true)
     */
    private $phoneTypeId;

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
     * @return Phone
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
     * @return Phone
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
     * @return Phone
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
     * Set mobileProviderId
     *
     * @param integer $mobileProviderId
     * @return Phone
     */
    public function setMobileProviderId($mobileProviderId)
    {
        $this->mobileProviderId = $mobileProviderId;

        return $this;
    }

    /**
     * Get mobileProviderId
     *
     * @return integer 
     */
    public function getMobileProviderId()
    {
        return $this->mobileProviderId;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set phoneExt
     *
     * @param string $phoneExt
     * @return Phone
     */
    public function setPhoneExt($phoneExt)
    {
        $this->phoneExt = $phoneExt;

        return $this;
    }

    /**
     * Get phoneExt
     *
     * @return string 
     */
    public function getPhoneExt()
    {
        return $this->phoneExt;
    }

    /**
     * Set phoneNumeric
     *
     * @param string $phoneNumeric
     * @return Phone
     */
    public function setPhoneNumeric($phoneNumeric)
    {
        $this->phoneNumeric = $phoneNumeric;

        return $this;
    }

    /**
     * Get phoneNumeric
     *
     * @return string 
     */
    public function getPhoneNumeric()
    {
        return $this->phoneNumeric;
    }

    /**
     * Set phoneTypeId
     *
     * @param integer $phoneTypeId
     * @return Phone
     */
    public function setPhoneTypeId($phoneTypeId)
    {
        $this->phoneTypeId = $phoneTypeId;

        return $this;
    }

    /**
     * Get phoneTypeId
     *
     * @return integer 
     */
    public function getPhoneTypeId()
    {
        return $this->phoneTypeId;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Phone
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
}
