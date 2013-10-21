<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * OpenID
 *
 * @ORM\Table(name="civicrm_openid", uniqueConstraints={@ORM\UniqueConstraint(name="UI_openid", columns={"openid"})}, indexes={@ORM\Index(name="index_location_type", columns={"location_type_id"}), @ORM\Index(name="FK_civicrm_openid_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class OpenID extends \Civi\Core\Entity
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
     * @ORM\Column(name="openid", type="string", length=255, nullable=true)
     */
    private $openid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="allowed_to_login", type="boolean", nullable=false)
     */
    private $allowedToLogin = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_primary", type="boolean", nullable=true)
     */
    private $isPrimary = '0';

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
     * @return OpenID
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
     * Set openid
     *
     * @param string $openid
     * @return OpenID
     */
    public function setOpenid($openid)
    {
        $this->openid = $openid;

        return $this;
    }

    /**
     * Get openid
     *
     * @return string 
     */
    public function getOpenid()
    {
        return $this->openid;
    }

    /**
     * Set allowedToLogin
     *
     * @param boolean $allowedToLogin
     * @return OpenID
     */
    public function setAllowedToLogin($allowedToLogin)
    {
        $this->allowedToLogin = $allowedToLogin;

        return $this;
    }

    /**
     * Get allowedToLogin
     *
     * @return boolean 
     */
    public function getAllowedToLogin()
    {
        return $this->allowedToLogin;
    }

    /**
     * Set isPrimary
     *
     * @param boolean $isPrimary
     * @return OpenID
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return OpenID
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
