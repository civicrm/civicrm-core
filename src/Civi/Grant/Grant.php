<?php

namespace Civi\Grant;

use Doctrine\ORM\Mapping as ORM;

/**
 * Grant
 *
 * @ORM\Table(name="civicrm_grant", indexes={@ORM\Index(name="index_grant_type_id", columns={"grant_type_id"}), @ORM\Index(name="index_status_id", columns={"status_id"}), @ORM\Index(name="FK_civicrm_grant_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_grant_financial_type_id", columns={"financial_type_id"})})
 * @ORM\Entity
 */
class Grant extends \Civi\Core\Entity
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
     * @ORM\Column(name="application_received_date", type="date", nullable=true)
     */
    private $applicationReceivedDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="decision_date", type="date", nullable=true)
     */
    private $decisionDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="money_transfer_date", type="date", nullable=true)
     */
    private $moneyTransferDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="grant_due_date", type="date", nullable=true)
     */
    private $grantDueDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="grant_report_received", type="boolean", nullable=true)
     */
    private $grantReportReceived;

    /**
     * @var integer
     *
     * @ORM\Column(name="grant_type_id", type="integer", nullable=false)
     */
    private $grantTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_total", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $amountTotal;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_requested", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $amountRequested;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_granted", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $amountGranted;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="rationale", type="text", nullable=true)
     */
    private $rationale;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=false)
     */
    private $statusId;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set applicationReceivedDate
     *
     * @param \DateTime $applicationReceivedDate
     * @return Grant
     */
    public function setApplicationReceivedDate($applicationReceivedDate)
    {
        $this->applicationReceivedDate = $applicationReceivedDate;

        return $this;
    }

    /**
     * Get applicationReceivedDate
     *
     * @return \DateTime 
     */
    public function getApplicationReceivedDate()
    {
        return $this->applicationReceivedDate;
    }

    /**
     * Set decisionDate
     *
     * @param \DateTime $decisionDate
     * @return Grant
     */
    public function setDecisionDate($decisionDate)
    {
        $this->decisionDate = $decisionDate;

        return $this;
    }

    /**
     * Get decisionDate
     *
     * @return \DateTime 
     */
    public function getDecisionDate()
    {
        return $this->decisionDate;
    }

    /**
     * Set moneyTransferDate
     *
     * @param \DateTime $moneyTransferDate
     * @return Grant
     */
    public function setMoneyTransferDate($moneyTransferDate)
    {
        $this->moneyTransferDate = $moneyTransferDate;

        return $this;
    }

    /**
     * Get moneyTransferDate
     *
     * @return \DateTime 
     */
    public function getMoneyTransferDate()
    {
        return $this->moneyTransferDate;
    }

    /**
     * Set grantDueDate
     *
     * @param \DateTime $grantDueDate
     * @return Grant
     */
    public function setGrantDueDate($grantDueDate)
    {
        $this->grantDueDate = $grantDueDate;

        return $this;
    }

    /**
     * Get grantDueDate
     *
     * @return \DateTime 
     */
    public function getGrantDueDate()
    {
        return $this->grantDueDate;
    }

    /**
     * Set grantReportReceived
     *
     * @param boolean $grantReportReceived
     * @return Grant
     */
    public function setGrantReportReceived($grantReportReceived)
    {
        $this->grantReportReceived = $grantReportReceived;

        return $this;
    }

    /**
     * Get grantReportReceived
     *
     * @return boolean 
     */
    public function getGrantReportReceived()
    {
        return $this->grantReportReceived;
    }

    /**
     * Set grantTypeId
     *
     * @param integer $grantTypeId
     * @return Grant
     */
    public function setGrantTypeId($grantTypeId)
    {
        $this->grantTypeId = $grantTypeId;

        return $this;
    }

    /**
     * Get grantTypeId
     *
     * @return integer 
     */
    public function getGrantTypeId()
    {
        return $this->grantTypeId;
    }

    /**
     * Set amountTotal
     *
     * @param string $amountTotal
     * @return Grant
     */
    public function setAmountTotal($amountTotal)
    {
        $this->amountTotal = $amountTotal;

        return $this;
    }

    /**
     * Get amountTotal
     *
     * @return string 
     */
    public function getAmountTotal()
    {
        return $this->amountTotal;
    }

    /**
     * Set amountRequested
     *
     * @param string $amountRequested
     * @return Grant
     */
    public function setAmountRequested($amountRequested)
    {
        $this->amountRequested = $amountRequested;

        return $this;
    }

    /**
     * Get amountRequested
     *
     * @return string 
     */
    public function getAmountRequested()
    {
        return $this->amountRequested;
    }

    /**
     * Set amountGranted
     *
     * @param string $amountGranted
     * @return Grant
     */
    public function setAmountGranted($amountGranted)
    {
        $this->amountGranted = $amountGranted;

        return $this;
    }

    /**
     * Get amountGranted
     *
     * @return string 
     */
    public function getAmountGranted()
    {
        return $this->amountGranted;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Grant
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
     * Set rationale
     *
     * @param string $rationale
     * @return Grant
     */
    public function setRationale($rationale)
    {
        $this->rationale = $rationale;

        return $this;
    }

    /**
     * Get rationale
     *
     * @return string 
     */
    public function getRationale()
    {
        return $this->rationale;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return Grant
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Grant
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
     * @return Grant
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
}
