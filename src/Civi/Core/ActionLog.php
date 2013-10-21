<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActionLog
 *
 * @ORM\Table(name="civicrm_action_log", indexes={@ORM\Index(name="FK_civicrm_action_log_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_action_log_action_schedule_id", columns={"action_schedule_id"})})
 * @ORM\Entity
 */
class ActionLog extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    private $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_table", type="string", length=255, nullable=true)
     */
    private $entityTable;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="action_date_time", type="datetime", nullable=true)
     */
    private $actionDateTime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_error", type="boolean", nullable=true)
     */
    private $isError = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var integer
     *
     * @ORM\Column(name="repetition_number", type="integer", nullable=true)
     */
    private $repetitionNumber;

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
     * @var \Civi\Core\ActionSchedule
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\ActionSchedule")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="action_schedule_id", referencedColumnName="id")
     * })
     */
    private $actionSchedule;



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
     * Set entityId
     *
     * @param integer $entityId
     * @return ActionLog
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer 
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set entityTable
     *
     * @param string $entityTable
     * @return ActionLog
     */
    public function setEntityTable($entityTable)
    {
        $this->entityTable = $entityTable;

        return $this;
    }

    /**
     * Get entityTable
     *
     * @return string 
     */
    public function getEntityTable()
    {
        return $this->entityTable;
    }

    /**
     * Set actionDateTime
     *
     * @param \DateTime $actionDateTime
     * @return ActionLog
     */
    public function setActionDateTime($actionDateTime)
    {
        $this->actionDateTime = $actionDateTime;

        return $this;
    }

    /**
     * Get actionDateTime
     *
     * @return \DateTime 
     */
    public function getActionDateTime()
    {
        return $this->actionDateTime;
    }

    /**
     * Set isError
     *
     * @param boolean $isError
     * @return ActionLog
     */
    public function setIsError($isError)
    {
        $this->isError = $isError;

        return $this;
    }

    /**
     * Get isError
     *
     * @return boolean 
     */
    public function getIsError()
    {
        return $this->isError;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return ActionLog
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set repetitionNumber
     *
     * @param integer $repetitionNumber
     * @return ActionLog
     */
    public function setRepetitionNumber($repetitionNumber)
    {
        $this->repetitionNumber = $repetitionNumber;

        return $this;
    }

    /**
     * Get repetitionNumber
     *
     * @return integer 
     */
    public function getRepetitionNumber()
    {
        return $this->repetitionNumber;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return ActionLog
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
     * Set actionSchedule
     *
     * @param \Civi\Core\ActionSchedule $actionSchedule
     * @return ActionLog
     */
    public function setActionSchedule(\Civi\Core\ActionSchedule $actionSchedule = null)
    {
        $this->actionSchedule = $actionSchedule;

        return $this;
    }

    /**
     * Get actionSchedule
     *
     * @return \Civi\Core\ActionSchedule 
     */
    public function getActionSchedule()
    {
        return $this->actionSchedule;
    }
}
