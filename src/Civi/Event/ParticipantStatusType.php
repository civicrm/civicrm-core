<?php

namespace Civi\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * ParticipantStatusType
 *
 * @ORM\Table(name="civicrm_participant_status_type")
 * @ORM\Entity
 */
class ParticipantStatusType extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="class", type="string", nullable=true)
     */
    private $class;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_counted", type="boolean", nullable=true)
     */
    private $isCounted;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility_id", type="integer", nullable=true)
     */
    private $visibilityId;



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
     * @return ParticipantStatusType
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
     * @return ParticipantStatusType
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
     * Set class
     *
     * @param string $class
     * @return ParticipantStatusType
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string 
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return ParticipantStatusType
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

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return ParticipantStatusType
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
     * Set isCounted
     *
     * @param boolean $isCounted
     * @return ParticipantStatusType
     */
    public function setIsCounted($isCounted)
    {
        $this->isCounted = $isCounted;

        return $this;
    }

    /**
     * Get isCounted
     *
     * @return boolean 
     */
    public function getIsCounted()
    {
        return $this->isCounted;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return ParticipantStatusType
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
     * Set visibilityId
     *
     * @param integer $visibilityId
     * @return ParticipantStatusType
     */
    public function setVisibilityId($visibilityId)
    {
        $this->visibilityId = $visibilityId;

        return $this;
    }

    /**
     * Get visibilityId
     *
     * @return integer 
     */
    public function getVisibilityId()
    {
        return $this->visibilityId;
    }
}
