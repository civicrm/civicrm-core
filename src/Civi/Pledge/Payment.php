<?php

namespace Civi\Pledge;

use Doctrine\ORM\Mapping as ORM;

/**
 * Payment
 *
 * @ORM\Table(name="civicrm_pledge_payment", indexes={@ORM\Index(name="index_contribution_pledge", columns={"contribution_id", "pledge_id"}), @ORM\Index(name="index_status", columns={"status_id"}), @ORM\Index(name="FK_civicrm_pledge_payment_pledge_id", columns={"pledge_id"}), @ORM\Index(name="IDX_D4276227FE5E5FBD", columns={"contribution_id"})})
 * @ORM\Entity
 */
class Payment extends \Civi\Core\Entity
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
     * @ORM\Column(name="scheduled_amount", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $scheduledAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="actual_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $actualAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="scheduled_date", type="datetime", nullable=false)
     */
    private $scheduledDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="reminder_date", type="datetime", nullable=true)
     */
    private $reminderDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="reminder_count", type="integer", nullable=true)
     */
    private $reminderCount = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=true)
     */
    private $statusId;

    /**
     * @var \Civi\Pledge\Pledge
     *
     * @ORM\ManyToOne(targetEntity="Civi\Pledge\Pledge")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pledge_id", referencedColumnName="id")
     * })
     */
    private $pledge;

    /**
     * @var \Civi\Contribute\Contribution
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\Contribution")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contribution_id", referencedColumnName="id")
     * })
     */
    private $contribution;



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
     * Set scheduledAmount
     *
     * @param string $scheduledAmount
     * @return Payment
     */
    public function setScheduledAmount($scheduledAmount)
    {
        $this->scheduledAmount = $scheduledAmount;

        return $this;
    }

    /**
     * Get scheduledAmount
     *
     * @return string 
     */
    public function getScheduledAmount()
    {
        return $this->scheduledAmount;
    }

    /**
     * Set actualAmount
     *
     * @param string $actualAmount
     * @return Payment
     */
    public function setActualAmount($actualAmount)
    {
        $this->actualAmount = $actualAmount;

        return $this;
    }

    /**
     * Get actualAmount
     *
     * @return string 
     */
    public function getActualAmount()
    {
        return $this->actualAmount;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Payment
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
     * Set scheduledDate
     *
     * @param \DateTime $scheduledDate
     * @return Payment
     */
    public function setScheduledDate($scheduledDate)
    {
        $this->scheduledDate = $scheduledDate;

        return $this;
    }

    /**
     * Get scheduledDate
     *
     * @return \DateTime 
     */
    public function getScheduledDate()
    {
        return $this->scheduledDate;
    }

    /**
     * Set reminderDate
     *
     * @param \DateTime $reminderDate
     * @return Payment
     */
    public function setReminderDate($reminderDate)
    {
        $this->reminderDate = $reminderDate;

        return $this;
    }

    /**
     * Get reminderDate
     *
     * @return \DateTime 
     */
    public function getReminderDate()
    {
        return $this->reminderDate;
    }

    /**
     * Set reminderCount
     *
     * @param integer $reminderCount
     * @return Payment
     */
    public function setReminderCount($reminderCount)
    {
        $this->reminderCount = $reminderCount;

        return $this;
    }

    /**
     * Get reminderCount
     *
     * @return integer 
     */
    public function getReminderCount()
    {
        return $this->reminderCount;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return Payment
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
     * Set pledge
     *
     * @param \Civi\Pledge\Pledge $pledge
     * @return Payment
     */
    public function setPledge(\Civi\Pledge\Pledge $pledge = null)
    {
        $this->pledge = $pledge;

        return $this;
    }

    /**
     * Get pledge
     *
     * @return \Civi\Pledge\Pledge 
     */
    public function getPledge()
    {
        return $this->pledge;
    }

    /**
     * Set contribution
     *
     * @param \Civi\Contribute\Contribution $contribution
     * @return Payment
     */
    public function setContribution(\Civi\Contribute\Contribution $contribution = null)
    {
        $this->contribution = $contribution;

        return $this;
    }

    /**
     * Get contribution
     *
     * @return \Civi\Contribute\Contribution 
     */
    public function getContribution()
    {
        return $this->contribution;
    }
}
