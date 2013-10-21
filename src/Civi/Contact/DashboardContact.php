<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * DashboardContact
 *
 * @ORM\Table(name="civicrm_dashboard_contact", indexes={@ORM\Index(name="FK_civicrm_dashboard_contact_dashboard_id", columns={"dashboard_id"}), @ORM\Index(name="FK_civicrm_dashboard_contact_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class DashboardContact extends \Civi\Core\Entity
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
     * @var boolean
     *
     * @ORM\Column(name="column_no", type="boolean", nullable=true)
     */
    private $columnNo = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_minimized", type="boolean", nullable=true)
     */
    private $isMinimized = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_fullscreen", type="boolean", nullable=true)
     */
    private $isFullscreen = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=true)
     */
    private $weight = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var \Civi\Core\Dashboard
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Dashboard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="dashboard_id", referencedColumnName="id")
     * })
     */
    private $dashboard;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set columnNo
     *
     * @param boolean $columnNo
     * @return DashboardContact
     */
    public function setColumnNo($columnNo)
    {
        $this->columnNo = $columnNo;

        return $this;
    }

    /**
     * Get columnNo
     *
     * @return boolean 
     */
    public function getColumnNo()
    {
        return $this->columnNo;
    }

    /**
     * Set isMinimized
     *
     * @param boolean $isMinimized
     * @return DashboardContact
     */
    public function setIsMinimized($isMinimized)
    {
        $this->isMinimized = $isMinimized;

        return $this;
    }

    /**
     * Get isMinimized
     *
     * @return boolean 
     */
    public function getIsMinimized()
    {
        return $this->isMinimized;
    }

    /**
     * Set isFullscreen
     *
     * @param boolean $isFullscreen
     * @return DashboardContact
     */
    public function setIsFullscreen($isFullscreen)
    {
        $this->isFullscreen = $isFullscreen;

        return $this;
    }

    /**
     * Get isFullscreen
     *
     * @return boolean 
     */
    public function getIsFullscreen()
    {
        return $this->isFullscreen;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return DashboardContact
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
     * Set weight
     *
     * @param integer $weight
     * @return DashboardContact
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return DashboardContact
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return DashboardContact
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate
     *
     * @return \DateTime 
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set dashboard
     *
     * @param \Civi\Core\Dashboard $dashboard
     * @return DashboardContact
     */
    public function setDashboard(\Civi\Core\Dashboard $dashboard = null)
    {
        $this->dashboard = $dashboard;

        return $this;
    }

    /**
     * Get dashboard
     *
     * @return \Civi\Core\Dashboard 
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return DashboardContact
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
}
