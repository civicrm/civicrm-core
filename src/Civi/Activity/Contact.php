<?php

namespace Civi\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contact
 *
 * @ORM\Table(name="civicrm_activity_contact", uniqueConstraints={@ORM\UniqueConstraint(name="UI_activity_contact", columns={"contact_id", "activity_id", "record_type_id"})}, indexes={@ORM\Index(name="FK_civicrm_activity_contact_activity_id", columns={"activity_id"}), @ORM\Index(name="IDX_4B4F91E3E7A1254A", columns={"contact_id"})})
 * @ORM\Entity
 */
class Contact extends \Civi\Core\Entity
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
     * @ORM\Column(name="record_type_id", type="integer", nullable=true)
     */
    private $recordTypeId;

    /**
     * @var \Civi\Activity\Activity
     *
     * @ORM\ManyToOne(targetEntity="Civi\Activity\Activity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="activity_id", referencedColumnName="id")
     * })
     */
    private $activity;

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
     * Set recordTypeId
     *
     * @param integer $recordTypeId
     * @return Contact
     */
    public function setRecordTypeId($recordTypeId)
    {
        $this->recordTypeId = $recordTypeId;

        return $this;
    }

    /**
     * Get recordTypeId
     *
     * @return integer 
     */
    public function getRecordTypeId()
    {
        return $this->recordTypeId;
    }

    /**
     * Set activity
     *
     * @param \Civi\Activity\Activity $activity
     * @return Contact
     */
    public function setActivity(\Civi\Activity\Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \Civi\Activity\Activity 
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Contact
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
