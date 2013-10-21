<?php

namespace Civi\Dedupe;

use Doctrine\ORM\Mapping as ORM;

/**
 * RuleGroup
 *
 * @ORM\Table(name="civicrm_dedupe_rule_group")
 * @ORM\Entity
 */
class RuleGroup extends \Civi\Core\Entity
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
     * @ORM\Column(name="contact_type", type="string", nullable=true)
     */
    private $contactType;

    /**
     * @var integer
     *
     * @ORM\Column(name="threshold", type="integer", nullable=false)
     */
    private $threshold;

    /**
     * @var string
     *
     * @ORM\Column(name="used", type="string", nullable=false)
     */
    private $used;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved;



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
     * Set contactType
     *
     * @param string $contactType
     * @return RuleGroup
     */
    public function setContactType($contactType)
    {
        $this->contactType = $contactType;

        return $this;
    }

    /**
     * Get contactType
     *
     * @return string 
     */
    public function getContactType()
    {
        return $this->contactType;
    }

    /**
     * Set threshold
     *
     * @param integer $threshold
     * @return RuleGroup
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Get threshold
     *
     * @return integer 
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * Set used
     *
     * @param string $used
     * @return RuleGroup
     */
    public function setUsed($used)
    {
        $this->used = $used;

        return $this;
    }

    /**
     * Get used
     *
     * @return string 
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return RuleGroup
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
     * Set title
     *
     * @param string $title
     * @return RuleGroup
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return RuleGroup
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
