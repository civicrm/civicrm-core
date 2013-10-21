<?php

namespace Civi\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Participant
 *
 * @ORM\Table(name="civicrm_participant", indexes={@ORM\Index(name="index_status_id", columns={"status_id"}), @ORM\Index(name="index_role_id", columns={"role_id"}), @ORM\Index(name="FK_civicrm_participant_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_participant_event_id", columns={"event_id"}), @ORM\Index(name="FK_civicrm_participant_registered_by_id", columns={"registered_by_id"}), @ORM\Index(name="FK_civicrm_participant_discount_id", columns={"discount_id"}), @ORM\Index(name="FK_civicrm_participant_campaign_id", columns={"campaign_id"}), @ORM\Index(name="FK_civicrm_participant_cart_id", columns={"cart_id"})})
 * @ORM\Entity
 */
class Participant extends \Civi\Core\Entity
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
     * @ORM\Column(name="role_id", type="string", length=128, nullable=true)
     */
    private $roleId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="register_date", type="datetime", nullable=true)
     */
    private $registerDate;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=128, nullable=true)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_level", type="text", nullable=true)
     */
    private $feeLevel;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_test", type="boolean", nullable=true)
     */
    private $isTest = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_pay_later", type="boolean", nullable=true)
     */
    private $isPayLater = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="fee_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $feeAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_currency", type="string", length=3, nullable=true)
     */
    private $feeCurrency;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount_amount", type="integer", nullable=true)
     */
    private $discountAmount;

    /**
     * @var integer
     *
     * @ORM\Column(name="must_wait", type="integer", nullable=true)
     */
    private $mustWait;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * })
     */
    private $contact;

    /**
     * @var \Civi\Event\Event
     *
     * @ORM\ManyToOne(targetEntity="Civi\Event\Event")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     * })
     */
    private $event;

    /**
     * @var \Civi\Event\ParticipantStatusType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Event\ParticipantStatusType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     */
    private $status;

    /**
     * @var \Civi\Event\Participant
     *
     * @ORM\ManyToOne(targetEntity="Civi\Event\Participant")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="registered_by_id", referencedColumnName="id")
     * })
     */
    private $registeredBy;

    /**
     * @var \Civi\Core\Discount
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Discount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="discount_id", referencedColumnName="id")
     * })
     */
    private $discount;

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
     * @var \Civi\Event\Cart
     *
     * @ORM\ManyToOne(targetEntity="Civi\Event\Cart")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cart_id", referencedColumnName="id")
     * })
     */
    private $cart;



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
     * Set roleId
     *
     * @param string $roleId
     * @return Participant
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Get roleId
     *
     * @return string 
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * Set registerDate
     *
     * @param \DateTime $registerDate
     * @return Participant
     */
    public function setRegisterDate($registerDate)
    {
        $this->registerDate = $registerDate;

        return $this;
    }

    /**
     * Get registerDate
     *
     * @return \DateTime 
     */
    public function getRegisterDate()
    {
        return $this->registerDate;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Participant
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
     * Set feeLevel
     *
     * @param string $feeLevel
     * @return Participant
     */
    public function setFeeLevel($feeLevel)
    {
        $this->feeLevel = $feeLevel;

        return $this;
    }

    /**
     * Get feeLevel
     *
     * @return string 
     */
    public function getFeeLevel()
    {
        return $this->feeLevel;
    }

    /**
     * Set isTest
     *
     * @param boolean $isTest
     * @return Participant
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
     * Set isPayLater
     *
     * @param boolean $isPayLater
     * @return Participant
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
     * Set feeAmount
     *
     * @param string $feeAmount
     * @return Participant
     */
    public function setFeeAmount($feeAmount)
    {
        $this->feeAmount = $feeAmount;

        return $this;
    }

    /**
     * Get feeAmount
     *
     * @return string 
     */
    public function getFeeAmount()
    {
        return $this->feeAmount;
    }

    /**
     * Set feeCurrency
     *
     * @param string $feeCurrency
     * @return Participant
     */
    public function setFeeCurrency($feeCurrency)
    {
        $this->feeCurrency = $feeCurrency;

        return $this;
    }

    /**
     * Get feeCurrency
     *
     * @return string 
     */
    public function getFeeCurrency()
    {
        return $this->feeCurrency;
    }

    /**
     * Set discountAmount
     *
     * @param integer $discountAmount
     * @return Participant
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    /**
     * Get discountAmount
     *
     * @return integer 
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Set mustWait
     *
     * @param integer $mustWait
     * @return Participant
     */
    public function setMustWait($mustWait)
    {
        $this->mustWait = $mustWait;

        return $this;
    }

    /**
     * Get mustWait
     *
     * @return integer 
     */
    public function getMustWait()
    {
        return $this->mustWait;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Participant
     */
    public function setContact(\Civi\Contact\Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set event
     *
     * @param \Civi\Event\Event $event
     * @return Participant
     */
    public function setEvent(\Civi\Event\Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return \Civi\Event\Event 
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set status
     *
     * @param \Civi\Event\ParticipantStatusType $status
     * @return Participant
     */
    public function setStatus(\Civi\Event\ParticipantStatusType $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \Civi\Event\ParticipantStatusType 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set registeredBy
     *
     * @param \Civi\Event\Participant $registeredBy
     * @return Participant
     */
    public function setRegisteredBy(\Civi\Event\Participant $registeredBy = null)
    {
        $this->registeredBy = $registeredBy;

        return $this;
    }

    /**
     * Get registeredBy
     *
     * @return \Civi\Event\Participant 
     */
    public function getRegisteredBy()
    {
        return $this->registeredBy;
    }

    /**
     * Set discount
     *
     * @param \Civi\Core\Discount $discount
     * @return Participant
     */
    public function setDiscount(\Civi\Core\Discount $discount = null)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return \Civi\Core\Discount 
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set campaign
     *
     * @param \Civi\Campaign\Campaign $campaign
     * @return Participant
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
     * Set cart
     *
     * @param \Civi\Event\Cart $cart
     * @return Participant
     */
    public function setCart(\Civi\Event\Cart $cart = null)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Get cart
     *
     * @return \Civi\Event\Cart 
     */
    public function getCart()
    {
        return $this->cart;
    }
}
