<?php

namespace Civi\Mailing\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Queue
 *
 * @ORM\Table(name="civicrm_mailing_event_queue", indexes={@ORM\Index(name="FK_civicrm_mailing_event_queue_job_id", columns={"job_id"}), @ORM\Index(name="FK_civicrm_mailing_event_queue_email_id", columns={"email_id"}), @ORM\Index(name="FK_civicrm_mailing_event_queue_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_mailing_event_queue_phone_id", columns={"phone_id"})})
 * @ORM\Entity
 */
class Queue extends \Civi\Core\Entity
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
     * @ORM\Column(name="hash", type="string", length=255, nullable=false)
     */
    private $hash;

    /**
     * @var \Civi\Mailing\Job
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Job")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="job_id", referencedColumnName="id")
     * })
     */
    private $job;

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
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * })
     */
    private $contact;

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
     * Set hash
     *
     * @param string $hash
     * @return Queue
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string 
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set job
     *
     * @param \Civi\Mailing\Job $job
     * @return Queue
     */
    public function setJob(\Civi\Mailing\Job $job = null)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Get job
     *
     * @return \Civi\Mailing\Job 
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set email
     *
     * @param \Civi\Core\Email $email
     * @return Queue
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Queue
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
     * Set phone
     *
     * @param \Civi\Core\Phone $phone
     * @return Queue
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
