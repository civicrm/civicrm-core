<?php

namespace Civi\Contribute;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contribution
 *
 * @ORM\Table(name="civicrm_contribution", uniqueConstraints={@ORM\UniqueConstraint(name="UI_contrib_trxn_id", columns={"trxn_id"}), @ORM\UniqueConstraint(name="UI_contrib_invoice_id", columns={"invoice_id"})}, indexes={@ORM\Index(name="UI_contrib_payment_instrument_id", columns={"payment_instrument_id"}), @ORM\Index(name="index_contribution_status", columns={"contribution_status_id"}), @ORM\Index(name="received_date", columns={"receive_date"}), @ORM\Index(name="check_number", columns={"check_number"}), @ORM\Index(name="FK_civicrm_contribution_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_contribution_financial_type_id", columns={"financial_type_id"}), @ORM\Index(name="FK_civicrm_contribution_contribution_page_id", columns={"contribution_page_id"}), @ORM\Index(name="FK_civicrm_contribution_contribution_recur_id", columns={"contribution_recur_id"}), @ORM\Index(name="FK_civicrm_contribution_honor_contact_id", columns={"honor_contact_id"}), @ORM\Index(name="FK_civicrm_contribution_address_id", columns={"address_id"}), @ORM\Index(name="FK_civicrm_contribution_campaign_id", columns={"campaign_id"})})
 * @ORM\Entity
 */
class Contribution extends \Civi\Core\Entity
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
     * @ORM\Column(name="payment_instrument_id", type="integer", nullable=true)
     */
    private $paymentInstrumentId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="receive_date", type="datetime", nullable=true)
     */
    private $receiveDate;

    /**
     * @var string
     *
     * @ORM\Column(name="non_deductible_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $nonDeductibleAmount = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="total_amount", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $totalAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $feeAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="net_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $netAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="trxn_id", type="string", length=255, nullable=true)
     */
    private $trxnId;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_id", type="string", length=255, nullable=true)
     */
    private $invoiceId;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cancel_date", type="datetime", nullable=true)
     */
    private $cancelDate;

    /**
     * @var string
     *
     * @ORM\Column(name="cancel_reason", type="text", nullable=true)
     */
    private $cancelReason;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="receipt_date", type="datetime", nullable=true)
     */
    private $receiptDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="thankyou_date", type="datetime", nullable=true)
     */
    private $thankyouDate;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_level", type="text", nullable=true)
     */
    private $amountLevel;

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
     * @var integer
     *
     * @ORM\Column(name="contribution_status_id", type="integer", nullable=true)
     */
    private $contributionStatusId = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="honor_type_id", type="integer", nullable=true)
     */
    private $honorTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="check_number", type="string", length=255, nullable=true)
     */
    private $checkNumber;

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
     * @var \Civi\Financial\Type
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Type")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="financial_type_id", referencedColumnName="id")
     * })
     */
    private $financialType;

    /**
     * @var \Civi\Contribute\ContributionPage
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\ContributionPage")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contribution_page_id", referencedColumnName="id")
     * })
     */
    private $contributionPage;

    /**
     * @var \Civi\Contribute\ContributionRecur
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\ContributionRecur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contribution_recur_id", referencedColumnName="id")
     * })
     */
    private $contributionRecur;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="honor_contact_id", referencedColumnName="id")
     * })
     */
    private $honorContact;

    /**
     * @var \Civi\Core\Address
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Address")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     * })
     */
    private $address;

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
     * Set paymentInstrumentId
     *
     * @param integer $paymentInstrumentId
     * @return Contribution
     */
    public function setPaymentInstrumentId($paymentInstrumentId)
    {
        $this->paymentInstrumentId = $paymentInstrumentId;

        return $this;
    }

    /**
     * Get paymentInstrumentId
     *
     * @return integer 
     */
    public function getPaymentInstrumentId()
    {
        return $this->paymentInstrumentId;
    }

    /**
     * Set receiveDate
     *
     * @param \DateTime $receiveDate
     * @return Contribution
     */
    public function setReceiveDate($receiveDate)
    {
        $this->receiveDate = $receiveDate;

        return $this;
    }

    /**
     * Get receiveDate
     *
     * @return \DateTime 
     */
    public function getReceiveDate()
    {
        return $this->receiveDate;
    }

    /**
     * Set nonDeductibleAmount
     *
     * @param string $nonDeductibleAmount
     * @return Contribution
     */
    public function setNonDeductibleAmount($nonDeductibleAmount)
    {
        $this->nonDeductibleAmount = $nonDeductibleAmount;

        return $this;
    }

    /**
     * Get nonDeductibleAmount
     *
     * @return string 
     */
    public function getNonDeductibleAmount()
    {
        return $this->nonDeductibleAmount;
    }

    /**
     * Set totalAmount
     *
     * @param string $totalAmount
     * @return Contribution
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return string 
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set feeAmount
     *
     * @param string $feeAmount
     * @return Contribution
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
     * Set netAmount
     *
     * @param string $netAmount
     * @return Contribution
     */
    public function setNetAmount($netAmount)
    {
        $this->netAmount = $netAmount;

        return $this;
    }

    /**
     * Get netAmount
     *
     * @return string 
     */
    public function getNetAmount()
    {
        return $this->netAmount;
    }

    /**
     * Set trxnId
     *
     * @param string $trxnId
     * @return Contribution
     */
    public function setTrxnId($trxnId)
    {
        $this->trxnId = $trxnId;

        return $this;
    }

    /**
     * Get trxnId
     *
     * @return string 
     */
    public function getTrxnId()
    {
        return $this->trxnId;
    }

    /**
     * Set invoiceId
     *
     * @param string $invoiceId
     * @return Contribution
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoiceId = $invoiceId;

        return $this;
    }

    /**
     * Get invoiceId
     *
     * @return string 
     */
    public function getInvoiceId()
    {
        return $this->invoiceId;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Contribution
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
     * Set cancelDate
     *
     * @param \DateTime $cancelDate
     * @return Contribution
     */
    public function setCancelDate($cancelDate)
    {
        $this->cancelDate = $cancelDate;

        return $this;
    }

    /**
     * Get cancelDate
     *
     * @return \DateTime 
     */
    public function getCancelDate()
    {
        return $this->cancelDate;
    }

    /**
     * Set cancelReason
     *
     * @param string $cancelReason
     * @return Contribution
     */
    public function setCancelReason($cancelReason)
    {
        $this->cancelReason = $cancelReason;

        return $this;
    }

    /**
     * Get cancelReason
     *
     * @return string 
     */
    public function getCancelReason()
    {
        return $this->cancelReason;
    }

    /**
     * Set receiptDate
     *
     * @param \DateTime $receiptDate
     * @return Contribution
     */
    public function setReceiptDate($receiptDate)
    {
        $this->receiptDate = $receiptDate;

        return $this;
    }

    /**
     * Get receiptDate
     *
     * @return \DateTime 
     */
    public function getReceiptDate()
    {
        return $this->receiptDate;
    }

    /**
     * Set thankyouDate
     *
     * @param \DateTime $thankyouDate
     * @return Contribution
     */
    public function setThankyouDate($thankyouDate)
    {
        $this->thankyouDate = $thankyouDate;

        return $this;
    }

    /**
     * Get thankyouDate
     *
     * @return \DateTime 
     */
    public function getThankyouDate()
    {
        return $this->thankyouDate;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Contribution
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
     * Set amountLevel
     *
     * @param string $amountLevel
     * @return Contribution
     */
    public function setAmountLevel($amountLevel)
    {
        $this->amountLevel = $amountLevel;

        return $this;
    }

    /**
     * Get amountLevel
     *
     * @return string 
     */
    public function getAmountLevel()
    {
        return $this->amountLevel;
    }

    /**
     * Set isTest
     *
     * @param boolean $isTest
     * @return Contribution
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
     * @return Contribution
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
     * Set contributionStatusId
     *
     * @param integer $contributionStatusId
     * @return Contribution
     */
    public function setContributionStatusId($contributionStatusId)
    {
        $this->contributionStatusId = $contributionStatusId;

        return $this;
    }

    /**
     * Get contributionStatusId
     *
     * @return integer 
     */
    public function getContributionStatusId()
    {
        return $this->contributionStatusId;
    }

    /**
     * Set honorTypeId
     *
     * @param integer $honorTypeId
     * @return Contribution
     */
    public function setHonorTypeId($honorTypeId)
    {
        $this->honorTypeId = $honorTypeId;

        return $this;
    }

    /**
     * Get honorTypeId
     *
     * @return integer 
     */
    public function getHonorTypeId()
    {
        return $this->honorTypeId;
    }

    /**
     * Set checkNumber
     *
     * @param string $checkNumber
     * @return Contribution
     */
    public function setCheckNumber($checkNumber)
    {
        $this->checkNumber = $checkNumber;

        return $this;
    }

    /**
     * Get checkNumber
     *
     * @return string 
     */
    public function getCheckNumber()
    {
        return $this->checkNumber;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Contribution
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
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return Contribution
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
     * Set contributionPage
     *
     * @param \Civi\Contribute\ContributionPage $contributionPage
     * @return Contribution
     */
    public function setContributionPage(\Civi\Contribute\ContributionPage $contributionPage = null)
    {
        $this->contributionPage = $contributionPage;

        return $this;
    }

    /**
     * Get contributionPage
     *
     * @return \Civi\Contribute\ContributionPage 
     */
    public function getContributionPage()
    {
        return $this->contributionPage;
    }

    /**
     * Set contributionRecur
     *
     * @param \Civi\Contribute\ContributionRecur $contributionRecur
     * @return Contribution
     */
    public function setContributionRecur(\Civi\Contribute\ContributionRecur $contributionRecur = null)
    {
        $this->contributionRecur = $contributionRecur;

        return $this;
    }

    /**
     * Get contributionRecur
     *
     * @return \Civi\Contribute\ContributionRecur 
     */
    public function getContributionRecur()
    {
        return $this->contributionRecur;
    }

    /**
     * Set honorContact
     *
     * @param \Civi\Contact\Contact $honorContact
     * @return Contribution
     */
    public function setHonorContact(\Civi\Contact\Contact $honorContact = null)
    {
        $this->honorContact = $honorContact;

        return $this;
    }

    /**
     * Get honorContact
     *
     * @return \Civi\Contact\Contact 
     */
    public function getHonorContact()
    {
        return $this->honorContact;
    }

    /**
     * Set address
     *
     * @param \Civi\Core\Address $address
     * @return Contribution
     */
    public function setAddress(\Civi\Core\Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \Civi\Core\Address 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set campaign
     *
     * @param \Civi\Campaign\Campaign $campaign
     * @return Contribution
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
