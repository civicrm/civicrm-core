<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * CustomGroup
 *
 * @ORM\Table(name="civicrm_custom_group", uniqueConstraints={@ORM\UniqueConstraint(name="UI_title_extends", columns={"title", "extends"}), @ORM\UniqueConstraint(name="UI_name_extends", columns={"name", "extends"})}, indexes={@ORM\Index(name="FK_civicrm_custom_group_created_id", columns={"created_id"})})
 * @ORM\Entity
 */
class CustomGroup extends \Civi\Core\Entity
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
     * @ORM\Column(name="title", type="string", length=64, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="extends", type="string", length=255, nullable=true)
     */
    private $extends = 'Contact';

    /**
     * @var integer
     *
     * @ORM\Column(name="extends_entity_column_id", type="integer", nullable=true)
     */
    private $extendsEntityColumnId;

    /**
     * @var string
     *
     * @ORM\Column(name="extends_entity_column_value", type="string", length=255, nullable=true)
     */
    private $extendsEntityColumnValue;

    /**
     * @var string
     *
     * @ORM\Column(name="style", type="string", nullable=true)
     */
    private $style;

    /**
     * @var integer
     *
     * @ORM\Column(name="collapse_display", type="integer", nullable=true)
     */
    private $collapseDisplay = '0';

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
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="table_name", type="string", length=255, nullable=true)
     */
    private $tableName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_multiple", type="boolean", nullable=true)
     */
    private $isMultiple;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_multiple", type="integer", nullable=true)
     */
    private $minMultiple;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_multiple", type="integer", nullable=true)
     */
    private $maxMultiple;

    /**
     * @var integer
     *
     * @ORM\Column(name="collapse_adv_display", type="integer", nullable=true)
     */
    private $collapseAdvDisplay = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved = '0';

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="created_id", referencedColumnName="id")
     * })
     */
    private $created;



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
     * @return CustomGroup
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
     * @return CustomGroup
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
     * Set extends
     *
     * @param string $extends
     * @return CustomGroup
     */
    public function setExtends($extends)
    {
        $this->extends = $extends;

        return $this;
    }

    /**
     * Get extends
     *
     * @return string 
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * Set extendsEntityColumnId
     *
     * @param integer $extendsEntityColumnId
     * @return CustomGroup
     */
    public function setExtendsEntityColumnId($extendsEntityColumnId)
    {
        $this->extendsEntityColumnId = $extendsEntityColumnId;

        return $this;
    }

    /**
     * Get extendsEntityColumnId
     *
     * @return integer 
     */
    public function getExtendsEntityColumnId()
    {
        return $this->extendsEntityColumnId;
    }

    /**
     * Set extendsEntityColumnValue
     *
     * @param string $extendsEntityColumnValue
     * @return CustomGroup
     */
    public function setExtendsEntityColumnValue($extendsEntityColumnValue)
    {
        $this->extendsEntityColumnValue = $extendsEntityColumnValue;

        return $this;
    }

    /**
     * Get extendsEntityColumnValue
     *
     * @return string 
     */
    public function getExtendsEntityColumnValue()
    {
        return $this->extendsEntityColumnValue;
    }

    /**
     * Set style
     *
     * @param string $style
     * @return CustomGroup
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Get style
     *
     * @return string 
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set collapseDisplay
     *
     * @param integer $collapseDisplay
     * @return CustomGroup
     */
    public function setCollapseDisplay($collapseDisplay)
    {
        $this->collapseDisplay = $collapseDisplay;

        return $this;
    }

    /**
     * Get collapseDisplay
     *
     * @return integer 
     */
    public function getCollapseDisplay()
    {
        return $this->collapseDisplay;
    }

    /**
     * Set helpPre
     *
     * @param string $helpPre
     * @return CustomGroup
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
     * @return CustomGroup
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
     * Set weight
     *
     * @param integer $weight
     * @return CustomGroup
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return CustomGroup
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
     * Set tableName
     *
     * @param string $tableName
     * @return CustomGroup
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Get tableName
     *
     * @return string 
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Set isMultiple
     *
     * @param boolean $isMultiple
     * @return CustomGroup
     */
    public function setIsMultiple($isMultiple)
    {
        $this->isMultiple = $isMultiple;

        return $this;
    }

    /**
     * Get isMultiple
     *
     * @return boolean 
     */
    public function getIsMultiple()
    {
        return $this->isMultiple;
    }

    /**
     * Set minMultiple
     *
     * @param integer $minMultiple
     * @return CustomGroup
     */
    public function setMinMultiple($minMultiple)
    {
        $this->minMultiple = $minMultiple;

        return $this;
    }

    /**
     * Get minMultiple
     *
     * @return integer 
     */
    public function getMinMultiple()
    {
        return $this->minMultiple;
    }

    /**
     * Set maxMultiple
     *
     * @param integer $maxMultiple
     * @return CustomGroup
     */
    public function setMaxMultiple($maxMultiple)
    {
        $this->maxMultiple = $maxMultiple;

        return $this;
    }

    /**
     * Get maxMultiple
     *
     * @return integer 
     */
    public function getMaxMultiple()
    {
        return $this->maxMultiple;
    }

    /**
     * Set collapseAdvDisplay
     *
     * @param integer $collapseAdvDisplay
     * @return CustomGroup
     */
    public function setCollapseAdvDisplay($collapseAdvDisplay)
    {
        $this->collapseAdvDisplay = $collapseAdvDisplay;

        return $this;
    }

    /**
     * Get collapseAdvDisplay
     *
     * @return integer 
     */
    public function getCollapseAdvDisplay()
    {
        return $this->collapseAdvDisplay;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return CustomGroup
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate
     *
     * @return \DateTime 
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return CustomGroup
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
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return CustomGroup
     */
    public function setCreated(\Civi\Contact\Contact $created = null)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \Civi\Contact\Contact 
     */
    public function getCreated()
    {
        return $this->created;
    }
}
