<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * SubscriptionHistory
 *
 * @ORM\Table(name="civicrm_subscription_history", indexes={@ORM\Index(name="FK_civicrm_subscription_history_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_subscription_history_group_id", columns={"group_id"})})
 * @ORM\Entity
 */
class SubscriptionHistory extends \Civi\Core\Entity
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", nullable=true)
     */
    private $method;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="tracking", type="string", length=255, nullable=true)
     */
    private $tracking;

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
     * @var \Civi\Contact\Group
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Group")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     * })
     */
    private $group;



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
     * Set date
     *
     * @param \DateTime $date
     * @return SubscriptionHistory
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set method
     *
     * @param string $method
     * @return SubscriptionHistory
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method
     *
     * @return string 
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return SubscriptionHistory
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set tracking
     *
     * @param string $tracking
     * @return SubscriptionHistory
     */
    public function setTracking($tracking)
    {
        $this->tracking = $tracking;

        return $this;
    }

    /**
     * Get tracking
     *
     * @return string 
     */
    public function getTracking()
    {
        return $this->tracking;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return SubscriptionHistory
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
     * Set group
     *
     * @param \Civi\Contact\Group $group
     * @return SubscriptionHistory
     */
    public function setGroup(\Civi\Contact\Group $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Civi\Contact\Group 
     */
    public function getGroup()
    {
        return $this->group;
    }
}
