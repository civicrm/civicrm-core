<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActionSchedule
 *
 * @ORM\Table(name="civicrm_action_schedule", indexes={@ORM\Index(name="FK_civicrm_action_schedule_mapping_id", columns={"mapping_id"}), @ORM\Index(name="FK_civicrm_action_schedule_group_id", columns={"group_id"}), @ORM\Index(name="FK_civicrm_action_schedule_msg_template_id", columns={"msg_template_id"})})
 * @ORM\Entity
 */
class ActionSchedule extends \Civi\Core\Entity
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
     * @ORM\Column(name="recipient", type="string", length=64, nullable=true)
     */
    private $recipient;

    /**
     * @var boolean
     *
     * @ORM\Column(name="limit_to", type="boolean", nullable=true)
     */
    private $limitTo = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="entity_value", type="string", length=64, nullable=true)
     */
    private $entityValue;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_status", type="string", length=64, nullable=true)
     */
    private $entityStatus;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_action_offset", type="integer", nullable=true)
     */
    private $startActionOffset;

    /**
     * @var string
     *
     * @ORM\Column(name="start_action_unit", type="string", nullable=true)
     */
    private $startActionUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="start_action_condition", type="string", length=32, nullable=true)
     */
    private $startActionCondition;

    /**
     * @var string
     *
     * @ORM\Column(name="start_action_date", type="string", length=64, nullable=true)
     */
    private $startActionDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_repeat", type="boolean", nullable=true)
     */
    private $isRepeat = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="repetition_frequency_unit", type="string", nullable=true)
     */
    private $repetitionFrequencyUnit;

    /**
     * @var integer
     *
     * @ORM\Column(name="repetition_frequency_interval", type="integer", nullable=true)
     */
    private $repetitionFrequencyInterval;

    /**
     * @var string
     *
     * @ORM\Column(name="end_frequency_unit", type="string", nullable=true)
     */
    private $endFrequencyUnit;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_frequency_interval", type="integer", nullable=true)
     */
    private $endFrequencyInterval;

    /**
     * @var string
     *
     * @ORM\Column(name="end_action", type="string", length=32, nullable=true)
     */
    private $endAction;

    /**
     * @var string
     *
     * @ORM\Column(name="end_date", type="string", length=64, nullable=true)
     */
    private $endDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_manual", type="string", length=128, nullable=true)
     */
    private $recipientManual;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_listing", type="string", length=128, nullable=true)
     */
    private $recipientListing;

    /**
     * @var string
     *
     * @ORM\Column(name="body_text", type="text", nullable=true)
     */
    private $bodyText;

    /**
     * @var string
     *
     * @ORM\Column(name="body_html", type="text", nullable=true)
     */
    private $bodyHtml;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=128, nullable=true)
     */
    private $subject;

    /**
     * @var boolean
     *
     * @ORM\Column(name="record_activity", type="boolean", nullable=true)
     */
    private $recordActivity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="absolute_date", type="date", nullable=true)
     */
    private $absoluteDate;

    /**
     * @var \Civi\Core\ActionMapping
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\ActionMapping")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mapping_id", referencedColumnName="id")
     * })
     */
    private $mapping;

    /**
     * @var \Civi\Contact\Group
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Group")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     * })
     */
    private $group;

    /**
     * @var \Civi\Core\MessageTemplate
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\MessageTemplate")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="msg_template_id", referencedColumnName="id")
     * })
     */
    private $msgTemplate;



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
     * @return ActionSchedule
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
     * @return ActionSchedule
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
     * Set recipient
     *
     * @param string $recipient
     * @return ActionSchedule
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Get recipient
     *
     * @return string 
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Set limitTo
     *
     * @param boolean $limitTo
     * @return ActionSchedule
     */
    public function setLimitTo($limitTo)
    {
        $this->limitTo = $limitTo;

        return $this;
    }

    /**
     * Get limitTo
     *
     * @return boolean 
     */
    public function getLimitTo()
    {
        return $this->limitTo;
    }

    /**
     * Set entityValue
     *
     * @param string $entityValue
     * @return ActionSchedule
     */
    public function setEntityValue($entityValue)
    {
        $this->entityValue = $entityValue;

        return $this;
    }

    /**
     * Get entityValue
     *
     * @return string 
     */
    public function getEntityValue()
    {
        return $this->entityValue;
    }

    /**
     * Set entityStatus
     *
     * @param string $entityStatus
     * @return ActionSchedule
     */
    public function setEntityStatus($entityStatus)
    {
        $this->entityStatus = $entityStatus;

        return $this;
    }

    /**
     * Get entityStatus
     *
     * @return string 
     */
    public function getEntityStatus()
    {
        return $this->entityStatus;
    }

    /**
     * Set startActionOffset
     *
     * @param integer $startActionOffset
     * @return ActionSchedule
     */
    public function setStartActionOffset($startActionOffset)
    {
        $this->startActionOffset = $startActionOffset;

        return $this;
    }

    /**
     * Get startActionOffset
     *
     * @return integer 
     */
    public function getStartActionOffset()
    {
        return $this->startActionOffset;
    }

    /**
     * Set startActionUnit
     *
     * @param string $startActionUnit
     * @return ActionSchedule
     */
    public function setStartActionUnit($startActionUnit)
    {
        $this->startActionUnit = $startActionUnit;

        return $this;
    }

    /**
     * Get startActionUnit
     *
     * @return string 
     */
    public function getStartActionUnit()
    {
        return $this->startActionUnit;
    }

    /**
     * Set startActionCondition
     *
     * @param string $startActionCondition
     * @return ActionSchedule
     */
    public function setStartActionCondition($startActionCondition)
    {
        $this->startActionCondition = $startActionCondition;

        return $this;
    }

    /**
     * Get startActionCondition
     *
     * @return string 
     */
    public function getStartActionCondition()
    {
        return $this->startActionCondition;
    }

    /**
     * Set startActionDate
     *
     * @param string $startActionDate
     * @return ActionSchedule
     */
    public function setStartActionDate($startActionDate)
    {
        $this->startActionDate = $startActionDate;

        return $this;
    }

    /**
     * Get startActionDate
     *
     * @return string 
     */
    public function getStartActionDate()
    {
        return $this->startActionDate;
    }

    /**
     * Set isRepeat
     *
     * @param boolean $isRepeat
     * @return ActionSchedule
     */
    public function setIsRepeat($isRepeat)
    {
        $this->isRepeat = $isRepeat;

        return $this;
    }

    /**
     * Get isRepeat
     *
     * @return boolean 
     */
    public function getIsRepeat()
    {
        return $this->isRepeat;
    }

    /**
     * Set repetitionFrequencyUnit
     *
     * @param string $repetitionFrequencyUnit
     * @return ActionSchedule
     */
    public function setRepetitionFrequencyUnit($repetitionFrequencyUnit)
    {
        $this->repetitionFrequencyUnit = $repetitionFrequencyUnit;

        return $this;
    }

    /**
     * Get repetitionFrequencyUnit
     *
     * @return string 
     */
    public function getRepetitionFrequencyUnit()
    {
        return $this->repetitionFrequencyUnit;
    }

    /**
     * Set repetitionFrequencyInterval
     *
     * @param integer $repetitionFrequencyInterval
     * @return ActionSchedule
     */
    public function setRepetitionFrequencyInterval($repetitionFrequencyInterval)
    {
        $this->repetitionFrequencyInterval = $repetitionFrequencyInterval;

        return $this;
    }

    /**
     * Get repetitionFrequencyInterval
     *
     * @return integer 
     */
    public function getRepetitionFrequencyInterval()
    {
        return $this->repetitionFrequencyInterval;
    }

    /**
     * Set endFrequencyUnit
     *
     * @param string $endFrequencyUnit
     * @return ActionSchedule
     */
    public function setEndFrequencyUnit($endFrequencyUnit)
    {
        $this->endFrequencyUnit = $endFrequencyUnit;

        return $this;
    }

    /**
     * Get endFrequencyUnit
     *
     * @return string 
     */
    public function getEndFrequencyUnit()
    {
        return $this->endFrequencyUnit;
    }

    /**
     * Set endFrequencyInterval
     *
     * @param integer $endFrequencyInterval
     * @return ActionSchedule
     */
    public function setEndFrequencyInterval($endFrequencyInterval)
    {
        $this->endFrequencyInterval = $endFrequencyInterval;

        return $this;
    }

    /**
     * Get endFrequencyInterval
     *
     * @return integer 
     */
    public function getEndFrequencyInterval()
    {
        return $this->endFrequencyInterval;
    }

    /**
     * Set endAction
     *
     * @param string $endAction
     * @return ActionSchedule
     */
    public function setEndAction($endAction)
    {
        $this->endAction = $endAction;

        return $this;
    }

    /**
     * Get endAction
     *
     * @return string 
     */
    public function getEndAction()
    {
        return $this->endAction;
    }

    /**
     * Set endDate
     *
     * @param string $endDate
     * @return ActionSchedule
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return string 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return ActionSchedule
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
     * Set recipientManual
     *
     * @param string $recipientManual
     * @return ActionSchedule
     */
    public function setRecipientManual($recipientManual)
    {
        $this->recipientManual = $recipientManual;

        return $this;
    }

    /**
     * Get recipientManual
     *
     * @return string 
     */
    public function getRecipientManual()
    {
        return $this->recipientManual;
    }

    /**
     * Set recipientListing
     *
     * @param string $recipientListing
     * @return ActionSchedule
     */
    public function setRecipientListing($recipientListing)
    {
        $this->recipientListing = $recipientListing;

        return $this;
    }

    /**
     * Get recipientListing
     *
     * @return string 
     */
    public function getRecipientListing()
    {
        return $this->recipientListing;
    }

    /**
     * Set bodyText
     *
     * @param string $bodyText
     * @return ActionSchedule
     */
    public function setBodyText($bodyText)
    {
        $this->bodyText = $bodyText;

        return $this;
    }

    /**
     * Get bodyText
     *
     * @return string 
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * Set bodyHtml
     *
     * @param string $bodyHtml
     * @return ActionSchedule
     */
    public function setBodyHtml($bodyHtml)
    {
        $this->bodyHtml = $bodyHtml;

        return $this;
    }

    /**
     * Get bodyHtml
     *
     * @return string 
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return ActionSchedule
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
     * Set recordActivity
     *
     * @param boolean $recordActivity
     * @return ActionSchedule
     */
    public function setRecordActivity($recordActivity)
    {
        $this->recordActivity = $recordActivity;

        return $this;
    }

    /**
     * Get recordActivity
     *
     * @return boolean 
     */
    public function getRecordActivity()
    {
        return $this->recordActivity;
    }

    /**
     * Set absoluteDate
     *
     * @param \DateTime $absoluteDate
     * @return ActionSchedule
     */
    public function setAbsoluteDate($absoluteDate)
    {
        $this->absoluteDate = $absoluteDate;

        return $this;
    }

    /**
     * Get absoluteDate
     *
     * @return \DateTime 
     */
    public function getAbsoluteDate()
    {
        return $this->absoluteDate;
    }

    /**
     * Set mapping
     *
     * @param \Civi\Core\ActionMapping $mapping
     * @return ActionSchedule
     */
    public function setMapping(\Civi\Core\ActionMapping $mapping = null)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * Get mapping
     *
     * @return \Civi\Core\ActionMapping 
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Set group
     *
     * @param \Civi\Contact\Group $group
     * @return ActionSchedule
     */
    public function setGroup(\Civi\Contact\Group $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Civi\Contact\Group 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set msgTemplate
     *
     * @param \Civi\Core\MessageTemplate $msgTemplate
     * @return ActionSchedule
     */
    public function setMsgTemplate(\Civi\Core\MessageTemplate $msgTemplate = null)
    {
        $this->msgTemplate = $msgTemplate;

        return $this;
    }

    /**
     * Get msgTemplate
     *
     * @return \Civi\Core\MessageTemplate 
     */
    public function getMsgTemplate()
    {
        return $this->msgTemplate;
    }
}
