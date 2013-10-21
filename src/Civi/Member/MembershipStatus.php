<?php

namespace Civi\Member;

use Doctrine\ORM\Mapping as ORM;

/**
 * MembershipStatus
 *
 * @ORM\Table(name="civicrm_membership_status")
 * @ORM\Entity
 */
class MembershipStatus extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=128, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=128, nullable=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="start_event", type="string", nullable=true)
     */
    private $startEvent;

    /**
     * @var string
     *
     * @ORM\Column(name="start_event_adjust_unit", type="string", nullable=true)
     */
    private $startEventAdjustUnit;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_event_adjust_interval", type="integer", nullable=true)
     */
    private $startEventAdjustInterval;

    /**
     * @var string
     *
     * @ORM\Column(name="end_event", type="string", nullable=true)
     */
    private $endEvent;

    /**
     * @var string
     *
     * @ORM\Column(name="end_event_adjust_unit", type="string", nullable=true)
     */
    private $endEventAdjustUnit;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_event_adjust_interval", type="integer", nullable=true)
     */
    private $endEventAdjustInterval;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_current_member", type="boolean", nullable=true)
     */
    private $isCurrentMember;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_admin", type="boolean", nullable=true)
     */
    private $isAdmin;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=true)
     */
    private $weight;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved = '0';



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
     * Set name
     *
     * @param string $name
     * @return MembershipStatus
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
     * Set label
     *
     * @param string $label
     * @return MembershipStatus
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set startEvent
     *
     * @param string $startEvent
     * @return MembershipStatus
     */
    public function setStartEvent($startEvent)
    {
        $this->startEvent = $startEvent;

        return $this;
    }

    /**
     * Get startEvent
     *
     * @return string 
     */
    public function getStartEvent()
    {
        return $this->startEvent;
    }

    /**
     * Set startEventAdjustUnit
     *
     * @param string $startEventAdjustUnit
     * @return MembershipStatus
     */
    public function setStartEventAdjustUnit($startEventAdjustUnit)
    {
        $this->startEventAdjustUnit = $startEventAdjustUnit;

        return $this;
    }

    /**
     * Get startEventAdjustUnit
     *
     * @return string 
     */
    public function getStartEventAdjustUnit()
    {
        return $this->startEventAdjustUnit;
    }

    /**
     * Set startEventAdjustInterval
     *
     * @param integer $startEventAdjustInterval
     * @return MembershipStatus
     */
    public function setStartEventAdjustInterval($startEventAdjustInterval)
    {
        $this->startEventAdjustInterval = $startEventAdjustInterval;

        return $this;
    }

    /**
     * Get startEventAdjustInterval
     *
     * @return integer 
     */
    public function getStartEventAdjustInterval()
    {
        return $this->startEventAdjustInterval;
    }

    /**
     * Set endEvent
     *
     * @param string $endEvent
     * @return MembershipStatus
     */
    public function setEndEvent($endEvent)
    {
        $this->endEvent = $endEvent;

        return $this;
    }

    /**
     * Get endEvent
     *
     * @return string 
     */
    public function getEndEvent()
    {
        return $this->endEvent;
    }

    /**
     * Set endEventAdjustUnit
     *
     * @param string $endEventAdjustUnit
     * @return MembershipStatus
     */
    public function setEndEventAdjustUnit($endEventAdjustUnit)
    {
        $this->endEventAdjustUnit = $endEventAdjustUnit;

        return $this;
    }

    /**
     * Get endEventAdjustUnit
     *
     * @return string 
     */
    public function getEndEventAdjustUnit()
    {
        return $this->endEventAdjustUnit;
    }

    /**
     * Set endEventAdjustInterval
     *
     * @param integer $endEventAdjustInterval
     * @return MembershipStatus
     */
    public function setEndEventAdjustInterval($endEventAdjustInterval)
    {
        $this->endEventAdjustInterval = $endEventAdjustInterval;

        return $this;
    }

    /**
     * Get endEventAdjustInterval
     *
     * @return integer 
     */
    public function getEndEventAdjustInterval()
    {
        return $this->endEventAdjustInterval;
    }

    /**
     * Set isCurrentMember
     *
     * @param boolean $isCurrentMember
     * @return MembershipStatus
     */
    public function setIsCurrentMember($isCurrentMember)
    {
        $this->isCurrentMember = $isCurrentMember;

        return $this;
    }

    /**
     * Get isCurrentMember
     *
     * @return boolean 
     */
    public function getIsCurrentMember()
    {
        return $this->isCurrentMember;
    }

    /**
     * Set isAdmin
     *
     * @param boolean $isAdmin
     * @return MembershipStatus
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isAdmin
     *
     * @return boolean 
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return MembershipStatus
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return MembershipStatus
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return MembershipStatus
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return MembershipStatus
     */
    public function setIsReserved($isReserved)
    {
        $this->isReserved = $isReserved;

        return $this;
    }

    /**
     * Get isReserved
     *
     * @return boolean 
     */
    public function getIsReserved()
    {
        return $this->isReserved;
    }
}
