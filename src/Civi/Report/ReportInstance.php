<?php

namespace Civi\Report;

use Doctrine\ORM\Mapping as ORM;

/**
 * ReportInstance
 *
 * @ORM\Table(name="civicrm_report_instance", indexes={@ORM\Index(name="FK_civicrm_report_instance_domain_id", columns={"domain_id"}), @ORM\Index(name="FK_civicrm_report_instance_navigation_id", columns={"navigation_id"}), @ORM\Index(name="FK_civicrm_report_instance_drilldown_id", columns={"drilldown_id"})})
 * @ORM\Entity
 */
class ReportInstance extends \Civi\Core\Entity
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
     * @ORM\Column(name="report_id", type="string", length=64, nullable=false)
     */
    private $reportId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="args", type="string", length=255, nullable=true)
     */
    private $args;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="permission", type="string", length=255, nullable=true)
     */
    private $permission;

    /**
     * @var string
     *
     * @ORM\Column(name="grouprole", type="string", length=1024, nullable=true)
     */
    private $grouprole;

    /**
     * @var string
     *
     * @ORM\Column(name="form_values", type="text", nullable=true)
     */
    private $formValues;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="email_subject", type="string", length=255, nullable=true)
     */
    private $emailSubject;

    /**
     * @var string
     *
     * @ORM\Column(name="email_to", type="text", nullable=true)
     */
    private $emailTo;

    /**
     * @var string
     *
     * @ORM\Column(name="email_cc", type="text", nullable=true)
     */
    private $emailCc;

    /**
     * @var string
     *
     * @ORM\Column(name="header", type="text", nullable=true)
     */
    private $header;

    /**
     * @var string
     *
     * @ORM\Column(name="footer", type="text", nullable=true)
     */
    private $footer;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved = '0';

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
     * @var \Civi\Core\Navigation
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Navigation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="navigation_id", referencedColumnName="id")
     * })
     */
    private $navigation;

    /**
     * @var \Civi\Report\ReportInstance
     *
     * @ORM\ManyToOne(targetEntity="Civi\Report\ReportInstance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="drilldown_id", referencedColumnName="id")
     * })
     */
    private $drilldown;



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
     * @return ReportInstance
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
     * Set reportId
     *
     * @param string $reportId
     * @return ReportInstance
     */
    public function setReportId($reportId)
    {
        $this->reportId = $reportId;

        return $this;
    }

    /**
     * Get reportId
     *
     * @return string 
     */
    public function getReportId()
    {
        return $this->reportId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ReportInstance
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
     * Set args
     *
     * @param string $args
     * @return ReportInstance
     */
    public function setArgs($args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * Get args
     *
     * @return string 
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ReportInstance
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
     * Set permission
     *
     * @param string $permission
     * @return ReportInstance
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission
     *
     * @return string 
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Set grouprole
     *
     * @param string $grouprole
     * @return ReportInstance
     */
    public function setGrouprole($grouprole)
    {
        $this->grouprole = $grouprole;

        return $this;
    }

    /**
     * Get grouprole
     *
     * @return string 
     */
    public function getGrouprole()
    {
        return $this->grouprole;
    }

    /**
     * Set formValues
     *
     * @param string $formValues
     * @return ReportInstance
     */
    public function setFormValues($formValues)
    {
        $this->formValues = $formValues;

        return $this;
    }

    /**
     * Get formValues
     *
     * @return string 
     */
    public function getFormValues()
    {
        return $this->formValues;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return ReportInstance
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
     * Set emailSubject
     *
     * @param string $emailSubject
     * @return ReportInstance
     */
    public function setEmailSubject($emailSubject)
    {
        $this->emailSubject = $emailSubject;

        return $this;
    }

    /**
     * Get emailSubject
     *
     * @return string 
     */
    public function getEmailSubject()
    {
        return $this->emailSubject;
    }

    /**
     * Set emailTo
     *
     * @param string $emailTo
     * @return ReportInstance
     */
    public function setEmailTo($emailTo)
    {
        $this->emailTo = $emailTo;

        return $this;
    }

    /**
     * Get emailTo
     *
     * @return string 
     */
    public function getEmailTo()
    {
        return $this->emailTo;
    }

    /**
     * Set emailCc
     *
     * @param string $emailCc
     * @return ReportInstance
     */
    public function setEmailCc($emailCc)
    {
        $this->emailCc = $emailCc;

        return $this;
    }

    /**
     * Get emailCc
     *
     * @return string 
     */
    public function getEmailCc()
    {
        return $this->emailCc;
    }

    /**
     * Set header
     *
     * @param string $header
     * @return ReportInstance
     */
    public function setHeader($header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Get header
     *
     * @return string 
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set footer
     *
     * @param string $footer
     * @return ReportInstance
     */
    public function setFooter($footer)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Get footer
     *
     * @return string 
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return ReportInstance
     */
    public function setIsReserved($isReserved)
    {
        $this->isReserved = $isReserved;

        return $this;
    }

    /**
     * Get isReserved
     *
     * @return boolean 
     */
    public function getIsReserved()
    {
        return $this->isReserved;
    }

    /**
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return ReportInstance
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

    /**
     * Set navigation
     *
     * @param \Civi\Core\Navigation $navigation
     * @return ReportInstance
     */
    public function setNavigation(\Civi\Core\Navigation $navigation = null)
    {
        $this->navigation = $navigation;

        return $this;
    }

    /**
     * Get navigation
     *
     * @return \Civi\Core\Navigation 
     */
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * Set drilldown
     *
     * @param \Civi\Report\ReportInstance $drilldown
     * @return ReportInstance
     */
    public function setDrilldown(\Civi\Report\ReportInstance $drilldown = null)
    {
        $this->drilldown = $drilldown;

        return $this;
    }

    /**
     * Get drilldown
     *
     * @return \Civi\Report\ReportInstance 
     */
    public function getDrilldown()
    {
        return $this->drilldown;
    }
}
