<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActionMapping
 *
 * @ORM\Table(name="civicrm_action_mapping")
 * @ORM\Entity
 */
class ActionMapping extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity", type="string", length=64, nullable=true)
     */
    private $entity;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_value", type="string", length=64, nullable=true)
     */
    private $entityValue;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_value_label", type="string", length=64, nullable=true)
     */
    private $entityValueLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_status", type="string", length=64, nullable=true)
     */
    private $entityStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_status_label", type="string", length=64, nullable=true)
     */
    private $entityStatusLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_date_start", type="string", length=64, nullable=true)
     */
    private $entityDateStart;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_date_end", type="string", length=64, nullable=true)
     */
    private $entityDateEnd;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_recipient", type="string", length=64, nullable=true)
     */
    private $entityRecipient;



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
     * Set entity
     *
     * @param string $entity
     * @return ActionMapping
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return string 
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set entityValue
     *
     * @param string $entityValue
     * @return ActionMapping
     */
    public function setEntityValue($entityValue)
    {
        $this->entityValue = $entityValue;

        return $this;
    }

    /**
     * Get entityValue
     *
     * @return string 
     */
    public function getEntityValue()
    {
        return $this->entityValue;
    }

    /**
     * Set entityValueLabel
     *
     * @param string $entityValueLabel
     * @return ActionMapping
     */
    public function setEntityValueLabel($entityValueLabel)
    {
        $this->entityValueLabel = $entityValueLabel;

        return $this;
    }

    /**
     * Get entityValueLabel
     *
     * @return string 
     */
    public function getEntityValueLabel()
    {
        return $this->entityValueLabel;
    }

    /**
     * Set entityStatus
     *
     * @param string $entityStatus
     * @return ActionMapping
     */
    public function setEntityStatus($entityStatus)
    {
        $this->entityStatus = $entityStatus;

        return $this;
    }

    /**
     * Get entityStatus
     *
     * @return string 
     */
    public function getEntityStatus()
    {
        return $this->entityStatus;
    }

    /**
     * Set entityStatusLabel
     *
     * @param string $entityStatusLabel
     * @return ActionMapping
     */
    public function setEntityStatusLabel($entityStatusLabel)
    {
        $this->entityStatusLabel = $entityStatusLabel;

        return $this;
    }

    /**
     * Get entityStatusLabel
     *
     * @return string 
     */
    public function getEntityStatusLabel()
    {
        return $this->entityStatusLabel;
    }

    /**
     * Set entityDateStart
     *
     * @param string $entityDateStart
     * @return ActionMapping
     */
    public function setEntityDateStart($entityDateStart)
    {
        $this->entityDateStart = $entityDateStart;

        return $this;
    }

    /**
     * Get entityDateStart
     *
     * @return string 
     */
    public function getEntityDateStart()
    {
        return $this->entityDateStart;
    }

    /**
     * Set entityDateEnd
     *
     * @param string $entityDateEnd
     * @return ActionMapping
     */
    public function setEntityDateEnd($entityDateEnd)
    {
        $this->entityDateEnd = $entityDateEnd;

        return $this;
    }

    /**
     * Get entityDateEnd
     *
     * @return string 
     */
    public function getEntityDateEnd()
    {
        return $this->entityDateEnd;
    }

    /**
     * Set entityRecipient
     *
     * @param string $entityRecipient
     * @return ActionMapping
     */
    public function setEntityRecipient($entityRecipient)
    {
        $this->entityRecipient = $entityRecipient;

        return $this;
    }

    /**
     * Get entityRecipient
     *
     * @return string 
     */
    public function getEntityRecipient()
    {
        return $this->entityRecipient;
    }
}
