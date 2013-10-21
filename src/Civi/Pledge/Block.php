<?php

namespace Civi\Pledge;

use Doctrine\ORM\Mapping as ORM;

/**
 * Block
 *
 * @ORM\Table(name="civicrm_pledge_block", indexes={@ORM\Index(name="index_entity", columns={"entity_table", "entity_id"})})
 * @ORM\Entity
 */
class Block extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity_table", type="string", length=64, nullable=true)
     */
    private $entityTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    private $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="pledge_frequency_unit", type="string", length=128, nullable=true)
     */
    private $pledgeFrequencyUnit;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_pledge_interval", type="boolean", nullable=true)
     */
    private $isPledgeInterval = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="max_reminders", type="integer", nullable=true)
     */
    private $maxReminders = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="initial_reminder_day", type="integer", nullable=true)
     */
    private $initialReminderDay = '5';

    /**
     * @var integer
     *
     * @ORM\Column(name="additional_reminder_day", type="integer", nullable=true)
     */
    private $additionalReminderDay = '5';



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
     * Set entityTable
     *
     * @param string $entityTable
     * @return Block
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
     * Set entityId
     *
     * @param integer $entityId
     * @return Block
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
     * Set pledgeFrequencyUnit
     *
     * @param string $pledgeFrequencyUnit
     * @return Block
     */
    public function setPledgeFrequencyUnit($pledgeFrequencyUnit)
    {
        $this->pledgeFrequencyUnit = $pledgeFrequencyUnit;

        return $this;
    }

    /**
     * Get pledgeFrequencyUnit
     *
     * @return string 
     */
    public function getPledgeFrequencyUnit()
    {
        return $this->pledgeFrequencyUnit;
    }

    /**
     * Set isPledgeInterval
     *
     * @param boolean $isPledgeInterval
     * @return Block
     */
    public function setIsPledgeInterval($isPledgeInterval)
    {
        $this->isPledgeInterval = $isPledgeInterval;

        return $this;
    }

    /**
     * Get isPledgeInterval
     *
     * @return boolean 
     */
    public function getIsPledgeInterval()
    {
        return $this->isPledgeInterval;
    }

    /**
     * Set maxReminders
     *
     * @param integer $maxReminders
     * @return Block
     */
    public function setMaxReminders($maxReminders)
    {
        $this->maxReminders = $maxReminders;

        return $this;
    }

    /**
     * Get maxReminders
     *
     * @return integer 
     */
    public function getMaxReminders()
    {
        return $this->maxReminders;
    }

    /**
     * Set initialReminderDay
     *
     * @param integer $initialReminderDay
     * @return Block
     */
    public function setInitialReminderDay($initialReminderDay)
    {
        $this->initialReminderDay = $initialReminderDay;

        return $this;
    }

    /**
     * Get initialReminderDay
     *
     * @return integer 
     */
    public function getInitialReminderDay()
    {
        return $this->initialReminderDay;
    }

    /**
     * Set additionalReminderDay
     *
     * @param integer $additionalReminderDay
     * @return Block
     */
    public function setAdditionalReminderDay($additionalReminderDay)
    {
        $this->additionalReminderDay = $additionalReminderDay;

        return $this;
    }

    /**
     * Get additionalReminderDay
     *
     * @return integer 
     */
    public function getAdditionalReminderDay()
    {
        return $this->additionalReminderDay;
    }
}
