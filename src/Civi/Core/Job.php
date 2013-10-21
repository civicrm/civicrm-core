<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Job
 *
 * @ORM\Table(name="civicrm_job", indexes={@ORM\Index(name="FK_civicrm_job_domain_id", columns={"domain_id"})})
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
     * @var string
     *
     * @ORM\Column(name="run_frequency", type="string", nullable=true)
     */
    private $runFrequency = 'Daily';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_run", type="datetime", nullable=true)
     */
    private $lastRun;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="api_entity", type="string", length=255, nullable=true)
     */
    private $apiEntity;

    /**
     * @var string
     *
     * @ORM\Column(name="api_action", type="string", length=255, nullable=true)
     */
    private $apiAction;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=true)
     */
    private $parameters;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var \Civi\Core\Domain
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Domain")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * })
     */
    private $domain;



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
     * Set runFrequency
     *
     * @param string $runFrequency
     * @return Job
     */
    public function setRunFrequency($runFrequency)
    {
        $this->runFrequency = $runFrequency;

        return $this;
    }

    /**
     * Get runFrequency
     *
     * @return string 
     */
    public function getRunFrequency()
    {
        return $this->runFrequency;
    }

    /**
     * Set lastRun
     *
     * @param \DateTime $lastRun
     * @return Job
     */
    public function setLastRun($lastRun)
    {
        $this->lastRun = $lastRun;

        return $this;
    }

    /**
     * Get lastRun
     *
     * @return \DateTime 
     */
    public function getLastRun()
    {
        return $this->lastRun;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Job
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Job
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set apiEntity
     *
     * @param string $apiEntity
     * @return Job
     */
    public function setApiEntity($apiEntity)
    {
        $this->apiEntity = $apiEntity;

        return $this;
    }

    /**
     * Get apiEntity
     *
     * @return string 
     */
    public function getApiEntity()
    {
        return $this->apiEntity;
    }

    /**
     * Set apiAction
     *
     * @param string $apiAction
     * @return Job
     */
    public function setApiAction($apiAction)
    {
        $this->apiAction = $apiAction;

        return $this;
    }

    /**
     * Get apiAction
     *
     * @return string 
     */
    public function getApiAction()
    {
        return $this->apiAction;
    }

    /**
     * Set parameters
     *
     * @param string $parameters
     * @return Job
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get parameters
     *
     * @return string 
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Job
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
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return Job
     */
    public function setDomain(\Civi\Core\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \Civi\Core\Domain 
     */
    public function getDomain()
    {
        return $this->domain;
    }
}
