<?php

namespace Civi\CCase;

use Doctrine\ORM\Mapping as ORM;

/**
 * CCase
 *
 * @ORM\Table(name="civicrm_case", indexes={@ORM\Index(name="index_case_type_id", columns={"case_type_id"}), @ORM\Index(name="index_is_deleted", columns={"is_deleted"})})
 * @ORM\Entity
 */
class CCase extends \Civi\Core\Entity
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
     * @ORM\Column(name="case_type_id", type="string", length=128, nullable=false)
     */
    private $caseTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=128, nullable=true)
     */
    private $subject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="date", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    private $endDate;

    /**
     * @var string
     *
     * @ORM\Column(name="details", type="text", nullable=true)
     */
    private $details;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=false)
     */
    private $statusId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=true)
     */
    private $isDeleted = '0';



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
     * Set caseTypeId
     *
     * @param string $caseTypeId
     * @return CCase
     */
    public function setCaseTypeId($caseTypeId)
    {
        $this->caseTypeId = $caseTypeId;

        return $this;
    }

    /**
     * Get caseTypeId
     *
     * @return string 
     */
    public function getCaseTypeId()
    {
        return $this->caseTypeId;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return CCase
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return CCase
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
     * @return CCase
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
     * Set details
     *
     * @param string $details
     * @return CCase
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details
     *
     * @return string 
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return CCase
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
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return CCase
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }
}
