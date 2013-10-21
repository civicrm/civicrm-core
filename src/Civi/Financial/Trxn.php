<?php

namespace Civi\Financial;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trxn
 *
 * @ORM\Table(name="civicrm_financial_trxn", indexes={@ORM\Index(name="UI_ftrxn_payment_instrument_id", columns={"payment_instrument_id"}), @ORM\Index(name="UI_ftrxn_check_number", columns={"check_number"}), @ORM\Index(name="FK_civicrm_financial_trxn_from_financial_account_id", columns={"from_financial_account_id"}), @ORM\Index(name="FK_civicrm_financial_trxn_to_financial_account_id", columns={"to_financial_account_id"}), @ORM\Index(name="FK_civicrm_financial_trxn_payment_processor_id", columns={"payment_processor_id"})})
 * @ORM\Entity
 */
class Trxn extends \Civi\Core\Entity
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
     * @var \DateTime
     *
     * @ORM\Column(name="trxn_date", type="datetime", nullable=true)
     */
    private $trxnDate;

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
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="trxn_id", type="string", length=255, nullable=true)
     */
    private $trxnId;

    /**
     * @var string
     *
     * @ORM\Column(name="trxn_result_code", type="string", length=255, nullable=true)
     */
    private $trxnResultCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=true)
     */
    private $statusId;

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_instrument_id", type="integer", nullable=true)
     */
    private $paymentInstrumentId;

    /**
     * @var string
     *
     * @ORM\Column(name="check_number", type="string", length=255, nullable=true)
     */
    private $checkNumber;

    /**
     * @var \Civi\Financial\Account
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="from_financial_account_id", referencedColumnName="id")
     * })
     */
    private $fromFinancialAccount;

    /**
     * @var \Civi\Financial\Account
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="to_financial_account_id", referencedColumnName="id")
     * })
     */
    private $toFinancialAccount;

    /**
     * @var \Civi\Financial\PaymentProcessor
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\PaymentProcessor")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payment_processor_id", referencedColumnName="id")
     * })
     */
    private $paymentProcessor;



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
     * Set trxnDate
     *
     * @param \DateTime $trxnDate
     * @return Trxn
     */
    public function setTrxnDate($trxnDate)
    {
        $this->trxnDate = $trxnDate;

        return $this;
    }

    /**
     * Get trxnDate
     *
     * @return \DateTime 
     */
    public function getTrxnDate()
    {
        return $this->trxnDate;
    }

    /**
     * Set totalAmount
     *
     * @param string $totalAmount
     * @return Trxn
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
     * @return Trxn
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
     * @return Trxn
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
     * Set currency
     *
     * @param string $currency
     * @return Trxn
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
     * Set trxnId
     *
     * @param string $trxnId
     * @return Trxn
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
     * Set trxnResultCode
     *
     * @param string $trxnResultCode
     * @return Trxn
     */
    public function setTrxnResultCode($trxnResultCode)
    {
        $this->trxnResultCode = $trxnResultCode;

        return $this;
    }

    /**
     * Get trxnResultCode
     *
     * @return string 
     */
    public function getTrxnResultCode()
    {
        return $this->trxnResultCode;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return Trxn
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
     * Set paymentInstrumentId
     *
     * @param integer $paymentInstrumentId
     * @return Trxn
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
     * Set checkNumber
     *
     * @param string $checkNumber
     * @return Trxn
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
     * Set fromFinancialAccount
     *
     * @param \Civi\Financial\Account $fromFinancialAccount
     * @return Trxn
     */
    public function setFromFinancialAccount(\Civi\Financial\Account $fromFinancialAccount = null)
    {
        $this->fromFinancialAccount = $fromFinancialAccount;

        return $this;
    }

    /**
     * Get fromFinancialAccount
     *
     * @return \Civi\Financial\Account 
     */
    public function getFromFinancialAccount()
    {
        return $this->fromFinancialAccount;
    }

    /**
     * Set toFinancialAccount
     *
     * @param \Civi\Financial\Account $toFinancialAccount
     * @return Trxn
     */
    public function setToFinancialAccount(\Civi\Financial\Account $toFinancialAccount = null)
    {
        $this->toFinancialAccount = $toFinancialAccount;

        return $this;
    }

    /**
     * Get toFinancialAccount
     *
     * @return \Civi\Financial\Account 
     */
    public function getToFinancialAccount()
    {
        return $this->toFinancialAccount;
    }

    /**
     * Set paymentProcessor
     *
     * @param \Civi\Financial\PaymentProcessor $paymentProcessor
     * @return Trxn
     */
    public function setPaymentProcessor(\Civi\Financial\PaymentProcessor $paymentProcessor = null)
    {
        $this->paymentProcessor = $paymentProcessor;

        return $this;
    }

    /**
     * Get paymentProcessor
     *
     * @return \Civi\Financial\PaymentProcessor 
     */
    public function getPaymentProcessor()
    {
        return $this->paymentProcessor;
    }
}
