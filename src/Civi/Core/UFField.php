<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * UFField
 *
 * @ORM\Table(name="civicrm_uf_field", indexes={@ORM\Index(name="FK_civicrm_uf_field_uf_group_id", columns={"uf_group_id"}), @ORM\Index(name="FK_civicrm_uf_field_location_type_id", columns={"location_type_id"})})
 * @ORM\Entity
 */
class UFField extends \Civi\Core\Entity
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
     * @ORM\Column(name="field_name", type="string", length=64, nullable=false)
     */
    private $fieldName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_view", type="boolean", nullable=true)
     */
    private $isView = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_required", type="boolean", nullable=true)
     */
    private $isRequired = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="help_post", type="text", nullable=true)
     */
    private $helpPost;

    /**
     * @var string
     *
     * @ORM\Column(name="help_pre", type="text", nullable=true)
     */
    private $helpPre;

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", nullable=true)
     */
    private $visibility = 'User and User Admin Only';

    /**
     * @var boolean
     *
     * @ORM\Column(name="in_selector", type="boolean", nullable=true)
     */
    private $inSelector = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_searchable", type="boolean", nullable=true)
     */
    private $isSearchable = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="phone_type_id", type="integer", nullable=true)
     */
    private $phoneTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="field_type", type="string", length=255, nullable=true)
     */
    private $fieldType;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_multi_summary", type="boolean", nullable=true)
     */
    private $isMultiSummary = '0';

    /**
     * @var \Civi\Core\UFGroup
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\UFGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="uf_group_id", referencedColumnName="id")
     * })
     */
    private $ufGroup;

    /**
     * @var \Civi\Core\LocationType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\LocationType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="location_type_id", referencedColumnName="id")
     * })
     */
    private $locationType;



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
     * Set fieldName
     *
     * @param string $fieldName
     * @return UFField
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * Get fieldName
     *
     * @return string 
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return UFField
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
     * Set isView
     *
     * @param boolean $isView
     * @return UFField
     */
    public function setIsView($isView)
    {
        $this->isView = $isView;

        return $this;
    }

    /**
     * Get isView
     *
     * @return boolean 
     */
    public function getIsView()
    {
        return $this->isView;
    }

    /**
     * Set isRequired
     *
     * @param boolean $isRequired
     * @return UFField
     */
    public function setIsRequired($isRequired)
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * Get isRequired
     *
     * @return boolean 
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return UFField
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
     * Set helpPost
     *
     * @param string $helpPost
     * @return UFField
     */
    public function setHelpPost($helpPost)
    {
        $this->helpPost = $helpPost;

        return $this;
    }

    /**
     * Get helpPost
     *
     * @return string 
     */
    public function getHelpPost()
    {
        return $this->helpPost;
    }

    /**
     * Set helpPre
     *
     * @param string $helpPre
     * @return UFField
     */
    public function setHelpPre($helpPre)
    {
        $this->helpPre = $helpPre;

        return $this;
    }

    /**
     * Get helpPre
     *
     * @return string 
     */
    public function getHelpPre()
    {
        return $this->helpPre;
    }

    /**
     * Set visibility
     *
     * @param string $visibility
     * @return UFField
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return string 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set inSelector
     *
     * @param boolean $inSelector
     * @return UFField
     */
    public function setInSelector($inSelector)
    {
        $this->inSelector = $inSelector;

        return $this;
    }

    /**
     * Get inSelector
     *
     * @return boolean 
     */
    public function getInSelector()
    {
        return $this->inSelector;
    }

    /**
     * Set isSearchable
     *
     * @param boolean $isSearchable
     * @return UFField
     */
    public function setIsSearchable($isSearchable)
    {
        $this->isSearchable = $isSearchable;

        return $this;
    }

    /**
     * Get isSearchable
     *
     * @return boolean 
     */
    public function getIsSearchable()
    {
        return $this->isSearchable;
    }

    /**
     * Set phoneTypeId
     *
     * @param integer $phoneTypeId
     * @return UFField
     */
    public function setPhoneTypeId($phoneTypeId)
    {
        $this->phoneTypeId = $phoneTypeId;

        return $this;
    }

    /**
     * Get phoneTypeId
     *
     * @return integer 
     */
    public function getPhoneTypeId()
    {
        return $this->phoneTypeId;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return UFField
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
     * Set fieldType
     *
     * @param string $fieldType
     * @return UFField
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    /**
     * Get fieldType
     *
     * @return string 
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return UFField
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
     * Set isMultiSummary
     *
     * @param boolean $isMultiSummary
     * @return UFField
     */
    public function setIsMultiSummary($isMultiSummary)
    {
        $this->isMultiSummary = $isMultiSummary;

        return $this;
    }

    /**
     * Get isMultiSummary
     *
     * @return boolean 
     */
    public function getIsMultiSummary()
    {
        return $this->isMultiSummary;
    }

    /**
     * Set ufGroup
     *
     * @param \Civi\Core\UFGroup $ufGroup
     * @return UFField
     */
    public function setUfGroup(\Civi\Core\UFGroup $ufGroup = null)
    {
        $this->ufGroup = $ufGroup;

        return $this;
    }

    /**
     * Get ufGroup
     *
     * @return \Civi\Core\UFGroup 
     */
    public function getUfGroup()
    {
        return $this->ufGroup;
    }

    /**
     * Set locationType
     *
     * @param \Civi\Core\LocationType $locationType
     * @return UFField
     */
    public function setLocationType(\Civi\Core\LocationType $locationType = null)
    {
        $this->locationType = $locationType;

        return $this;
    }

    /**
     * Get locationType
     *
     * @return \Civi\Core\LocationType 
     */
    public function getLocationType()
    {
        return $this->locationType;
    }
}
