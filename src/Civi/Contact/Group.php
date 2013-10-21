<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * Group
 *
 * @ORM\Table(name="civicrm_group", uniqueConstraints={@ORM\UniqueConstraint(name="UI_title", columns={"title"}), @ORM\UniqueConstraint(name="UI_name", columns={"name"})}, indexes={@ORM\Index(name="index_group_type", columns={"group_type"}), @ORM\Index(name="FK_civicrm_group_saved_search_id", columns={"saved_search_id"}), @ORM\Index(name="FK_civicrm_group_created_id", columns={"created_id"})})
 * @ORM\Entity
 */
class Group extends \Civi\Core\Entity
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
     * @ORM\Column(name="title", type="string", length=64, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=64, nullable=true)
     */
    private $source;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", nullable=true)
     */
    private $visibility = 'User and User Admin Only';

    /**
     * @var string
     *
     * @ORM\Column(name="where_clause", type="text", nullable=true)
     */
    private $whereClause;

    /**
     * @var string
     *
     * @ORM\Column(name="select_tables", type="text", nullable=true)
     */
    private $selectTables;

    /**
     * @var string
     *
     * @ORM\Column(name="where_tables", type="text", nullable=true)
     */
    private $whereTables;

    /**
     * @var string
     *
     * @ORM\Column(name="group_type", type="string", length=128, nullable=true)
     */
    private $groupType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cache_date", type="datetime", nullable=true)
     */
    private $cacheDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="refresh_date", type="datetime", nullable=true)
     */
    private $refreshDate;

    /**
     * @var string
     *
     * @ORM\Column(name="parents", type="text", nullable=true)
     */
    private $parents;

    /**
     * @var string
     *
     * @ORM\Column(name="children", type="text", nullable=true)
     */
    private $children;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_hidden", type="boolean", nullable=true)
     */
    private $isHidden = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved = '0';

    /**
     * @var \Civi\Contact\SavedSearch
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\SavedSearch")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="saved_search_id", referencedColumnName="id")
     * })
     */
    private $savedSearch;

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
     * @return Group
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
     * @return Group
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
     * Set description
     *
     * @param string $description
     * @return Group
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
     * Set source
     *
     * @param string $source
     * @return Group
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Group
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
     * Set visibility
     *
     * @param string $visibility
     * @return Group
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
     * Set whereClause
     *
     * @param string $whereClause
     * @return Group
     */
    public function setWhereClause($whereClause)
    {
        $this->whereClause = $whereClause;

        return $this;
    }

    /**
     * Get whereClause
     *
     * @return string 
     */
    public function getWhereClause()
    {
        return $this->whereClause;
    }

    /**
     * Set selectTables
     *
     * @param string $selectTables
     * @return Group
     */
    public function setSelectTables($selectTables)
    {
        $this->selectTables = $selectTables;

        return $this;
    }

    /**
     * Get selectTables
     *
     * @return string 
     */
    public function getSelectTables()
    {
        return $this->selectTables;
    }

    /**
     * Set whereTables
     *
     * @param string $whereTables
     * @return Group
     */
    public function setWhereTables($whereTables)
    {
        $this->whereTables = $whereTables;

        return $this;
    }

    /**
     * Get whereTables
     *
     * @return string 
     */
    public function getWhereTables()
    {
        return $this->whereTables;
    }

    /**
     * Set groupType
     *
     * @param string $groupType
     * @return Group
     */
    public function setGroupType($groupType)
    {
        $this->groupType = $groupType;

        return $this;
    }

    /**
     * Get groupType
     *
     * @return string 
     */
    public function getGroupType()
    {
        return $this->groupType;
    }

    /**
     * Set cacheDate
     *
     * @param \DateTime $cacheDate
     * @return Group
     */
    public function setCacheDate($cacheDate)
    {
        $this->cacheDate = $cacheDate;

        return $this;
    }

    /**
     * Get cacheDate
     *
     * @return \DateTime 
     */
    public function getCacheDate()
    {
        return $this->cacheDate;
    }

    /**
     * Set refreshDate
     *
     * @param \DateTime $refreshDate
     * @return Group
     */
    public function setRefreshDate($refreshDate)
    {
        $this->refreshDate = $refreshDate;

        return $this;
    }

    /**
     * Get refreshDate
     *
     * @return \DateTime 
     */
    public function getRefreshDate()
    {
        return $this->refreshDate;
    }

    /**
     * Set parents
     *
     * @param string $parents
     * @return Group
     */
    public function setParents($parents)
    {
        $this->parents = $parents;

        return $this;
    }

    /**
     * Get parents
     *
     * @return string 
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * Set children
     *
     * @param string $children
     * @return Group
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Get children
     *
     * @return string 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set isHidden
     *
     * @param boolean $isHidden
     * @return Group
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden
     *
     * @return boolean 
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return Group
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
     * Set savedSearch
     *
     * @param \Civi\Contact\SavedSearch $savedSearch
     * @return Group
     */
    public function setSavedSearch(\Civi\Contact\SavedSearch $savedSearch = null)
    {
        $this->savedSearch = $savedSearch;

        return $this;
    }

    /**
     * Get savedSearch
     *
     * @return \Civi\Contact\SavedSearch 
     */
    public function getSavedSearch()
    {
        return $this->savedSearch;
    }

    /**
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return Group
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
