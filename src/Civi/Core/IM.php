<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * IM
 *
 * @ORM\Table(name="civicrm_im", indexes={@ORM\Index(name="index_location_type", columns={"location_type_id"}), @ORM\Index(name="UI_provider_id", columns={"provider_id"}), @ORM\Index(name="index_is_primary", columns={"is_primary"}), @ORM\Index(name="index_is_billing", columns={"is_billing"}), @ORM\Index(name="FK_civicrm_im_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class IM extends \Civi\Core\Entity
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="provider_id", type="integer", nullable=true)
     */
    private $providerId;

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
     * @return IM
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
     * Set name
     *
     * @param string $name
     * @return IM
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
     * Set providerId
     *
     * @param integer $providerId
     * @return IM
     */
    public function setProviderId($providerId)
    {
        $this->providerId = $providerId;

        return $this;
    }

    /**
     * Get providerId
     *
     * @return integer 
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    /**
     * Set isPrimary
     *
     * @param boolean $isPrimary
     * @return IM
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
     * @return IM
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return IM
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
