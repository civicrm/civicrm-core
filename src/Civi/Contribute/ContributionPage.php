<?php

namespace Civi\Contribute;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContributionPage
 *
 * @ORM\Table(name="civicrm_contribution_page", indexes={@ORM\Index(name="FK_civicrm_contribution_page_financial_type_id", columns={"financial_type_id"}), @ORM\Index(name="FK_civicrm_contribution_page_created_id", columns={"created_id"}), @ORM\Index(name="FK_civicrm_contribution_page_campaign_id", columns={"campaign_id"})})
 * @ORM\Entity
 */
class ContributionPage extends \Civi\Core\Entity
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
     * @ORM\Column(name="intro_text", type="text", nullable=true)
     */
    private $introText;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_processor", type="string", length=128, nullable=true)
     */
    private $paymentProcessor;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_credit_card_only", type="boolean", nullable=true)
     */
    private $isCreditCardOnly = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_monetary", type="boolean", nullable=true)
     */
    private $isMonetary = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_recur", type="boolean", nullable=true)
     */
    private $isRecur = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_confirm_enabled", type="boolean", nullable=true)
     */
    private $isConfirmEnabled = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="recur_frequency_unit", type="string", length=128, nullable=true)
     */
    private $recurFrequencyUnit;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_recur_interval", type="boolean", nullable=true)
     */
    private $isRecurInterval = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_recur_installments", type="boolean", nullable=true)
     */
    private $isRecurInstallments = '0';

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
     * @ORM\Column(name="is_allow_other_amount", type="boolean", nullable=true)
     */
    private $isAllowOtherAmount = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="default_amount_id", type="integer", nullable=true)
     */
    private $defaultAmountId;

    /**
     * @var string
     *
     * @ORM\Column(name="min_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $minAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="max_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $maxAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="goal_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $goalAmount;

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
     * @ORM\Column(name="thankyou_footer", type="text", nullable=true)
     */
    private $thankyouFooter;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_for_organization", type="boolean", nullable=true)
     */
    private $isForOrganization = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="for_organization", type="text", nullable=true)
     */
    private $forOrganization;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_email_receipt", type="boolean", nullable=true)
     */
    private $isEmailReceipt = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="receipt_from_name", type="string", length=255, nullable=true)
     */
    private $receiptFromName;

    /**
     * @var string
     *
     * @ORM\Column(name="receipt_from_email", type="string", length=255, nullable=true)
     */
    private $receiptFromEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="cc_receipt", type="string", length=255, nullable=true)
     */
    private $ccReceipt;

    /**
     * @var string
     *
     * @ORM\Column(name="bcc_receipt", type="string", length=255, nullable=true)
     */
    private $bccReceipt;

    /**
     * @var string
     *
     * @ORM\Column(name="receipt_text", type="text", nullable=true)
     */
    private $receiptText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="footer_text", type="text", nullable=true)
     */
    private $footerText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="amount_block_is_active", type="boolean", nullable=true)
     */
    private $amountBlockIsActive = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="honor_block_is_active", type="boolean", nullable=true)
     */
    private $honorBlockIsActive;

    /**
     * @var string
     *
     * @ORM\Column(name="honor_block_title", type="string", length=255, nullable=true)
     */
    private $honorBlockTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="honor_block_text", type="text", nullable=true)
     */
    private $honorBlockText;

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
     * @var \Civi\Financial\Type
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Type")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="financial_type_id", referencedColumnName="id")
     * })
     */
    private $financialType;

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
     * @return ContributionPage
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
     * Set introText
     *
     * @param string $introText
     * @return ContributionPage
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
     * Set paymentProcessor
     *
     * @param string $paymentProcessor
     * @return ContributionPage
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
     * Set isCreditCardOnly
     *
     * @param boolean $isCreditCardOnly
     * @return ContributionPage
     */
    public function setIsCreditCardOnly($isCreditCardOnly)
    {
        $this->isCreditCardOnly = $isCreditCardOnly;

        return $this;
    }

    /**
     * Get isCreditCardOnly
     *
     * @return boolean 
     */
    public function getIsCreditCardOnly()
    {
        return $this->isCreditCardOnly;
    }

    /**
     * Set isMonetary
     *
     * @param boolean $isMonetary
     * @return ContributionPage
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
     * Set isRecur
     *
     * @param boolean $isRecur
     * @return ContributionPage
     */
    public function setIsRecur($isRecur)
    {
        $this->isRecur = $isRecur;

        return $this;
    }

    /**
     * Get isRecur
     *
     * @return boolean 
     */
    public function getIsRecur()
    {
        return $this->isRecur;
    }

    /**
     * Set isConfirmEnabled
     *
     * @param boolean $isConfirmEnabled
     * @return ContributionPage
     */
    public function setIsConfirmEnabled($isConfirmEnabled)
    {
        $this->isConfirmEnabled = $isConfirmEnabled;

        return $this;
    }

    /**
     * Get isConfirmEnabled
     *
     * @return boolean 
     */
    public function getIsConfirmEnabled()
    {
        return $this->isConfirmEnabled;
    }

    /**
     * Set recurFrequencyUnit
     *
     * @param string $recurFrequencyUnit
     * @return ContributionPage
     */
    public function setRecurFrequencyUnit($recurFrequencyUnit)
    {
        $this->recurFrequencyUnit = $recurFrequencyUnit;

        return $this;
    }

    /**
     * Get recurFrequencyUnit
     *
     * @return string 
     */
    public function getRecurFrequencyUnit()
    {
        return $this->recurFrequencyUnit;
    }

    /**
     * Set isRecurInterval
     *
     * @param boolean $isRecurInterval
     * @return ContributionPage
     */
    public function setIsRecurInterval($isRecurInterval)
    {
        $this->isRecurInterval = $isRecurInterval;

        return $this;
    }

    /**
     * Get isRecurInterval
     *
     * @return boolean 
     */
    public function getIsRecurInterval()
    {
        return $this->isRecurInterval;
    }

    /**
     * Set isRecurInstallments
     *
     * @param boolean $isRecurInstallments
     * @return ContributionPage
     */
    public function setIsRecurInstallments($isRecurInstallments)
    {
        $this->isRecurInstallments = $isRecurInstallments;

        return $this;
    }

    /**
     * Get isRecurInstallments
     *
     * @return boolean 
     */
    public function getIsRecurInstallments()
    {
        return $this->isRecurInstallments;
    }

    /**
     * Set isPayLater
     *
     * @param boolean $isPayLater
     * @return ContributionPage
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
     * @return ContributionPage
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
     * @return ContributionPage
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
     * @return ContributionPage
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
     * @return ContributionPage
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
     * @return ContributionPage
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
     * @return ContributionPage
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
     * Set isAllowOtherAmount
     *
     * @param boolean $isAllowOtherAmount
     * @return ContributionPage
     */
    public function setIsAllowOtherAmount($isAllowOtherAmount)
    {
        $this->isAllowOtherAmount = $isAllowOtherAmount;

        return $this;
    }

    /**
     * Get isAllowOtherAmount
     *
     * @return boolean 
     */
    public function getIsAllowOtherAmount()
    {
        return $this->isAllowOtherAmount;
    }

    /**
     * Set defaultAmountId
     *
     * @param integer $defaultAmountId
     * @return ContributionPage
     */
    public function setDefaultAmountId($defaultAmountId)
    {
        $this->defaultAmountId = $defaultAmountId;

        return $this;
    }

    /**
     * Get defaultAmountId
     *
     * @return integer 
     */
    public function getDefaultAmountId()
    {
        return $this->defaultAmountId;
    }

    /**
     * Set minAmount
     *
     * @param string $minAmount
     * @return ContributionPage
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;

        return $this;
    }

    /**
     * Get minAmount
     *
     * @return string 
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * Set maxAmount
     *
     * @param string $maxAmount
     * @return ContributionPage
     */
    public function setMaxAmount($maxAmount)
    {
        $this->maxAmount = $maxAmount;

        return $this;
    }

    /**
     * Get maxAmount
     *
     * @return string 
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * Set goalAmount
     *
     * @param string $goalAmount
     * @return ContributionPage
     */
    public function setGoalAmount($goalAmount)
    {
        $this->goalAmount = $goalAmount;

        return $this;
    }

    /**
     * Get goalAmount
     *
     * @return string 
     */
    public function getGoalAmount()
    {
        return $this->goalAmount;
    }

    /**
     * Set thankyouTitle
     *
     * @param string $thankyouTitle
     * @return ContributionPage
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
     * @return ContributionPage
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
     * Set thankyouFooter
     *
     * @param string $thankyouFooter
     * @return ContributionPage
     */
    public function setThankyouFooter($thankyouFooter)
    {
        $this->thankyouFooter = $thankyouFooter;

        return $this;
    }

    /**
     * Get thankyouFooter
     *
     * @return string 
     */
    public function getThankyouFooter()
    {
        return $this->thankyouFooter;
    }

    /**
     * Set isForOrganization
     *
     * @param boolean $isForOrganization
     * @return ContributionPage
     */
    public function setIsForOrganization($isForOrganization)
    {
        $this->isForOrganization = $isForOrganization;

        return $this;
    }

    /**
     * Get isForOrganization
     *
     * @return boolean 
     */
    public function getIsForOrganization()
    {
        return $this->isForOrganization;
    }

    /**
     * Set forOrganization
     *
     * @param string $forOrganization
     * @return ContributionPage
     */
    public function setForOrganization($forOrganization)
    {
        $this->forOrganization = $forOrganization;

        return $this;
    }

    /**
     * Get forOrganization
     *
     * @return string 
     */
    public function getForOrganization()
    {
        return $this->forOrganization;
    }

    /**
     * Set isEmailReceipt
     *
     * @param boolean $isEmailReceipt
     * @return ContributionPage
     */
    public function setIsEmailReceipt($isEmailReceipt)
    {
        $this->isEmailReceipt = $isEmailReceipt;

        return $this;
    }

    /**
     * Get isEmailReceipt
     *
     * @return boolean 
     */
    public function getIsEmailReceipt()
    {
        return $this->isEmailReceipt;
    }

    /**
     * Set receiptFromName
     *
     * @param string $receiptFromName
     * @return ContributionPage
     */
    public function setReceiptFromName($receiptFromName)
    {
        $this->receiptFromName = $receiptFromName;

        return $this;
    }

    /**
     * Get receiptFromName
     *
     * @return string 
     */
    public function getReceiptFromName()
    {
        return $this->receiptFromName;
    }

    /**
     * Set receiptFromEmail
     *
     * @param string $receiptFromEmail
     * @return ContributionPage
     */
    public function setReceiptFromEmail($receiptFromEmail)
    {
        $this->receiptFromEmail = $receiptFromEmail;

        return $this;
    }

    /**
     * Get receiptFromEmail
     *
     * @return string 
     */
    public function getReceiptFromEmail()
    {
        return $this->receiptFromEmail;
    }

    /**
     * Set ccReceipt
     *
     * @param string $ccReceipt
     * @return ContributionPage
     */
    public function setCcReceipt($ccReceipt)
    {
        $this->ccReceipt = $ccReceipt;

        return $this;
    }

    /**
     * Get ccReceipt
     *
     * @return string 
     */
    public function getCcReceipt()
    {
        return $this->ccReceipt;
    }

    /**
     * Set bccReceipt
     *
     * @param string $bccReceipt
     * @return ContributionPage
     */
    public function setBccReceipt($bccReceipt)
    {
        $this->bccReceipt = $bccReceipt;

        return $this;
    }

    /**
     * Get bccReceipt
     *
     * @return string 
     */
    public function getBccReceipt()
    {
        return $this->bccReceipt;
    }

    /**
     * Set receiptText
     *
     * @param string $receiptText
     * @return ContributionPage
     */
    public function setReceiptText($receiptText)
    {
        $this->receiptText = $receiptText;

        return $this;
    }

    /**
     * Get receiptText
     *
     * @return string 
     */
    public function getReceiptText()
    {
        return $this->receiptText;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return ContributionPage
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
     * Set footerText
     *
     * @param string $footerText
     * @return ContributionPage
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
     * Set amountBlockIsActive
     *
     * @param boolean $amountBlockIsActive
     * @return ContributionPage
     */
    public function setAmountBlockIsActive($amountBlockIsActive)
    {
        $this->amountBlockIsActive = $amountBlockIsActive;

        return $this;
    }

    /**
     * Get amountBlockIsActive
     *
     * @return boolean 
     */
    public function getAmountBlockIsActive()
    {
        return $this->amountBlockIsActive;
    }

    /**
     * Set honorBlockIsActive
     *
     * @param boolean $honorBlockIsActive
     * @return ContributionPage
     */
    public function setHonorBlockIsActive($honorBlockIsActive)
    {
        $this->honorBlockIsActive = $honorBlockIsActive;

        return $this;
    }

    /**
     * Get honorBlockIsActive
     *
     * @return boolean 
     */
    public function getHonorBlockIsActive()
    {
        return $this->honorBlockIsActive;
    }

    /**
     * Set honorBlockTitle
     *
     * @param string $honorBlockTitle
     * @return ContributionPage
     */
    public function setHonorBlockTitle($honorBlockTitle)
    {
        $this->honorBlockTitle = $honorBlockTitle;

        return $this;
    }

    /**
     * Get honorBlockTitle
     *
     * @return string 
     */
    public function getHonorBlockTitle()
    {
        return $this->honorBlockTitle;
    }

    /**
     * Set honorBlockText
     *
     * @param string $honorBlockText
     * @return ContributionPage
     */
    public function setHonorBlockText($honorBlockText)
    {
        $this->honorBlockText = $honorBlockText;

        return $this;
    }

    /**
     * Get honorBlockText
     *
     * @return string 
     */
    public function getHonorBlockText()
    {
        return $this->honorBlockText;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return ContributionPage
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
     * @return ContributionPage
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
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return ContributionPage
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
     * @return ContributionPage
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
     * @return ContributionPage
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
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return ContributionPage
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
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return ContributionPage
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
     * @return ContributionPage
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
