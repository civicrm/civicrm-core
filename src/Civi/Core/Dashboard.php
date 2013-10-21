<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dashboard
 *
 * @ORM\Table(name="civicrm_dashboard", indexes={@ORM\Index(name="FK_civicrm_dashboard_domain_id", columns={"domain_id"})})
 * @ORM\Entity
 */
class Dashboard extends \Civi\Core\Entity
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
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="permission", type="string", length=255, nullable=true)
     */
    private $permission;

    /**
     * @var string
     *
     * @ORM\Column(name="permission_operator", type="string", length=3, nullable=true)
     */
    private $permissionOperator;

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
     * @var string
     *
     * @ORM\Column(name="fullscreen_url", type="string", length=255, nullable=true)
     */
    private $fullscreenUrl;

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
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=true)
     */
    private $weight = '0';

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
     * Set label
     *
     * @param string $label
     * @return Dashboard
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Dashboard
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set permission
     *
     * @param string $permission
     * @return Dashboard
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
     * Set permissionOperator
     *
     * @param string $permissionOperator
     * @return Dashboard
     */
    public function setPermissionOperator($permissionOperator)
    {
        $this->permissionOperator = $permissionOperator;

        return $this;
    }

    /**
     * Get permissionOperator
     *
     * @return string 
     */
    public function getPermissionOperator()
    {
        return $this->permissionOperator;
    }

    /**
     * Set columnNo
     *
     * @param boolean $columnNo
     * @return Dashboard
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
     * @return Dashboard
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
     * Set fullscreenUrl
     *
     * @param string $fullscreenUrl
     * @return Dashboard
     */
    public function setFullscreenUrl($fullscreenUrl)
    {
        $this->fullscreenUrl = $fullscreenUrl;

        return $this;
    }

    /**
     * Get fullscreenUrl
     *
     * @return string 
     */
    public function getFullscreenUrl()
    {
        return $this->fullscreenUrl;
    }

    /**
     * Set isFullscreen
     *
     * @param boolean $isFullscreen
     * @return Dashboard
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
     * @return Dashboard
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
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return Dashboard
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
     * Set weight
     *
     * @param integer $weight
     * @return Dashboard
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
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return Dashboard
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
