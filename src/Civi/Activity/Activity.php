<?php

namespace Civi\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity
 *
 * @ORM\Table(name="civicrm_activity", indexes={@ORM\Index(name="UI_source_record_id", columns={"source_record_id"}), @ORM\Index(name="UI_activity_type_id", columns={"activity_type_id"}), @ORM\Index(name="index_medium_id", columns={"medium_id"}), @ORM\Index(name="index_is_current_revision", columns={"is_current_revision"}), @ORM\Index(name="index_is_deleted", columns={"is_deleted"}), @ORM\Index(name="FK_civicrm_activity_phone_id", columns={"phone_id"}), @ORM\Index(name="FK_civicrm_activity_parent_id", columns={"parent_id"}), @ORM\Index(name="FK_civicrm_activity_relationship_id", columns={"relationship_id"}), @ORM\Index(name="FK_civicrm_activity_original_id", columns={"original_id"}), @ORM\Index(name="FK_civicrm_activity_campaign_id", columns={"campaign_id"})})
 * @ORM\Entity
 */
class Activity extends \Civi\Core\Entity
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
     * @var integer
     *
     * @ORM\Column(name="source_record_id", type="integer", nullable=true)
     */
    private $sourceRecordId;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_type_id", type="integer", nullable=false)
     */
    private $activityTypeId = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="activity_date_time", type="datetime", nullable=true)
     */
    private $activityDateTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=64, nullable=true)
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="details", type="text", nullable=true)
     */
    private $details;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=true)
     */
    private $statusId;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority_id", type="integer", nullable=true)
     */
    private $priorityId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_test", type="boolean", nullable=true)
     */
    private $isTest = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="medium_id", type="integer", nullable=true)
     */
    private $mediumId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_auto", type="boolean", nullable=true)
     */
    private $isAuto = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_current_revision", type="boolean", nullable=true)
     */
    private $isCurrentRevision = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="result", type="string", length=255, nullable=true)
     */
    private $result;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=true)
     */
    private $isDeleted = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="engagement_level", type="integer", nullable=true)
     */
    private $engagementLevel;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=true)
     */
    private $weight;

    /**
     * @var \Civi\Core\Phone
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Phone")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="phone_id", referencedColumnName="id")
     * })
     */
    private $phone;

    /**
     * @var \Civi\Activity\Activity
     *
     * @ORM\ManyToOne(targetEntity="Civi\Activity\Activity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * @var \Civi\Contact\Relationship
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Relationship")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="relationship_id", referencedColumnName="id")
     * })
     */
    private $relationship;

    /**
     * @var \Civi\Activity\Activity
     *
     * @ORM\ManyToOne(targetEntity="Civi\Activity\Activity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="original_id", referencedColumnName="id")
     * })
     */
    private $original;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sourceRecordId
     *
     * @param integer $sourceRecordId
     * @return Activity
     */
    public function setSourceRecordId($sourceRecordId)
    {
        $this->sourceRecordId = $sourceRecordId;

        return $this;
    }

    /**
     * Get sourceRecordId
     *
     * @return integer 
     */
    public function getSourceRecordId()
    {
        return $this->sourceRecordId;
    }

    /**
     * Set activityTypeId
     *
     * @param integer $activityTypeId
     * @return Activity
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
     * Set subject
     *
     * @param string $subject
     * @return Activity
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set activityDateTime
     *
     * @param \DateTime $activityDateTime
     * @return Activity
     */
    public function setActivityDateTime($activityDateTime)
    {
        $this->activityDateTime = $activityDateTime;

        return $this;
    }

    /**
     * Get activityDateTime
     *
     * @return \DateTime 
     */
    public function getActivityDateTime()
    {
        return $this->activityDateTime;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return Activity
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer 
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set location
     *
     * @param string $location
     * @return Activity
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string 
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set phoneNumber
     *
     * @param string $phoneNumber
     * @return Activity
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber
     *
     * @return string 
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * Set details
     *
     * @param string $details
     * @return Activity
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details
     *
     * @return string 
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return Activity
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
     * Set priorityId
     *
     * @param integer $priorityId
     * @return Activity
     */
    public function setPriorityId($priorityId)
    {
        $this->priorityId = $priorityId;

        return $this;
    }

    /**
     * Get priorityId
     *
     * @return integer 
     */
    public function getPriorityId()
    {
        return $this->priorityId;
    }

    /**
     * Set isTest
     *
     * @param boolean $isTest
     * @return Activity
     */
    public function setIsTest($isTest)
    {
        $this->isTest = $isTest;

        return $this;
    }

    /**
     * Get isTest
     *
     * @return boolean 
     */
    public function getIsTest()
    {
        return $this->isTest;
    }

    /**
     * Set mediumId
     *
     * @param integer $mediumId
     * @return Activity
     */
    public function setMediumId($mediumId)
    {
        $this->mediumId = $mediumId;

        return $this;
    }

    /**
     * Get mediumId
     *
     * @return integer 
     */
    public function getMediumId()
    {
        return $this->mediumId;
    }

    /**
     * Set isAuto
     *
     * @param boolean $isAuto
     * @return Activity
     */
    public function setIsAuto($isAuto)
    {
        $this->isAuto = $isAuto;

        return $this;
    }

    /**
     * Get isAuto
     *
     * @return boolean 
     */
    public function getIsAuto()
    {
        return $this->isAuto;
    }

    /**
     * Set isCurrentRevision
     *
     * @param boolean $isCurrentRevision
     * @return Activity
     */
    public function setIsCurrentRevision($isCurrentRevision)
    {
        $this->isCurrentRevision = $isCurrentRevision;

        return $this;
    }

    /**
     * Get isCurrentRevision
     *
     * @return boolean 
     */
    public function getIsCurrentRevision()
    {
        return $this->isCurrentRevision;
    }

    /**
     * Set result
     *
     * @param string $result
     * @return Activity
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result
     *
     * @return string 
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return Activity
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * Set engagementLevel
     *
     * @param integer $engagementLevel
     * @return Activity
     */
    public function setEngagementLevel($engagementLevel)
    {
        $this->engagementLevel = $engagementLevel;

        return $this;
    }

    /**
     * Get engagementLevel
     *
     * @return integer 
     */
    public function getEngagementLevel()
    {
        return $this->engagementLevel;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return Activity
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
     * Set phone
     *
     * @param \Civi\Core\Phone $phone
     * @return Activity
     */
    public function setPhone(\Civi\Core\Phone $phone = null)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return \Civi\Core\Phone 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set parent
     *
     * @param \Civi\Activity\Activity $parent
     * @return Activity
     */
    public function setParent(\Civi\Activity\Activity $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Civi\Activity\Activity 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set relationship
     *
     * @param \Civi\Contact\Relationship $relationship
     * @return Activity
     */
    public function setRelationship(\Civi\Contact\Relationship $relationship = null)
    {
        $this->relationship = $relationship;

        return $this;
    }

    /**
     * Get relationship
     *
     * @return \Civi\Contact\Relationship 
     */
    public function getRelationship()
    {
        return $this->relationship;
    }

    /**
     * Set original
     *
     * @param \Civi\Activity\Activity $original
     * @return Activity
     */
    public function setOriginal(\Civi\Activity\Activity $original = null)
    {
        $this->original = $original;

        return $this;
    }

    /**
     * Get original
     *
     * @return \Civi\Activity\Activity 
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * Set campaign
     *
     * @param \Civi\Campaign\Campaign $campaign
     * @return Activity
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
}
