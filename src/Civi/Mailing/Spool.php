<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Spool
 *
 * @ORM\Table(name="civicrm_mailing_spool", indexes={@ORM\Index(name="FK_civicrm_mailing_spool_job_id", columns={"job_id"})})
 * @ORM\Entity
 */
class Spool extends \Civi\Core\Entity
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
     * @ORM\Column(name="recipient_email", type="text", nullable=true)
     */
    private $recipientEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="headers", type="text", nullable=true)
     */
    private $headers;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added_at", type="datetime", nullable=true)
     */
    private $addedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="removed_at", type="datetime", nullable=true)
     */
    private $removedAt;

    /**
     * @var \Civi\Mailing\Job
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Job")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="job_id", referencedColumnName="id")
     * })
     */
    private $job;



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
     * Set recipientEmail
     *
     * @param string $recipientEmail
     * @return Spool
     */
    public function setRecipientEmail($recipientEmail)
    {
        $this->recipientEmail = $recipientEmail;

        return $this;
    }

    /**
     * Get recipientEmail
     *
     * @return string 
     */
    public function getRecipientEmail()
    {
        return $this->recipientEmail;
    }

    /**
     * Set headers
     *
     * @param string $headers
     * @return Spool
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get headers
     *
     * @return string 
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return Spool
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string 
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set addedAt
     *
     * @param \DateTime $addedAt
     * @return Spool
     */
    public function setAddedAt($addedAt)
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    /**
     * Get addedAt
     *
     * @return \DateTime 
     */
    public function getAddedAt()
    {
        return $this->addedAt;
    }

    /**
     * Set removedAt
     *
     * @param \DateTime $removedAt
     * @return Spool
     */
    public function setRemovedAt($removedAt)
    {
        $this->removedAt = $removedAt;

        return $this;
    }

    /**
     * Get removedAt
     *
     * @return \DateTime 
     */
    public function getRemovedAt()
    {
        return $this->removedAt;
    }

    /**
     * Set job
     *
     * @param \Civi\Mailing\Job $job
     * @return Spool
     */
    public function setJob(\Civi\Mailing\Job $job = null)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Get job
     *
     * @return \Civi\Mailing\Job 
     */
    public function getJob()
    {
        return $this->job;
    }
}
