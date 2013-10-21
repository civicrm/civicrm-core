<?php

namespace Civi\Campaign;

use Doctrine\ORM\Mapping as ORM;

/**
 * Campaign
 *
 * @ORM\Table(name="civicrm_campaign", uniqueConstraints={@ORM\UniqueConstraint(name="UI_external_identifier", columns={"external_identifier"})}, indexes={@ORM\Index(name="UI_campaign_type_id", columns={"campaign_type_id"}), @ORM\Index(name="UI_campaign_status_id", columns={"status_id"}), @ORM\Index(name="FK_civicrm_campaign_parent_id", columns={"parent_id"}), @ORM\Index(name="FK_civicrm_campaign_created_id", columns={"created_id"}), @ORM\Index(name="FK_civicrm_campaign_last_modified_id", columns={"last_modified_id"})})
 * @ORM\Entity
 */
class Campaign extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="campaign_type_id", type="integer", nullable=true)
     */
    private $campaignTypeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=true)
     */
    private $statusId;

    /**
     * @var string
     *
     * @ORM\Column(name="external_identifier", type="string", length=32, nullable=true)
     */
    private $externalIdentifier;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_modified_date", type="datetime", nullable=true)
     */
    private $lastModifiedDate;

    /**
     * @var string
     *
     * @ORM\Column(name="goal_general", type="text", nullable=true)
     */
    private $goalGeneral;

    /**
     * @var string
     *
     * @ORM\Column(name="goal_revenue", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $goalRevenue;

    /**
     * @var \Civi\Campaign\Campaign
     *
     * @ORM\ManyToOne(targetEntity="Civi\Campaign\Campaign")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

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
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="last_modified_id", referencedColumnName="id")
     * })
     */
    private $lastModified;



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
     * @return Campaign
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
     * @return Campaign
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
     * @return Campaign
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Campaign
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Campaign
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set campaignTypeId
     *
     * @param integer $campaignTypeId
     * @return Campaign
     */
    public function setCampaignTypeId($campaignTypeId)
    {
        $this->campaignTypeId = $campaignTypeId;

        return $this;
    }

    /**
     * Get campaignTypeId
     *
     * @return integer 
     */
    public function getCampaignTypeId()
    {
        return $this->campaignTypeId;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return Campaign
     */
    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;

        return $this;
    }

    /**
     * Get statusId
     *
     * @return integer 
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * Set externalIdentifier
     *
     * @param string $externalIdentifier
     * @return Campaign
     */
    public function setExternalIdentifier($externalIdentifier)
    {
        $this->externalIdentifier = $externalIdentifier;

        return $this;
    }

    /**
     * Get externalIdentifier
     *
     * @return string 
     */
    public function getExternalIdentifier()
    {
        return $this->externalIdentifier;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Campaign
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
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return Campaign
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
     * Set lastModifiedDate
     *
     * @param \DateTime $lastModifiedDate
     * @return Campaign
     */
    public function setLastModifiedDate($lastModifiedDate)
    {
        $this->lastModifiedDate = $lastModifiedDate;

        return $this;
    }

    /**
     * Get lastModifiedDate
     *
     * @return \DateTime 
     */
    public function getLastModifiedDate()
    {
        return $this->lastModifiedDate;
    }

    /**
     * Set goalGeneral
     *
     * @param string $goalGeneral
     * @return Campaign
     */
    public function setGoalGeneral($goalGeneral)
    {
        $this->goalGeneral = $goalGeneral;

        return $this;
    }

    /**
     * Get goalGeneral
     *
     * @return string 
     */
    public function getGoalGeneral()
    {
        return $this->goalGeneral;
    }

    /**
     * Set goalRevenue
     *
     * @param string $goalRevenue
     * @return Campaign
     */
    public function setGoalRevenue($goalRevenue)
    {
        $this->goalRevenue = $goalRevenue;

        return $this;
    }

    /**
     * Get goalRevenue
     *
     * @return string 
     */
    public function getGoalRevenue()
    {
        return $this->goalRevenue;
    }

    /**
     * Set parent
     *
     * @param \Civi\Campaign\Campaign $parent
     * @return Campaign
     */
    public function setParent(\Civi\Campaign\Campaign $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Civi\Campaign\Campaign 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return Campaign
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

    /**
     * Set lastModified
     *
     * @param \Civi\Contact\Contact $lastModified
     * @return Campaign
     */
    public function setLastModified(\Civi\Contact\Contact $lastModified = null)
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * Get lastModified
     *
     * @return \Civi\Contact\Contact 
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }
}
