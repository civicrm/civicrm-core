<?php

namespace Civi\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\UnitOfWork;

/**
 * Event
 *
 * @ORM\Table(name="civicrm_event", indexes={@ORM\Index(name="index_event_type_id", columns={"event_type_id"}), @ORM\Index(name="index_participant_listing_id", columns={"participant_listing_id"}), @ORM\Index(name="index_parent_event_id", columns={"parent_event_id"}), @ORM\Index(name="FK_civicrm_event_loc_block_id", columns={"loc_block_id"}), @ORM\Index(name="FK_civicrm_event_created_id", columns={"created_id"}), @ORM\Index(name="FK_civicrm_event_campaign_id", columns={"campaign_id"})})
 * @ORM\Entity 
 * @ORM\HasLifecycleCallbacks
 */
class Event extends \Civi\Core\Entity
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
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="summary", type="text", nullable=true)
     */
    private $summary;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="event_type_id", type="integer", nullable=true)
     */
    private $eventTypeId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="participant_listing_id", type="integer", nullable=true)
     */
    private $participantListingId = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_public", type="boolean", nullable=true)
     */
    private $isPublic = '1';

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
     * @var boolean
     *
     * @ORM\Column(name="is_online_registration", type="boolean", nullable=true)
     */
    private $isOnlineRegistration = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="registration_link_text", type="string", length=255, nullable=true)
     */
    private $registrationLinkText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_start_date", type="datetime", nullable=true)
     */
    private $registrationStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_end_date", type="datetime", nullable=true)
     */
    private $registrationEndDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_participants", type="integer", nullable=true)
     */
    private $maxParticipants;

    /**
     * @var string
     *
     * @ORM\Column(name="event_full_text", type="text", nullable=true)
     */
    private $eventFullText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_monetary", type="boolean", nullable=true)
     */
    private $isMonetary = '0';

    /**
     * @var \Civi\Financial\Type
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Type")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="financial_type_id", referencedColumnName="id")
     * })
     */
    private $financialType;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_processor", type="string", length=128, nullable=true)
     */
    private $paymentProcessor;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_map", type="boolean", nullable=true)
     */
    private $isMap = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="fee_label", type="string", length=255, nullable=true)
     */
    private $feeLabel;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_show_location", type="boolean", nullable=true)
     */
    private $isShowLocation = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="default_role_id", type="integer", nullable=true)
     */
    private $defaultRoleId = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="intro_text", type="text", nullable=true)
     */
    private $introText;

    /**
     * @var string
     *
     * @ORM\Column(name="footer_text", type="text", nullable=true)
     */
    private $footerText;

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_title", type="string", length=255, nullable=true)
     */
    private $confirmTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_text", type="text", nullable=true)
     */
    private $confirmText;

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_footer_text", type="text", nullable=true)
     */
    private $confirmFooterText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_email_confirm", type="boolean", nullable=true)
     */
    private $isEmailConfirm = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_email_text", type="text", nullable=true)
     */
    private $confirmEmailText;

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_from_name", type="string", length=255, nullable=true)
     */
    private $confirmFromName;

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_from_email", type="string", length=255, nullable=true)
     */
    private $confirmFromEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="cc_confirm", type="string", length=255, nullable=true)
     */
    private $ccConfirm;

    /**
     * @var string
     *
     * @ORM\Column(name="bcc_confirm", type="string", length=255, nullable=true)
     */
    private $bccConfirm;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_fee_id", type="integer", nullable=true)
     */
    private $defaultFeeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_discount_fee_id", type="integer", nullable=true)
     */
    private $defaultDiscountFeeId;

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
     * @var string
     *
     * @ORM\Column(name="thankyou_footer_text", type="text", nullable=true)
     */
    private $thankyouFooterText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_pay_later", type="boolean", nullable=true)
     */
    private $isPayLater = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="pay_later_text", type="text", nullable=true)
     */
    private $payLaterText;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_later_receipt", type="text", nullable=true)
     */
    private $payLaterReceipt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_partial_payment", type="boolean", nullable=true)
     */
    private $isPartialPayment = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="initial_amount_label", type="string", length=255, nullable=true)
     */
    private $initialAmountLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="initial_amount_help_text", type="text", nullable=true)
     */
    private $initialAmountHelpText;

    /**
     * @var string
     *
     * @ORM\Column(name="min_initial_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $minInitialAmount;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_multiple_registrations", type="boolean", nullable=true)
     */
    private $isMultipleRegistrations = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="allow_same_participant_emails", type="boolean", nullable=true)
     */
    private $allowSameParticipantEmails = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_waitlist", type="boolean", nullable=true)
     */
    private $hasWaitlist;

    /**
     * @var boolean
     *
     * @ORM\Column(name="requires_approval", type="boolean", nullable=true)
     */
    private $requiresApproval;

    /**
     * @var integer
     *
     * @ORM\Column(name="expiration_time", type="integer", nullable=true)
     */
    private $expirationTime;

    /**
     * @var string
     *
     * @ORM\Column(name="waitlist_text", type="text", nullable=true)
     */
    private $waitlistText;

    /**
     * @var string
     *
     * @ORM\Column(name="approval_req_text", type="text", nullable=true)
     */
    private $approvalReqText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_template", type="boolean", nullable=true)
     */
    private $isTemplate = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="template_title", type="string", length=255, nullable=true)
     */
    private $templateTitle;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_share", type="boolean", nullable=true)
     */
    private $isShare = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_event_id", type="integer", nullable=true)
     */
    private $parentEventId;

    /**
     * @var integer
     *
     * @ORM\Column(name="slot_label_id", type="integer", nullable=true)
     */
    private $slotLabelId;

    /**
     * @var \Civi\Core\LocBlock
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\LocBlock")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="loc_block_id", referencedColumnName="id")
     * })
     */
    private $locBlock;

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
     * @var \Civi\Campaign\Campaign
     *
     * @ORM\ManyToOne(targetEntity="Civi\Campaign\Campaign")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     * })
     */
    private $campaign;

    /**
     *
     * @ORM\OneToMany(targetEntity="Civi\Event\Participant", mappedBy="event", cascade={"persist"})
     */
    private $participants;

    /**
     *
     * @ORM\OneToMany(targetEntity="Civi\Price\SetEventEntity", mappedBy="event", cascade={"persist"})
     */

    private $priceSetEventEntities;

    private $paymentProcessors;

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
     * @return Event
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
     * Set summary
     *
     * @param string $summary
     * @return Event
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary
     *
     * @return string 
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Event
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
     * Set eventTypeId
     *
     * @param integer $eventTypeId
     * @return Event
     */
    public function setEventTypeId($eventTypeId)
    {
        $this->eventTypeId = $eventTypeId;

        return $this;
    }

    /**
     * Get eventTypeId
     *
     * @return integer 
     */
    public function getEventTypeId()
    {
        return $this->eventTypeId;
    }

    /**
     * Set participantListingId
     *
     * @param integer $participantListingId
     * @return Event
     */
    public function setParticipantListingId($participantListingId)
    {
        $this->participantListingId = $participantListingId;

        return $this;
    }

    /**
     * Get participantListingId
     *
     * @return integer 
     */
    public function getParticipantListingId()
    {
        return $this->participantListingId;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     * @return Event
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic
     *
     * @return boolean 
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Event
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
     * @return Event
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
     * Set isOnlineRegistration
     *
     * @param boolean $isOnlineRegistration
     * @return Event
     */
    public function setIsOnlineRegistration($isOnlineRegistration)
    {
        $this->isOnlineRegistration = $isOnlineRegistration;

        return $this;
    }

    /**
     * Get isOnlineRegistration
     *
     * @return boolean 
     */
    public function getIsOnlineRegistration()
    {
        return $this->isOnlineRegistration;
    }

    /**
     * Set registrationLinkText
     *
     * @param string $registrationLinkText
     * @return Event
     */
    public function setRegistrationLinkText($registrationLinkText)
    {
        $this->registrationLinkText = $registrationLinkText;

        return $this;
    }

    /**
     * Get registrationLinkText
     *
     * @return string 
     */
    public function getRegistrationLinkText()
    {
        return $this->registrationLinkText;
    }

    /**
     * Set registrationStartDate
     *
     * @param \DateTime $registrationStartDate
     * @return Event
     */
    public function setRegistrationStartDate($registrationStartDate)
    {
        $this->registrationStartDate = $registrationStartDate;

        return $this;
    }

    /**
     * Get registrationStartDate
     *
     * @return \DateTime 
     */
    public function getRegistrationStartDate()
    {
        return $this->registrationStartDate;
    }

    /**
     * Set registrationEndDate
     *
     * @param \DateTime $registrationEndDate
     * @return Event
     */
    public function setRegistrationEndDate($registrationEndDate)
    {
        $this->registrationEndDate = $registrationEndDate;

        return $this;
    }

    /**
     * Get registrationEndDate
     *
     * @return \DateTime 
     */
    public function getRegistrationEndDate()
    {
        return $this->registrationEndDate;
    }

    /**
     * Set maxParticipants
     *
     * @param integer $maxParticipants
     * @return Event
     */
    public function setMaxParticipants($maxParticipants)
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    /**
     * Get maxParticipants
     *
     * @return integer 
     */
    public function getMaxParticipants()
    {
        return $this->maxParticipants;
    }

    /**
     * Set eventFullText
     *
     * @param string $eventFullText
     * @return Event
     */
    public function setEventFullText($eventFullText)
    {
        $this->eventFullText = $eventFullText;

        return $this;
    }

    /**
     * Get eventFullText
     *
     * @return string 
     */
    public function getEventFullText()
    {
        return $this->eventFullText;
    }

    /**
     * Set isMonetary
     *
     * @param boolean $isMonetary
     * @return Event
     */
    public function setIsMonetary($isMonetary)
    {
        $this->isMonetary = $isMonetary;

        return $this;
    }

    /**
     * Get isMonetary
     *
     * @return boolean 
     */
    public function getIsMonetary()
    {
        return $this->isMonetary;
    }

    /**
     * Set isMap
     *
     * @param boolean $isMap
     * @return Event
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return Event
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
     * Set feeLabel
     *
     * @param string $feeLabel
     * @return Event
     */
    public function setFeeLabel($feeLabel)
    {
        $this->feeLabel = $feeLabel;

        return $this;
    }

    /**
     * Get feeLabel
     *
     * @return string 
     */
    public function getFeeLabel()
    {
        return $this->feeLabel;
    }

    /**
     * Set isShowLocation
     *
     * @param boolean $isShowLocation
     * @return Event
     */
    public function setIsShowLocation($isShowLocation)
    {
        $this->isShowLocation = $isShowLocation;

        return $this;
    }

    /**
     * Get isShowLocation
     *
     * @return boolean 
     */
    public function getIsShowLocation()
    {
        return $this->isShowLocation;
    }

    /**
     * Set defaultRoleId
     *
     * @param integer $defaultRoleId
     * @return Event
     */
    public function setDefaultRoleId($defaultRoleId)
    {
        $this->defaultRoleId = $defaultRoleId;

        return $this;
    }

    /**
     * Get defaultRoleId
     *
     * @return integer 
     */
    public function getDefaultRoleId()
    {
        return $this->defaultRoleId;
    }

    /**
     * Set introText
     *
     * @param string $introText
     * @return Event
     */
    public function setIntroText($introText)
    {
        $this->introText = $introText;

        return $this;
    }

    /**
     * Get introText
     *
     * @return string 
     */
    public function getIntroText()
    {
        return $this->introText;
    }

    /**
     * Set footerText
     *
     * @param string $footerText
     * @return Event
     */
    public function setFooterText($footerText)
    {
        $this->footerText = $footerText;

        return $this;
    }

    /**
     * Get footerText
     *
     * @return string 
     */
    public function getFooterText()
    {
        return $this->footerText;
    }

    /**
     * Set confirmTitle
     *
     * @param string $confirmTitle
     * @return Event
     */
    public function setConfirmTitle($confirmTitle)
    {
        $this->confirmTitle = $confirmTitle;

        return $this;
    }

    /**
     * Get confirmTitle
     *
     * @return string 
     */
    public function getConfirmTitle()
    {
        return $this->confirmTitle;
    }

    /**
     * Set confirmText
     *
     * @param string $confirmText
     * @return Event
     */
    public function setConfirmText($confirmText)
    {
        $this->confirmText = $confirmText;

        return $this;
    }

    /**
     * Get confirmText
     *
     * @return string 
     */
    public function getConfirmText()
    {
        return $this->confirmText;
    }

    /**
     * Set confirmFooterText
     *
     * @param string $confirmFooterText
     * @return Event
     */
    public function setConfirmFooterText($confirmFooterText)
    {
        $this->confirmFooterText = $confirmFooterText;

        return $this;
    }

    /**
     * Get confirmFooterText
     *
     * @return string 
     */
    public function getConfirmFooterText()
    {
        return $this->confirmFooterText;
    }

    /**
     * Set isEmailConfirm
     *
     * @param boolean $isEmailConfirm
     * @return Event
     */
    public function setIsEmailConfirm($isEmailConfirm)
    {
        $this->isEmailConfirm = $isEmailConfirm;

        return $this;
    }

    /**
     * Get isEmailConfirm
     *
     * @return boolean 
     */
    public function getIsEmailConfirm()
    {
        return $this->isEmailConfirm;
    }

    /**
     * Set confirmEmailText
     *
     * @param string $confirmEmailText
     * @return Event
     */
    public function setConfirmEmailText($confirmEmailText)
    {
        $this->confirmEmailText = $confirmEmailText;

        return $this;
    }

    /**
     * Get confirmEmailText
     *
     * @return string 
     */
    public function getConfirmEmailText()
    {
        return $this->confirmEmailText;
    }

    /**
     * Set confirmFromName
     *
     * @param string $confirmFromName
     * @return Event
     */
    public function setConfirmFromName($confirmFromName)
    {
        $this->confirmFromName = $confirmFromName;

        return $this;
    }

    /**
     * Get confirmFromName
     *
     * @return string 
     */
    public function getConfirmFromName()
    {
        return $this->confirmFromName;
    }

    /**
     * Set confirmFromEmail
     *
     * @param string $confirmFromEmail
     * @return Event
     */
    public function setConfirmFromEmail($confirmFromEmail)
    {
        $this->confirmFromEmail = $confirmFromEmail;

        return $this;
    }

    /**
     * Get confirmFromEmail
     *
     * @return string 
     */
    public function getConfirmFromEmail()
    {
        return $this->confirmFromEmail;
    }

    /**
     * Set ccConfirm
     *
     * @param string $ccConfirm
     * @return Event
     */
    public function setCcConfirm($ccConfirm)
    {
        $this->ccConfirm = $ccConfirm;

        return $this;
    }

    /**
     * Get ccConfirm
     *
     * @return string 
     */
    public function getCcConfirm()
    {
        return $this->ccConfirm;
    }

    /**
     * Set bccConfirm
     *
     * @param string $bccConfirm
     * @return Event
     */
    public function setBccConfirm($bccConfirm)
    {
        $this->bccConfirm = $bccConfirm;

        return $this;
    }

    /**
     * Get bccConfirm
     *
     * @return string 
     */
    public function getBccConfirm()
    {
        return $this->bccConfirm;
    }

    /**
     * Set defaultFeeId
     *
     * @param integer $defaultFeeId
     * @return Event
     */
    public function setDefaultFeeId($defaultFeeId)
    {
        $this->defaultFeeId = $defaultFeeId;

        return $this;
    }

    /**
     * Get defaultFeeId
     *
     * @return integer 
     */
    public function getDefaultFeeId()
    {
        return $this->defaultFeeId;
    }

    /**
     * Set defaultDiscountFeeId
     *
     * @param integer $defaultDiscountFeeId
     * @return Event
     */
    public function setDefaultDiscountFeeId($defaultDiscountFeeId)
    {
        $this->defaultDiscountFeeId = $defaultDiscountFeeId;

        return $this;
    }

    /**
     * Get defaultDiscountFeeId
     *
     * @return integer 
     */
    public function getDefaultDiscountFeeId()
    {
        return $this->defaultDiscountFeeId;
    }

    /**
     * Set thankyouTitle
     *
     * @param string $thankyouTitle
     * @return Event
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
     * @return Event
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
     * Set thankyouFooterText
     *
     * @param string $thankyouFooterText
     * @return Event
     */
    public function setThankyouFooterText($thankyouFooterText)
    {
        $this->thankyouFooterText = $thankyouFooterText;

        return $this;
    }

    /**
     * Get thankyouFooterText
     *
     * @return string 
     */
    public function getThankyouFooterText()
    {
        return $this->thankyouFooterText;
    }

    /**
     * Set isPayLater
     *
     * @param boolean $isPayLater
     * @return Event
     */
    public function setIsPayLater($isPayLater)
    {
        $this->isPayLater = $isPayLater;

        return $this;
    }

    /**
     * Get isPayLater
     *
     * @return boolean 
     */
    public function getIsPayLater()
    {
        return $this->isPayLater;
    }

    /**
     * Set payLaterText
     *
     * @param string $payLaterText
     * @return Event
     */
    public function setPayLaterText($payLaterText)
    {
        $this->payLaterText = $payLaterText;

        return $this;
    }

    /**
     * Get payLaterText
     *
     * @return string 
     */
    public function getPayLaterText()
    {
        return $this->payLaterText;
    }

    /**
     * Set payLaterReceipt
     *
     * @param string $payLaterReceipt
     * @return Event
     */
    public function setPayLaterReceipt($payLaterReceipt)
    {
        $this->payLaterReceipt = $payLaterReceipt;

        return $this;
    }

    /**
     * Get payLaterReceipt
     *
     * @return string 
     */
    public function getPayLaterReceipt()
    {
        return $this->payLaterReceipt;
    }

    /**
     * Set isPartialPayment
     *
     * @param boolean $isPartialPayment
     * @return Event
     */
    public function setIsPartialPayment($isPartialPayment)
    {
        $this->isPartialPayment = $isPartialPayment;

        return $this;
    }

    /**
     * Get isPartialPayment
     *
     * @return boolean 
     */
    public function getIsPartialPayment()
    {
        return $this->isPartialPayment;
    }

    /**
     * Set initialAmountLabel
     *
     * @param string $initialAmountLabel
     * @return Event
     */
    public function setInitialAmountLabel($initialAmountLabel)
    {
        $this->initialAmountLabel = $initialAmountLabel;

        return $this;
    }

    /**
     * Get initialAmountLabel
     *
     * @return string 
     */
    public function getInitialAmountLabel()
    {
        return $this->initialAmountLabel;
    }

    /**
     * Set initialAmountHelpText
     *
     * @param string $initialAmountHelpText
     * @return Event
     */
    public function setInitialAmountHelpText($initialAmountHelpText)
    {
        $this->initialAmountHelpText = $initialAmountHelpText;

        return $this;
    }

    /**
     * Get initialAmountHelpText
     *
     * @return string 
     */
    public function getInitialAmountHelpText()
    {
        return $this->initialAmountHelpText;
    }

    /**
     * Set minInitialAmount
     *
     * @param string $minInitialAmount
     * @return Event
     */
    public function setMinInitialAmount($minInitialAmount)
    {
        $this->minInitialAmount = $minInitialAmount;

        return $this;
    }

    /**
     * Get minInitialAmount
     *
     * @return string 
     */
    public function getMinInitialAmount()
    {
        return $this->minInitialAmount;
    }

    /**
     * Set isMultipleRegistrations
     *
     * @param boolean $isMultipleRegistrations
     * @return Event
     */
    public function setIsMultipleRegistrations($isMultipleRegistrations)
    {
        $this->isMultipleRegistrations = $isMultipleRegistrations;

        return $this;
    }

    /**
     * Get isMultipleRegistrations
     *
     * @return boolean 
     */
    public function getIsMultipleRegistrations()
    {
        return $this->isMultipleRegistrations;
    }

    /**
     * Set allowSameParticipantEmails
     *
     * @param boolean $allowSameParticipantEmails
     * @return Event
     */
    public function setAllowSameParticipantEmails($allowSameParticipantEmails)
    {
        $this->allowSameParticipantEmails = $allowSameParticipantEmails;

        return $this;
    }

    /**
     * Get allowSameParticipantEmails
     *
     * @return boolean 
     */
    public function getAllowSameParticipantEmails()
    {
        return $this->allowSameParticipantEmails;
    }

    /**
     * Set hasWaitlist
     *
     * @param boolean $hasWaitlist
     * @return Event
     */
    public function setHasWaitlist($hasWaitlist)
    {
        $this->hasWaitlist = $hasWaitlist;

        return $this;
    }

    /**
     * Get hasWaitlist
     *
     * @return boolean 
     */
    public function getHasWaitlist()
    {
        return $this->hasWaitlist;
    }

    /**
     * Set requiresApproval
     *
     * @param boolean $requiresApproval
     * @return Event
     */
    public function setRequiresApproval($requiresApproval)
    {
        $this->requiresApproval = $requiresApproval;

        return $this;
    }

    /**
     * Get requiresApproval
     *
     * @return boolean 
     */
    public function getRequiresApproval()
    {
        return $this->requiresApproval;
    }

    /**
     * Set expirationTime
     *
     * @param integer $expirationTime
     * @return Event
     */
    public function setExpirationTime($expirationTime)
    {
        $this->expirationTime = $expirationTime;

        return $this;
    }

    /**
     * Get expirationTime
     *
     * @return integer 
     */
    public function getExpirationTime()
    {
        return $this->expirationTime;
    }

    /**
     * Set waitlistText
     *
     * @param string $waitlistText
     * @return Event
     */
    public function setWaitlistText($waitlistText)
    {
        $this->waitlistText = $waitlistText;

        return $this;
    }

    /**
     * Get waitlistText
     *
     * @return string 
     */
    public function getWaitlistText()
    {
        return $this->waitlistText;
    }

    /**
     * Set approvalReqText
     *
     * @param string $approvalReqText
     * @return Event
     */
    public function setApprovalReqText($approvalReqText)
    {
        $this->approvalReqText = $approvalReqText;

        return $this;
    }

    /**
     * Get approvalReqText
     *
     * @return string 
     */
    public function getApprovalReqText()
    {
        return $this->approvalReqText;
    }

    /**
     * Set isTemplate
     *
     * @param boolean $isTemplate
     * @return Event
     */
    public function setIsTemplate($isTemplate)
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    /**
     * Get isTemplate
     *
     * @return boolean 
     */
    public function getIsTemplate()
    {
        return $this->isTemplate;
    }

    /**
     * Set templateTitle
     *
     * @param string $templateTitle
     * @return Event
     */
    public function setTemplateTitle($templateTitle)
    {
        $this->templateTitle = $templateTitle;

        return $this;
    }

    /**
     * Get templateTitle
     *
     * @return string 
     */
    public function getTemplateTitle()
    {
        return $this->templateTitle;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return Event
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
     * Set currency
     *
     * @param string $currency
     * @return Event
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set isShare
     *
     * @param boolean $isShare
     * @return Event
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
     * Set parentEventId
     *
     * @param integer $parentEventId
     * @return Event
     */
    public function setParentEventId($parentEventId)
    {
        $this->parentEventId = $parentEventId;

        return $this;
    }

    /**
     * Get parentEventId
     *
     * @return integer 
     */
    public function getParentEventId()
    {
        return $this->parentEventId;
    }

    /**
     * Set slotLabelId
     *
     * @param integer $slotLabelId
     * @return Event
     */
    public function setSlotLabelId($slotLabelId)
    {
        $this->slotLabelId = $slotLabelId;

        return $this;
    }

    /**
     * Get slotLabelId
     *
     * @return integer 
     */
    public function getSlotLabelId()
    {
        return $this->slotLabelId;
    }

    /**
     * Set locBlock
     *
     * @param \Civi\Core\LocBlock $locBlock
     * @return Event
     */
    public function setLocBlock(\Civi\Core\LocBlock $locBlock = null)
    {
        $this->locBlock = $locBlock;

        return $this;
    }

    /**
     * Get locBlock
     *
     * @return \Civi\Core\LocBlock 
     */
    public function getLocBlock()
    {
        return $this->locBlock;
    }

    /**
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return Event
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
     * Set campaign
     *
     * @param \Civi\Campaign\Campaign $campaign
     * @return Event
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
     * Constructor
     */
    public function __construct()
    {
        $this->participants = new \Doctrine\Common\Collections\ArrayCollection();
        $this->priceSetEventEntities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->paymentProcessors = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set paymentProcessor
     *
     * @param string $paymentProcessor
     * @return Event
     */
    public function setPaymentProcessor($paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;

        return $this;
    }

    /**
     * Get paymentProcessor
     *
     * @return string 
     */
    public function getPaymentProcessor()
    {
        return $this->paymentProcessor;
    }

    /**
     * Add participants
     *
     * @param \Civi\Event\Participant $participants
     * @return Event
     */
    public function addParticipant(\Civi\Event\Participant $participants)
    {
        $this->participants[] = $participants;

        return $this;
    }

    /**
     * Remove participants
     *
     * @param \Civi\Event\Participant $participants
     */
    public function removeParticipant(\Civi\Event\Participant $participants)
    {
        $this->participants->removeElement($participants);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Add priceSetEventEntities
     *
     * @param \Civi\Price\SetEventEntity $priceSetEventEntities
     * @return Event
     */
    public function addPriceSetEventEntity(\Civi\Price\SetEventEntity $priceSetEventEntities)
    {
        $this->priceSetEventEntities[] = $priceSetEventEntities;

        return $this;
    }

    /**
     * Remove priceSetEventEntities
     *
     * @param \Civi\Price\SetEventEntity $priceSetEventEntities
     */
    public function removePriceSetEventEntity(\Civi\Price\SetEventEntity $priceSetEventEntities)
    {
        $this->priceSetEventEntities->removeElement($priceSetEventEntities);
    }

    /**
     * Get priceSetEventEntities
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPriceSetEventEntities()
    {
        return $this->priceSetEventEntities;
    }

    public function addPaymentProcessor(\Civi\Financial\PaymentProcessor $paymentProcessor)
    {
        $this->paymentProcessors[] = $paymentProcessor;
        return $this;
    }

    public function removePaymentProcessor(\Civi\Financial\PaymentProcessor $paymentProcessor)
    {
        $this->paymentProcessors->removeElement($paymentProcessor);
        if ($this->updatePaymentProcessorField() === FALSE) {
            throw new Exception("Not expecting any payment processors in the array to not be persisted here.");
        }
    }

    public function getPaymentProcessors()
    {
        return $this->paymentProcessors;
    }

    public function getPriceSets()
    {
      return new ArrayCollection(array_map(function ($price_set_entity) { return $price_set_entity->getPriceSet(); }, $this->getPriceSetEventEntities()->toArray()));
    }

    public function addPriceSet($priceSet)
    {
      $priceSetEventEntity = new \Civi\Price\SetEventEntity();
      $priceSetEventEntity->setPriceSet($priceSet);
      $priceSetEventEntity->setEvent($this);
      $this->addPriceSetEventEntity($priceSetEventEntity);
    }

    /**
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return Event
     */
    public function setFinancialType(\Civi\Financial\Type $financialType = null)
    {
        $this->financialType = $financialType;

        return $this;
    }

    /**
     * Get financialType
     *
     * @return \Civi\Financial\Type 
     */
    public function getFinancialType()
    {
        return $this->financialType;
    }

    /** 
     * @ORM\PrePersist 
     */
    public function cascadePaymentProcessors(LifecycleEventArgs $event_args)
    {
        $listener_created = FALSE;
        $entity_manager = $event_args->getEntityManager();
        $unit_of_work = $entity_manager->getUnitOfWork();
        $payment_processor_ids = array();
        foreach ($this->paymentProcessors as $payment_processor) {
            $state = $unit_of_work->getEntityState($payment_processor, UnitOfWork::STATE_NEW);
            if ($state == UnitOfWork::STATE_NEW) {
                $unit_of_work->persist($payment_processor);
                if (!$listener_created) {
                    $listener_created = TRUE;
                    $commit_order_calculator = $unit_of_work->getCommitOrderCalculator();
                    $from_class = $entity_manager->getClassMetadata('\Civi\Financial\PaymentProcessor');
                    $to_class = $entity_manager->getClassMetadata('\Civi\Event\Event');
                    $commit_order_calculator->addDependency($from_class, $to_class);
                    $event_manager = $entity_manager->getEventManager();
                    $post_persist_listener = new \Civi\Event\PaymentProcessorPostPersistListener($this);
                    $event_manager->addEventListener(array(Events::postPersist), $post_persist_listener);
                }
            }
        }
        if (!$listener_created) {
            $this->updatePaymentProcessorField();
        }
    }

    public function updatePaymentProcessorField()
    {
        $payment_processor_ids = array();
        foreach ($this->paymentProcessors as $payment_processor) {
            $payment_processor_id = $payment_processor->getId();
            if ($payment_processor_id == NULL) {
                return FALSE;
            }
            $payment_processor_ids[] = $payment_processor_id;
        }
        $value = \CRM_DB_Array::marshal($payment_processor_ids);
        $this->setPaymentProcessor($value);
        return TRUE;
    }

    /** 
     * @ORM\PostLoad
     */
    public function loadPaymentProcessorsFromField(LifecycleEventArgs $event_args)
    {
        $this->paymentProcessors = new \Doctrine\Common\Collections\ArrayCollection();
        $entity_manager = $event_args->getEntityManager();
        $payment_processor_ids = \CRM_DB_Array::unmarshal($this->getPaymentProcessor());
        if ($payment_processor_ids != NULL) {
            foreach ($payment_processor_ids as $payment_processor_id) {
                $this->paymentProcessors[] = $entity_manager->find('\Civi\Financial\PaymentProcessor', $payment_processor_id);
            }
        }
    }
}
