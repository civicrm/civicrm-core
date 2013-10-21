<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Job
 *
 * @ORM\Table(name="civicrm_mailing_job", indexes={@ORM\Index(name="FK_civicrm_mailing_job_mailing_id", columns={"mailing_id"}), @ORM\Index(name="FK_civicrm_mailing_job_parent_id", columns={"parent_id"})})
 * @ORM\Entity
 */
class Job extends \Civi\Core\Entity
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
     * @ORM\Column(name="scheduled_date", type="datetime", nullable=true)
     */
    private $scheduledDate;

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
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=true)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_test", type="boolean", nullable=true)
     */
    private $isTest = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="job_type", type="string", length=255, nullable=true)
     */
    private $jobType;

    /**
     * @var integer
     *
     * @ORM\Column(name="job_offset", type="integer", nullable=true)
     */
    private $jobOffset = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="job_limit", type="integer", nullable=true)
     */
    private $jobLimit = '0';

    /**
     * @var \Civi\Mailing\Mailing
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Mailing")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mailing_id", referencedColumnName="id")
     * })
     */
    private $mailing;

    /**
     * @var \Civi\Mailing\Job
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Job")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;



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
     * Set scheduledDate
     *
     * @param \DateTime $scheduledDate
     * @return Job
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Job
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
     * @return Job
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
     * Set status
     *
     * @param string $status
     * @return Job
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set isTest
     *
     * @param boolean $isTest
     * @return Job
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
     * Set jobType
     *
     * @param string $jobType
     * @return Job
     */
    public function setJobType($jobType)
    {
        $this->jobType = $jobType;

        return $this;
    }

    /**
     * Get jobType
     *
     * @return string 
     */
    public function getJobType()
    {
        return $this->jobType;
    }

    /**
     * Set jobOffset
     *
     * @param integer $jobOffset
     * @return Job
     */
    public function setJobOffset($jobOffset)
    {
        $this->jobOffset = $jobOffset;

        return $this;
    }

    /**
     * Get jobOffset
     *
     * @return integer 
     */
    public function getJobOffset()
    {
        return $this->jobOffset;
    }

    /**
     * Set jobLimit
     *
     * @param integer $jobLimit
     * @return Job
     */
    public function setJobLimit($jobLimit)
    {
        $this->jobLimit = $jobLimit;

        return $this;
    }

    /**
     * Get jobLimit
     *
     * @return integer 
     */
    public function getJobLimit()
    {
        return $this->jobLimit;
    }

    /**
     * Set mailing
     *
     * @param \Civi\Mailing\Mailing $mailing
     * @return Job
     */
    public function setMailing(\Civi\Mailing\Mailing $mailing = null)
    {
        $this->mailing = $mailing;

        return $this;
    }

    /**
     * Get mailing
     *
     * @return \Civi\Mailing\Mailing 
     */
    public function getMailing()
    {
        return $this->mailing;
    }

    /**
     * Set parent
     *
     * @param \Civi\Mailing\Job $parent
     * @return Job
     */
    public function setParent(\Civi\Mailing\Job $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Civi\Mailing\Job 
     */
    public function getParent()
    {
        return $this->parent;
    }
}
