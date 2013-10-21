<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Email
 *
 * @ORM\Table(name="civicrm_email", indexes={@ORM\Index(name="index_location_type", columns={"location_type_id"}), @ORM\Index(name="UI_email", columns={"email"}), @ORM\Index(name="index_is_primary", columns={"is_primary"}), @ORM\Index(name="index_is_billing", columns={"is_billing"}), @ORM\Index(name="FK_civicrm_email_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class Email extends \Civi\Core\Entity
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
     * @ORM\Column(name="email", type="string", length=254, nullable=true)
     */
    private $email;

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
     * @var boolean
     *
     * @ORM\Column(name="on_hold", type="boolean", nullable=false)
     */
    private $onHold = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_bulkmail", type="boolean", nullable=false)
     */
    private $isBulkmail = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="hold_date", type="datetime", nullable=true)
     */
    private $holdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="reset_date", type="datetime", nullable=true)
     */
    private $resetDate;

    /**
     * @var string
     *
     * @ORM\Column(name="signature_text", type="text", nullable=true)
     */
    private $signatureText;

    /**
     * @var string
     *
     * @ORM\Column(name="signature_html", type="text", nullable=true)
     */
    private $signatureHtml;

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
     * @return Email
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
     * Set email
     *
     * @param string $email
     * @return Email
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set isPrimary
     *
     * @param boolean $isPrimary
     * @return Email
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
     * @return Email
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
     * Set onHold
     *
     * @param boolean $onHold
     * @return Email
     */
    public function setOnHold($onHold)
    {
        $this->onHold = $onHold;

        return $this;
    }

    /**
     * Get onHold
     *
     * @return boolean 
     */
    public function getOnHold()
    {
        return $this->onHold;
    }

    /**
     * Set isBulkmail
     *
     * @param boolean $isBulkmail
     * @return Email
     */
    public function setIsBulkmail($isBulkmail)
    {
        $this->isBulkmail = $isBulkmail;

        return $this;
    }

    /**
     * Get isBulkmail
     *
     * @return boolean 
     */
    public function getIsBulkmail()
    {
        return $this->isBulkmail;
    }

    /**
     * Set holdDate
     *
     * @param \DateTime $holdDate
     * @return Email
     */
    public function setHoldDate($holdDate)
    {
        $this->holdDate = $holdDate;

        return $this;
    }

    /**
     * Get holdDate
     *
     * @return \DateTime 
     */
    public function getHoldDate()
    {
        return $this->holdDate;
    }

    /**
     * Set resetDate
     *
     * @param \DateTime $resetDate
     * @return Email
     */
    public function setResetDate($resetDate)
    {
        $this->resetDate = $resetDate;

        return $this;
    }

    /**
     * Get resetDate
     *
     * @return \DateTime 
     */
    public function getResetDate()
    {
        return $this->resetDate;
    }

    /**
     * Set signatureText
     *
     * @param string $signatureText
     * @return Email
     */
    public function setSignatureText($signatureText)
    {
        $this->signatureText = $signatureText;

        return $this;
    }

    /**
     * Get signatureText
     *
     * @return string 
     */
    public function getSignatureText()
    {
        return $this->signatureText;
    }

    /**
     * Set signatureHtml
     *
     * @param string $signatureHtml
     * @return Email
     */
    public function setSignatureHtml($signatureHtml)
    {
        $this->signatureHtml = $signatureHtml;

        return $this;
    }

    /**
     * Get signatureHtml
     *
     * @return string 
     */
    public function getSignatureHtml()
    {
        return $this->signatureHtml;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Email
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
