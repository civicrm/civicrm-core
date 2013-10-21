<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Recipients
 *
 * @ORM\Table(name="civicrm_mailing_recipients", indexes={@ORM\Index(name="FK_civicrm_mailing_recipients_mailing_id", columns={"mailing_id"}), @ORM\Index(name="FK_civicrm_mailing_recipients_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_mailing_recipients_email_id", columns={"email_id"}), @ORM\Index(name="FK_civicrm_mailing_recipients_phone_id", columns={"phone_id"})})
 * @ORM\Entity
 */
class Recipients extends \Civi\Core\Entity
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
     * @var \Civi\Mailing\Mailing
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Mailing")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mailing_id", referencedColumnName="id")
     * })
     */
    private $mailing;

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
     * @var \Civi\Core\Email
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Email")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="email_id", referencedColumnName="id")
     * })
     */
    private $email;

    /**
     * @var \Civi\Core\Phone
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Phone")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="phone_id", referencedColumnName="id")
     * })
     */
    private $phone;



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
     * Set mailing
     *
     * @param \Civi\Mailing\Mailing $mailing
     * @return Recipients
     */
    public function setMailing(\Civi\Mailing\Mailing $mailing = null)
    {
        $this->mailing = $mailing;

        return $this;
    }

    /**
     * Get mailing
     *
     * @return \Civi\Mailing\Mailing 
     */
    public function getMailing()
    {
        return $this->mailing;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Recipients
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
     * Set email
     *
     * @param \Civi\Core\Email $email
     * @return Recipients
     */
    public function setEmail(\Civi\Core\Email $email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return \Civi\Core\Email 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phone
     *
     * @param \Civi\Core\Phone $phone
     * @return Recipients
     */
    public function setPhone(\Civi\Core\Phone $phone = null)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return \Civi\Core\Phone 
     */
    public function getPhone()
    {
        return $this->phone;
    }
}
