<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * UFGroup
 *
 * @ORM\Table(name="civicrm_uf_group", indexes={@ORM\Index(name="FK_civicrm_uf_group_limit_listings_group_id", columns={"limit_listings_group_id"}), @ORM\Index(name="FK_civicrm_uf_group_add_to_group_id", columns={"add_to_group_id"}), @ORM\Index(name="FK_civicrm_uf_group_created_id", columns={"created_id"})})
 * @ORM\Entity
 */
class UFGroup extends \Civi\Core\Entity
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
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="group_type", type="string", length=255, nullable=true)
     */
    private $groupType;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=64, nullable=false)
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
     * @ORM\Column(name="post_URL", type="string", length=255, nullable=true)
     */
    private $postUrl;

    /**
     * @var boolean
     *
     * @ORM\Column(name="add_captcha", type="boolean", nullable=true)
     */
    private $addCaptcha = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_map", type="boolean", nullable=true)
     */
    private $isMap = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_edit_link", type="boolean", nullable=true)
     */
    private $isEditLink = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_uf_link", type="boolean", nullable=true)
     */
    private $isUfLink = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_update_dupe", type="boolean", nullable=true)
     */
    private $isUpdateDupe = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="cancel_URL", type="string", length=255, nullable=true)
     */
    private $cancelUrl;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_cms_user", type="boolean", nullable=true)
     */
    private $isCmsUser = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="notify", type="text", nullable=true)
     */
    private $notify;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_proximity_search", type="boolean", nullable=true)
     */
    private $isProximitySearch = '0';

    /**
     * @var \Civi\Contact\Group
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Group")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="limit_listings_group_id", referencedColumnName="id")
     * })
     */
    private $limitListingsGroup;

    /**
     * @var \Civi\Contact\Group
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Group")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="add_to_group_id", referencedColumnName="id")
     * })
     */
    private $addToGroup;

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
     * Set isActive
     *
     * @param boolean $isActive
     * @return UFGroup
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
     * Set groupType
     *
     * @param string $groupType
     * @return UFGroup
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
     * Set title
     *
     * @param string $title
     * @return UFGroup
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
     * @return UFGroup
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
     * Set helpPre
     *
     * @param string $helpPre
     * @return UFGroup
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
     * @return UFGroup
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
     * Set postUrl
     *
     * @param string $postUrl
     * @return UFGroup
     */
    public function setPostUrl($postUrl)
    {
        $this->postUrl = $postUrl;

        return $this;
    }

    /**
     * Get postUrl
     *
     * @return string 
     */
    public function getPostUrl()
    {
        return $this->postUrl;
    }

    /**
     * Set addCaptcha
     *
     * @param boolean $addCaptcha
     * @return UFGroup
     */
    public function setAddCaptcha($addCaptcha)
    {
        $this->addCaptcha = $addCaptcha;

        return $this;
    }

    /**
     * Get addCaptcha
     *
     * @return boolean 
     */
    public function getAddCaptcha()
    {
        return $this->addCaptcha;
    }

    /**
     * Set isMap
     *
     * @param boolean $isMap
     * @return UFGroup
     */
    public function setIsMap($isMap)
    {
        $this->isMap = $isMap;

        return $this;
    }

    /**
     * Get isMap
     *
     * @return boolean 
     */
    public function getIsMap()
    {
        return $this->isMap;
    }

    /**
     * Set isEditLink
     *
     * @param boolean $isEditLink
     * @return UFGroup
     */
    public function setIsEditLink($isEditLink)
    {
        $this->isEditLink = $isEditLink;

        return $this;
    }

    /**
     * Get isEditLink
     *
     * @return boolean 
     */
    public function getIsEditLink()
    {
        return $this->isEditLink;
    }

    /**
     * Set isUfLink
     *
     * @param boolean $isUfLink
     * @return UFGroup
     */
    public function setIsUfLink($isUfLink)
    {
        $this->isUfLink = $isUfLink;

        return $this;
    }

    /**
     * Get isUfLink
     *
     * @return boolean 
     */
    public function getIsUfLink()
    {
        return $this->isUfLink;
    }

    /**
     * Set isUpdateDupe
     *
     * @param boolean $isUpdateDupe
     * @return UFGroup
     */
    public function setIsUpdateDupe($isUpdateDupe)
    {
        $this->isUpdateDupe = $isUpdateDupe;

        return $this;
    }

    /**
     * Get isUpdateDupe
     *
     * @return boolean 
     */
    public function getIsUpdateDupe()
    {
        return $this->isUpdateDupe;
    }

    /**
     * Set cancelUrl
     *
     * @param string $cancelUrl
     * @return UFGroup
     */
    public function setCancelUrl($cancelUrl)
    {
        $this->cancelUrl = $cancelUrl;

        return $this;
    }

    /**
     * Get cancelUrl
     *
     * @return string 
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * Set isCmsUser
     *
     * @param boolean $isCmsUser
     * @return UFGroup
     */
    public function setIsCmsUser($isCmsUser)
    {
        $this->isCmsUser = $isCmsUser;

        return $this;
    }

    /**
     * Get isCmsUser
     *
     * @return boolean 
     */
    public function getIsCmsUser()
    {
        return $this->isCmsUser;
    }

    /**
     * Set notify
     *
     * @param string $notify
     * @return UFGroup
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;

        return $this;
    }

    /**
     * Get notify
     *
     * @return string 
     */
    public function getNotify()
    {
        return $this->notify;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return UFGroup
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
     * Set name
     *
     * @param string $name
     * @return UFGroup
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
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return UFGroup
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
     * Set isProximitySearch
     *
     * @param boolean $isProximitySearch
     * @return UFGroup
     */
    public function setIsProximitySearch($isProximitySearch)
    {
        $this->isProximitySearch = $isProximitySearch;

        return $this;
    }

    /**
     * Get isProximitySearch
     *
     * @return boolean 
     */
    public function getIsProximitySearch()
    {
        return $this->isProximitySearch;
    }

    /**
     * Set limitListingsGroup
     *
     * @param \Civi\Contact\Group $limitListingsGroup
     * @return UFGroup
     */
    public function setLimitListingsGroup(\Civi\Contact\Group $limitListingsGroup = null)
    {
        $this->limitListingsGroup = $limitListingsGroup;

        return $this;
    }

    /**
     * Get limitListingsGroup
     *
     * @return \Civi\Contact\Group 
     */
    public function getLimitListingsGroup()
    {
        return $this->limitListingsGroup;
    }

    /**
     * Set addToGroup
     *
     * @param \Civi\Contact\Group $addToGroup
     * @return UFGroup
     */
    public function setAddToGroup(\Civi\Contact\Group $addToGroup = null)
    {
        $this->addToGroup = $addToGroup;

        return $this;
    }

    /**
     * Get addToGroup
     *
     * @return \Civi\Contact\Group 
     */
    public function getAddToGroup()
    {
        return $this->addToGroup;
    }

    /**
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return UFGroup
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
