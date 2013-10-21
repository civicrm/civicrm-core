<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Menu
 *
 * @ORM\Table(name="civicrm_menu", uniqueConstraints={@ORM\UniqueConstraint(name="UI_path_domain_id", columns={"path", "domain_id"})}, indexes={@ORM\Index(name="FK_civicrm_menu_domain_id", columns={"domain_id"}), @ORM\Index(name="FK_civicrm_menu_component_id", columns={"component_id"})})
 * @ORM\Entity
 */
class Menu extends \Civi\Core\Entity
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
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="path_arguments", type="text", nullable=true)
     */
    private $pathArguments;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="access_callback", type="string", length=255, nullable=true)
     */
    private $accessCallback;

    /**
     * @var string
     *
     * @ORM\Column(name="access_arguments", type="text", nullable=true)
     */
    private $accessArguments;

    /**
     * @var string
     *
     * @ORM\Column(name="page_callback", type="string", length=255, nullable=true)
     */
    private $pageCallback;

    /**
     * @var string
     *
     * @ORM\Column(name="page_arguments", type="text", nullable=true)
     */
    private $pageArguments;

    /**
     * @var string
     *
     * @ORM\Column(name="breadcrumb", type="text", nullable=true)
     */
    private $breadcrumb;

    /**
     * @var string
     *
     * @ORM\Column(name="return_url", type="string", length=255, nullable=true)
     */
    private $returnUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="return_url_args", type="string", length=255, nullable=true)
     */
    private $returnUrlArgs;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_public", type="boolean", nullable=true)
     */
    private $isPublic;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_exposed", type="boolean", nullable=true)
     */
    private $isExposed;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_ssl", type="boolean", nullable=true)
     */
    private $isSsl;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="page_type", type="integer", nullable=false)
     */
    private $pageType = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="skipBreadcrumb", type="boolean", nullable=true)
     */
    private $skipbreadcrumb;

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
     * @var \Civi\Core\Component
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="component_id", referencedColumnName="id")
     * })
     */
    private $component;



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
     * Set path
     *
     * @param string $path
     * @return Menu
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set pathArguments
     *
     * @param string $pathArguments
     * @return Menu
     */
    public function setPathArguments($pathArguments)
    {
        $this->pathArguments = $pathArguments;

        return $this;
    }

    /**
     * Get pathArguments
     *
     * @return string 
     */
    public function getPathArguments()
    {
        return $this->pathArguments;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Menu
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
     * Set accessCallback
     *
     * @param string $accessCallback
     * @return Menu
     */
    public function setAccessCallback($accessCallback)
    {
        $this->accessCallback = $accessCallback;

        return $this;
    }

    /**
     * Get accessCallback
     *
     * @return string 
     */
    public function getAccessCallback()
    {
        return $this->accessCallback;
    }

    /**
     * Set accessArguments
     *
     * @param string $accessArguments
     * @return Menu
     */
    public function setAccessArguments($accessArguments)
    {
        $this->accessArguments = $accessArguments;

        return $this;
    }

    /**
     * Get accessArguments
     *
     * @return string 
     */
    public function getAccessArguments()
    {
        return $this->accessArguments;
    }

    /**
     * Set pageCallback
     *
     * @param string $pageCallback
     * @return Menu
     */
    public function setPageCallback($pageCallback)
    {
        $this->pageCallback = $pageCallback;

        return $this;
    }

    /**
     * Get pageCallback
     *
     * @return string 
     */
    public function getPageCallback()
    {
        return $this->pageCallback;
    }

    /**
     * Set pageArguments
     *
     * @param string $pageArguments
     * @return Menu
     */
    public function setPageArguments($pageArguments)
    {
        $this->pageArguments = $pageArguments;

        return $this;
    }

    /**
     * Get pageArguments
     *
     * @return string 
     */
    public function getPageArguments()
    {
        return $this->pageArguments;
    }

    /**
     * Set breadcrumb
     *
     * @param string $breadcrumb
     * @return Menu
     */
    public function setBreadcrumb($breadcrumb)
    {
        $this->breadcrumb = $breadcrumb;

        return $this;
    }

    /**
     * Get breadcrumb
     *
     * @return string 
     */
    public function getBreadcrumb()
    {
        return $this->breadcrumb;
    }

    /**
     * Set returnUrl
     *
     * @param string $returnUrl
     * @return Menu
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    /**
     * Get returnUrl
     *
     * @return string 
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * Set returnUrlArgs
     *
     * @param string $returnUrlArgs
     * @return Menu
     */
    public function setReturnUrlArgs($returnUrlArgs)
    {
        $this->returnUrlArgs = $returnUrlArgs;

        return $this;
    }

    /**
     * Get returnUrlArgs
     *
     * @return string 
     */
    public function getReturnUrlArgs()
    {
        return $this->returnUrlArgs;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Menu
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
     * Set isPublic
     *
     * @param boolean $isPublic
     * @return Menu
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic
     *
     * @return boolean 
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set isExposed
     *
     * @param boolean $isExposed
     * @return Menu
     */
    public function setIsExposed($isExposed)
    {
        $this->isExposed = $isExposed;

        return $this;
    }

    /**
     * Get isExposed
     *
     * @return boolean 
     */
    public function getIsExposed()
    {
        return $this->isExposed;
    }

    /**
     * Set isSsl
     *
     * @param boolean $isSsl
     * @return Menu
     */
    public function setIsSsl($isSsl)
    {
        $this->isSsl = $isSsl;

        return $this;
    }

    /**
     * Get isSsl
     *
     * @return boolean 
     */
    public function getIsSsl()
    {
        return $this->isSsl;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return Menu
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
     * Set type
     *
     * @param integer $type
     * @return Menu
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set pageType
     *
     * @param integer $pageType
     * @return Menu
     */
    public function setPageType($pageType)
    {
        $this->pageType = $pageType;

        return $this;
    }

    /**
     * Get pageType
     *
     * @return integer 
     */
    public function getPageType()
    {
        return $this->pageType;
    }

    /**
     * Set skipbreadcrumb
     *
     * @param boolean $skipbreadcrumb
     * @return Menu
     */
    public function setSkipbreadcrumb($skipbreadcrumb)
    {
        $this->skipbreadcrumb = $skipbreadcrumb;

        return $this;
    }

    /**
     * Get skipbreadcrumb
     *
     * @return boolean 
     */
    public function getSkipbreadcrumb()
    {
        return $this->skipbreadcrumb;
    }

    /**
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return Menu
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
     * Set component
     *
     * @param \Civi\Core\Component $component
     * @return Menu
     */
    public function setComponent(\Civi\Core\Component $component = null)
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get component
     *
     * @return \Civi\Core\Component 
     */
    public function getComponent()
    {
        return $this->component;
    }
}
