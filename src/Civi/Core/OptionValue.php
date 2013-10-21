<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * OptionValue
 *
 * @ORM\Table(name="civicrm_option_value", indexes={@ORM\Index(name="index_option_group_id_value", columns={"value", "option_group_id"}), @ORM\Index(name="index_option_group_id_name", columns={"name", "option_group_id"}), @ORM\Index(name="FK_civicrm_option_value_option_group_id", columns={"option_group_id"}), @ORM\Index(name="FK_civicrm_option_value_component_id", columns={"component_id"}), @ORM\Index(name="FK_civicrm_option_value_domain_id", columns={"domain_id"})})
 * @ORM\Entity
 */
class OptionValue extends \Civi\Core\Entity
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
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=512, nullable=false)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="grouping", type="string", length=255, nullable=true)
     */
    private $grouping;

    /**
     * @var integer
     *
     * @ORM\Column(name="filter", type="integer", nullable=true)
     */
    private $filter;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_optgroup", type="boolean", nullable=true)
     */
    private $isOptgroup = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility_id", type="integer", nullable=true)
     */
    private $visibilityId;

    /**
     * @var \Civi\Core\OptionGroup
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\OptionGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="option_group_id", referencedColumnName="id")
     * })
     */
    private $optionGroup;

    /**
     * @var \Civi\Core\Component
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="component_id", referencedColumnName="id")
     * })
     */
    private $component;

    /**
     * @var \Civi\Core\Domain
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Domain")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * })
     */
    private $domain;



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
     * Set label
     *
     * @param string $label
     * @return OptionValue
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
     * Set value
     *
     * @param string $value
     * @return OptionValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return OptionValue
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
     * Set grouping
     *
     * @param string $grouping
     * @return OptionValue
     */
    public function setGrouping($grouping)
    {
        $this->grouping = $grouping;

        return $this;
    }

    /**
     * Get grouping
     *
     * @return string 
     */
    public function getGrouping()
    {
        return $this->grouping;
    }

    /**
     * Set filter
     *
     * @param integer $filter
     * @return OptionValue
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get filter
     *
     * @return integer 
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return OptionValue
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
     * Set weight
     *
     * @param integer $weight
     * @return OptionValue
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
     * Set description
     *
     * @param string $description
     * @return OptionValue
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isOptgroup
     *
     * @param boolean $isOptgroup
     * @return OptionValue
     */
    public function setIsOptgroup($isOptgroup)
    {
        $this->isOptgroup = $isOptgroup;

        return $this;
    }

    /**
     * Get isOptgroup
     *
     * @return boolean 
     */
    public function getIsOptgroup()
    {
        return $this->isOptgroup;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return OptionValue
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
     * @return OptionValue
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
     * Set visibilityId
     *
     * @param integer $visibilityId
     * @return OptionValue
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

    /**
     * Set optionGroup
     *
     * @param \Civi\Core\OptionGroup $optionGroup
     * @return OptionValue
     */
    public function setOptionGroup(\Civi\Core\OptionGroup $optionGroup = null)
    {
        $this->optionGroup = $optionGroup;

        return $this;
    }

    /**
     * Get optionGroup
     *
     * @return \Civi\Core\OptionGroup 
     */
    public function getOptionGroup()
    {
        return $this->optionGroup;
    }

    /**
     * Set component
     *
     * @param \Civi\Core\Component $component
     * @return OptionValue
     */
    public function setComponent(\Civi\Core\Component $component = null)
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get component
     *
     * @return \Civi\Core\Component 
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return OptionValue
     */
    public function setDomain(\Civi\Core\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \Civi\Core\Domain 
     */
    public function getDomain()
    {
        return $this->domain;
    }
}
