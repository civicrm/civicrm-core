<?php

namespace Civi\Campaign;

use Doctrine\ORM\Mapping as ORM;

/**
 * Survey
 *
 * @ORM\Table(name="civicrm_survey", indexes={@ORM\Index(name="UI_activity_type_id", columns={"activity_type_id"}), @ORM\Index(name="FK_civicrm_survey_campaign_id", columns={"campaign_id"}), @ORM\Index(name="FK_civicrm_survey_created_id", columns={"created_id"}), @ORM\Index(name="FK_civicrm_survey_last_modified_id", columns={"last_modified_id"})})
 * @ORM\Entity
 */
class Survey extends \Civi\Core\Entity
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
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_type_id", type="integer", nullable=true)
     */
    private $activityTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="recontact_interval", type="text", nullable=true)
     */
    private $recontactInterval;

    /**
     * @var string
     *
     * @ORM\Column(name="instructions", type="text", nullable=true)
     */
    private $instructions;

    /**
     * @var integer
     *
     * @ORM\Column(name="release_frequency", type="integer", nullable=true)
     */
    private $releaseFrequency;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_number_of_contacts", type="integer", nullable=true)
     */
    private $maxNumberOfContacts;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_number_of_contacts", type="integer", nullable=true)
     */
    private $defaultNumberOfContacts;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault = '0';

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
     * @var integer
     *
     * @ORM\Column(name="result_id", type="integer", nullable=true)
     */
    private $resultId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="bypass_confirm", type="boolean", nullable=true)
     */
    private $bypassConfirm = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="thankyou_title", type="string", length=255, nullable=true)
     */
    private $thankyouTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="thankyou_text", type="text", nullable=true)
     */
    private $thankyouText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_share", type="boolean", nullable=true)
     */
    private $isShare = '1';

    /**
     * @var \Civi\Campaign\Campaign
     *
     * @ORM\ManyToOne(targetEntity="Civi\Campaign\Campaign")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     * })
     */
    private $campaign;

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
     * Set title
     *
     * @param string $title
     * @return Survey
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
     * Set activityTypeId
     *
     * @param integer $activityTypeId
     * @return Survey
     */
    public function setActivityTypeId($activityTypeId)
    {
        $this->activityTypeId = $activityTypeId;

        return $this;
    }

    /**
     * Get activityTypeId
     *
     * @return integer 
     */
    public function getActivityTypeId()
    {
        return $this->activityTypeId;
    }

    /**
     * Set recontactInterval
     *
     * @param string $recontactInterval
     * @return Survey
     */
    public function setRecontactInterval($recontactInterval)
    {
        $this->recontactInterval = $recontactInterval;

        return $this;
    }

    /**
     * Get recontactInterval
     *
     * @return string 
     */
    public function getRecontactInterval()
    {
        return $this->recontactInterval;
    }

    /**
     * Set instructions
     *
     * @param string $instructions
     * @return Survey
     */
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * Get instructions
     *
     * @return string 
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * Set releaseFrequency
     *
     * @param integer $releaseFrequency
     * @return Survey
     */
    public function setReleaseFrequency($releaseFrequency)
    {
        $this->releaseFrequency = $releaseFrequency;

        return $this;
    }

    /**
     * Get releaseFrequency
     *
     * @return integer 
     */
    public function getReleaseFrequency()
    {
        return $this->releaseFrequency;
    }

    /**
     * Set maxNumberOfContacts
     *
     * @param integer $maxNumberOfContacts
     * @return Survey
     */
    public function setMaxNumberOfContacts($maxNumberOfContacts)
    {
        $this->maxNumberOfContacts = $maxNumberOfContacts;

        return $this;
    }

    /**
     * Get maxNumberOfContacts
     *
     * @return integer 
     */
    public function getMaxNumberOfContacts()
    {
        return $this->maxNumberOfContacts;
    }

    /**
     * Set defaultNumberOfContacts
     *
     * @param integer $defaultNumberOfContacts
     * @return Survey
     */
    public function setDefaultNumberOfContacts($defaultNumberOfContacts)
    {
        $this->defaultNumberOfContacts = $defaultNumberOfContacts;

        return $this;
    }

    /**
     * Get defaultNumberOfContacts
     *
     * @return integer 
     */
    public function getDefaultNumberOfContacts()
    {
        return $this->defaultNumberOfContacts;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Survey
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
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return Survey
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
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return Survey
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
     * @return Survey
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
     * Set resultId
     *
     * @param integer $resultId
     * @return Survey
     */
    public function setResultId($resultId)
    {
        $this->resultId = $resultId;

        return $this;
    }

    /**
     * Get resultId
     *
     * @return integer 
     */
    public function getResultId()
    {
        return $this->resultId;
    }

    /**
     * Set bypassConfirm
     *
     * @param boolean $bypassConfirm
     * @return Survey
     */
    public function setBypassConfirm($bypassConfirm)
    {
        $this->bypassConfirm = $bypassConfirm;

        return $this;
    }

    /**
     * Get bypassConfirm
     *
     * @return boolean 
     */
    public function getBypassConfirm()
    {
        return $this->bypassConfirm;
    }

    /**
     * Set thankyouTitle
     *
     * @param string $thankyouTitle
     * @return Survey
     */
    public function setThankyouTitle($thankyouTitle)
    {
        $this->thankyouTitle = $thankyouTitle;

        return $this;
    }

    /**
     * Get thankyouTitle
     *
     * @return string 
     */
    public function getThankyouTitle()
    {
        return $this->thankyouTitle;
    }

    /**
     * Set thankyouText
     *
     * @param string $thankyouText
     * @return Survey
     */
    public function setThankyouText($thankyouText)
    {
        $this->thankyouText = $thankyouText;

        return $this;
    }

    /**
     * Get thankyouText
     *
     * @return string 
     */
    public function getThankyouText()
    {
        return $this->thankyouText;
    }

    /**
     * Set isShare
     *
     * @param boolean $isShare
     * @return Survey
     */
    public function setIsShare($isShare)
    {
        $this->isShare = $isShare;

        return $this;
    }

    /**
     * Get isShare
     *
     * @return boolean 
     */
    public function getIsShare()
    {
        return $this->isShare;
    }

    /**
     * Set campaign
     *
     * @param \Civi\Campaign\Campaign $campaign
     * @return Survey
     */
    public function setCampaign(\Civi\Campaign\Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \Civi\Campaign\Campaign 
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return Survey
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
     * @return Survey
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
