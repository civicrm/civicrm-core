<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * CustomField
 *
 * @ORM\Table(name="civicrm_custom_field", uniqueConstraints={@ORM\UniqueConstraint(name="UI_label_custom_group_id", columns={"label", "custom_group_id"}), @ORM\UniqueConstraint(name="UI_name_custom_group_id", columns={"name", "custom_group_id"})}, indexes={@ORM\Index(name="FK_civicrm_custom_field_custom_group_id", columns={"custom_group_id"})})
 * @ORM\Entity
 */
class CustomField extends \Civi\Core\Entity
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
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="data_type", type="string", nullable=false)
     */
    private $dataType;

    /**
     * @var string
     *
     * @ORM\Column(name="html_type", type="string", nullable=false)
     */
    private $htmlType;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value", type="string", length=255, nullable=true)
     */
    private $defaultValue;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_required", type="boolean", nullable=true)
     */
    private $isRequired;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_searchable", type="boolean", nullable=true)
     */
    private $isSearchable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_search_range", type="boolean", nullable=true)
     */
    private $isSearchRange = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="help_pre", type="text", nullable=true)
     */
    private $helpPre;

    /**
     * @var string
     *
     * @ORM\Column(name="help_post", type="text", nullable=true)
     */
    private $helpPost;

    /**
     * @var string
     *
     * @ORM\Column(name="mask", type="string", length=64, nullable=true)
     */
    private $mask;

    /**
     * @var string
     *
     * @ORM\Column(name="attributes", type="string", length=255, nullable=true)
     */
    private $attributes;

    /**
     * @var string
     *
     * @ORM\Column(name="javascript", type="string", length=255, nullable=true)
     */
    private $javascript;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_view", type="boolean", nullable=true)
     */
    private $isView;

    /**
     * @var integer
     *
     * @ORM\Column(name="options_per_line", type="integer", nullable=true)
     */
    private $optionsPerLine;

    /**
     * @var integer
     *
     * @ORM\Column(name="text_length", type="integer", nullable=true)
     */
    private $textLength;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_date_years", type="integer", nullable=true)
     */
    private $startDateYears;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_date_years", type="integer", nullable=true)
     */
    private $endDateYears;

    /**
     * @var string
     *
     * @ORM\Column(name="date_format", type="string", length=64, nullable=true)
     */
    private $dateFormat;

    /**
     * @var integer
     *
     * @ORM\Column(name="time_format", type="integer", nullable=true)
     */
    private $timeFormat;

    /**
     * @var integer
     *
     * @ORM\Column(name="note_columns", type="integer", nullable=true)
     */
    private $noteColumns;

    /**
     * @var integer
     *
     * @ORM\Column(name="note_rows", type="integer", nullable=true)
     */
    private $noteRows;

    /**
     * @var string
     *
     * @ORM\Column(name="column_name", type="string", length=255, nullable=true)
     */
    private $columnName;

    /**
     * @var integer
     *
     * @ORM\Column(name="option_group_id", type="integer", nullable=true)
     */
    private $optionGroupId;

    /**
     * @var string
     *
     * @ORM\Column(name="filter", type="string", length=255, nullable=true)
     */
    private $filter;

    /**
     * @var \Civi\Core\CustomGroup
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\CustomGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="custom_group_id", referencedColumnName="id")
     * })
     */
    private $customGroup;



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
     * @return CustomField
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
     * @return CustomField
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
     * Set dataType
     *
     * @param string $dataType
     * @return CustomField
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;

        return $this;
    }

    /**
     * Get dataType
     *
     * @return string 
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Set htmlType
     *
     * @param string $htmlType
     * @return CustomField
     */
    public function setHtmlType($htmlType)
    {
        $this->htmlType = $htmlType;

        return $this;
    }

    /**
     * Get htmlType
     *
     * @return string 
     */
    public function getHtmlType()
    {
        return $this->htmlType;
    }

    /**
     * Set defaultValue
     *
     * @param string $defaultValue
     * @return CustomField
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * Get defaultValue
     *
     * @return string 
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set isRequired
     *
     * @param boolean $isRequired
     * @return CustomField
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
     * Set isSearchable
     *
     * @param boolean $isSearchable
     * @return CustomField
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
     * Set isSearchRange
     *
     * @param boolean $isSearchRange
     * @return CustomField
     */
    public function setIsSearchRange($isSearchRange)
    {
        $this->isSearchRange = $isSearchRange;

        return $this;
    }

    /**
     * Get isSearchRange
     *
     * @return boolean 
     */
    public function getIsSearchRange()
    {
        return $this->isSearchRange;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return CustomField
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
     * Set helpPre
     *
     * @param string $helpPre
     * @return CustomField
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
     * Set helpPost
     *
     * @param string $helpPost
     * @return CustomField
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
     * Set mask
     *
     * @param string $mask
     * @return CustomField
     */
    public function setMask($mask)
    {
        $this->mask = $mask;

        return $this;
    }

    /**
     * Get mask
     *
     * @return string 
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * Set attributes
     *
     * @param string $attributes
     * @return CustomField
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get attributes
     *
     * @return string 
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set javascript
     *
     * @param string $javascript
     * @return CustomField
     */
    public function setJavascript($javascript)
    {
        $this->javascript = $javascript;

        return $this;
    }

    /**
     * Get javascript
     *
     * @return string 
     */
    public function getJavascript()
    {
        return $this->javascript;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return CustomField
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
     * @return CustomField
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
     * Set optionsPerLine
     *
     * @param integer $optionsPerLine
     * @return CustomField
     */
    public function setOptionsPerLine($optionsPerLine)
    {
        $this->optionsPerLine = $optionsPerLine;

        return $this;
    }

    /**
     * Get optionsPerLine
     *
     * @return integer 
     */
    public function getOptionsPerLine()
    {
        return $this->optionsPerLine;
    }

    /**
     * Set textLength
     *
     * @param integer $textLength
     * @return CustomField
     */
    public function setTextLength($textLength)
    {
        $this->textLength = $textLength;

        return $this;
    }

    /**
     * Get textLength
     *
     * @return integer 
     */
    public function getTextLength()
    {
        return $this->textLength;
    }

    /**
     * Set startDateYears
     *
     * @param integer $startDateYears
     * @return CustomField
     */
    public function setStartDateYears($startDateYears)
    {
        $this->startDateYears = $startDateYears;

        return $this;
    }

    /**
     * Get startDateYears
     *
     * @return integer 
     */
    public function getStartDateYears()
    {
        return $this->startDateYears;
    }

    /**
     * Set endDateYears
     *
     * @param integer $endDateYears
     * @return CustomField
     */
    public function setEndDateYears($endDateYears)
    {
        $this->endDateYears = $endDateYears;

        return $this;
    }

    /**
     * Get endDateYears
     *
     * @return integer 
     */
    public function getEndDateYears()
    {
        return $this->endDateYears;
    }

    /**
     * Set dateFormat
     *
     * @param string $dateFormat
     * @return CustomField
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Get dateFormat
     *
     * @return string 
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * Set timeFormat
     *
     * @param integer $timeFormat
     * @return CustomField
     */
    public function setTimeFormat($timeFormat)
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }

    /**
     * Get timeFormat
     *
     * @return integer 
     */
    public function getTimeFormat()
    {
        return $this->timeFormat;
    }

    /**
     * Set noteColumns
     *
     * @param integer $noteColumns
     * @return CustomField
     */
    public function setNoteColumns($noteColumns)
    {
        $this->noteColumns = $noteColumns;

        return $this;
    }

    /**
     * Get noteColumns
     *
     * @return integer 
     */
    public function getNoteColumns()
    {
        return $this->noteColumns;
    }

    /**
     * Set noteRows
     *
     * @param integer $noteRows
     * @return CustomField
     */
    public function setNoteRows($noteRows)
    {
        $this->noteRows = $noteRows;

        return $this;
    }

    /**
     * Get noteRows
     *
     * @return integer 
     */
    public function getNoteRows()
    {
        return $this->noteRows;
    }

    /**
     * Set columnName
     *
     * @param string $columnName
     * @return CustomField
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;

        return $this;
    }

    /**
     * Get columnName
     *
     * @return string 
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * Set optionGroupId
     *
     * @param integer $optionGroupId
     * @return CustomField
     */
    public function setOptionGroupId($optionGroupId)
    {
        $this->optionGroupId = $optionGroupId;

        return $this;
    }

    /**
     * Get optionGroupId
     *
     * @return integer 
     */
    public function getOptionGroupId()
    {
        return $this->optionGroupId;
    }

    /**
     * Set filter
     *
     * @param string $filter
     * @return CustomField
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get filter
     *
     * @return string 
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set customGroup
     *
     * @param \Civi\Core\CustomGroup $customGroup
     * @return CustomField
     */
    public function setCustomGroup(\Civi\Core\CustomGroup $customGroup = null)
    {
        $this->customGroup = $customGroup;

        return $this;
    }

    /**
     * Get customGroup
     *
     * @return \Civi\Core\CustomGroup 
     */
    public function getCustomGroup()
    {
        return $this->customGroup;
    }
}
