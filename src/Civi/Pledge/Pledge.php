<?php

namespace Civi\Pledge;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pledge
 *
 * @ORM\Table(name="civicrm_pledge", indexes={@ORM\Index(name="index_status", columns={"status_id"}), @ORM\Index(name="FK_civicrm_pledge_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_pledge_financial_type_id", columns={"financial_type_id"}), @ORM\Index(name="FK_civicrm_pledge_contribution_page_id", columns={"contribution_page_id"}), @ORM\Index(name="FK_civicrm_pledge_honor_contact_id", columns={"honor_contact_id"}), @ORM\Index(name="FK_civicrm_pledge_campaign_id", columns={"campaign_id"})})
 * @ORM\Entity
 */
class Pledge extends \Civi\Core\Entity
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
     * @ORM\Column(name="amount", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="original_installment_amount", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $originalInstallmentAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="frequency_unit", type="string", nullable=true)
     */
    private $frequencyUnit = 'month';

    /**
     * @var integer
     *
     * @ORM\Column(name="frequency_interval", type="integer", nullable=false)
     */
    private $frequencyInterval = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="frequency_day", type="integer", nullable=false)
     */
    private $frequencyDay = '3';

    /**
     * @var integer
     *
     * @ORM\Column(name="installments", type="integer", nullable=true)
     */
    private $installments = '1';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="acknowledge_date", type="datetime", nullable=true)
     */
    private $acknowledgeDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_date", type="datetime", nullable=true)
     */
    private $modifiedDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cancel_date", type="datetime", nullable=true)
     */
    private $cancelDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="honor_type_id", type="integer", nullable=true)
     */
    private $honorTypeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_reminders", type="integer", nullable=true)
     */
    private $maxReminders = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="initial_reminder_day", type="integer", nullable=true)
     */
    private $initialReminderDay = '5';

    /**
     * @var integer
     *
     * @ORM\Column(name="additional_reminder_day", type="integer", nullable=true)
     */
    private $additionalReminderDay = '5';

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=true)
     */
    private $statusId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_test", type="boolean", nullable=true)
     */
    private $isTest = '0';

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
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="honor_contact_id", referencedColumnName="id")
     * })
     */
    private $honorContact;

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
     * Set amount
     *
     * @param string $amount
     * @return Pledge
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set originalInstallmentAmount
     *
     * @param string $originalInstallmentAmount
     * @return Pledge
     */
    public function setOriginalInstallmentAmount($originalInstallmentAmount)
    {
        $this->originalInstallmentAmount = $originalInstallmentAmount;

        return $this;
    }

    /**
     * Get originalInstallmentAmount
     *
     * @return string 
     */
    public function getOriginalInstallmentAmount()
    {
        return $this->originalInstallmentAmount;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Pledge
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
     * Set frequencyUnit
     *
     * @param string $frequencyUnit
     * @return Pledge
     */
    public function setFrequencyUnit($frequencyUnit)
    {
        $this->frequencyUnit = $frequencyUnit;

        return $this;
    }

    /**
     * Get frequencyUnit
     *
     * @return string 
     */
    public function getFrequencyUnit()
    {
        return $this->frequencyUnit;
    }

    /**
     * Set frequencyInterval
     *
     * @param integer $frequencyInterval
     * @return Pledge
     */
    public function setFrequencyInterval($frequencyInterval)
    {
        $this->frequencyInterval = $frequencyInterval;

        return $this;
    }

    /**
     * Get frequencyInterval
     *
     * @return integer 
     */
    public function getFrequencyInterval()
    {
        return $this->frequencyInterval;
    }

    /**
     * Set frequencyDay
     *
     * @param integer $frequencyDay
     * @return Pledge
     */
    public function setFrequencyDay($frequencyDay)
    {
        $this->frequencyDay = $frequencyDay;

        return $this;
    }

    /**
     * Get frequencyDay
     *
     * @return integer 
     */
    public function getFrequencyDay()
    {
        return $this->frequencyDay;
    }

    /**
     * Set installments
     *
     * @param integer $installments
     * @return Pledge
     */
    public function setInstallments($installments)
    {
        $this->installments = $installments;

        return $this;
    }

    /**
     * Get installments
     *
     * @return integer 
     */
    public function getInstallments()
    {
        return $this->installments;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Pledge
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
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return Pledge
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get createDate
     *
     * @return \DateTime 
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set acknowledgeDate
     *
     * @param \DateTime $acknowledgeDate
     * @return Pledge
     */
    public function setAcknowledgeDate($acknowledgeDate)
    {
        $this->acknowledgeDate = $acknowledgeDate;

        return $this;
    }

    /**
     * Get acknowledgeDate
     *
     * @return \DateTime 
     */
    public function getAcknowledgeDate()
    {
        return $this->acknowledgeDate;
    }

    /**
     * Set modifiedDate
     *
     * @param \DateTime $modifiedDate
     * @return Pledge
     */
    public function setModifiedDate($modifiedDate)
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }

    /**
     * Get modifiedDate
     *
     * @return \DateTime 
     */
    public function getModifiedDate()
    {
        return $this->modifiedDate;
    }

    /**
     * Set cancelDate
     *
     * @param \DateTime $cancelDate
     * @return Pledge
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
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Pledge
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
     * Set honorTypeId
     *
     * @param integer $honorTypeId
     * @return Pledge
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
     * Set maxReminders
     *
     * @param integer $maxReminders
     * @return Pledge
     */
    public function setMaxReminders($maxReminders)
    {
        $this->maxReminders = $maxReminders;

        return $this;
    }

    /**
     * Get maxReminders
     *
     * @return integer 
     */
    public function getMaxReminders()
    {
        return $this->maxReminders;
    }

    /**
     * Set initialReminderDay
     *
     * @param integer $initialReminderDay
     * @return Pledge
     */
    public function setInitialReminderDay($initialReminderDay)
    {
        $this->initialReminderDay = $initialReminderDay;

        return $this;
    }

    /**
     * Get initialReminderDay
     *
     * @return integer 
     */
    public function getInitialReminderDay()
    {
        return $this->initialReminderDay;
    }

    /**
     * Set additionalReminderDay
     *
     * @param integer $additionalReminderDay
     * @return Pledge
     */
    public function setAdditionalReminderDay($additionalReminderDay)
    {
        $this->additionalReminderDay = $additionalReminderDay;

        return $this;
    }

    /**
     * Get additionalReminderDay
     *
     * @return integer 
     */
    public function getAdditionalReminderDay()
    {
        return $this->additionalReminderDay;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return Pledge
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
     * Set isTest
     *
     * @param boolean $isTest
     * @return Pledge
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Pledge
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
     * @return Pledge
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
     * @return Pledge
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
     * Set honorContact
     *
     * @param \Civi\Contact\Contact $honorContact
     * @return Pledge
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
     * Set campaign
     *
     * @param \Civi\Campaign\Campaign $campaign
     * @return Pledge
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
