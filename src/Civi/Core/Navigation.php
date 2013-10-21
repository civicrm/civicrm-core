<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Navigation
 *
 * @ORM\Table(name="civicrm_navigation", indexes={@ORM\Index(name="FK_civicrm_navigation_domain_id", columns={"domain_id"}), @ORM\Index(name="FK_civicrm_navigation_parent_id", columns={"parent_id"})})
 * @ORM\Entity
 */
class Navigation extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

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
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_separator", type="boolean", nullable=true)
     */
    private $hasSeparator;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=true)
     */
    private $weight;

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
     * Set label
     *
     * @param string $label
     * @return Navigation
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
     * Set name
     *
     * @param string $name
     * @return Navigation
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
     * Set url
     *
     * @param string $url
     * @return Navigation
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
     * @return Navigation
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
     * @return Navigation
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return Navigation
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
     * Set hasSeparator
     *
     * @param boolean $hasSeparator
     * @return Navigation
     */
    public function setHasSeparator($hasSeparator)
    {
        $this->hasSeparator = $hasSeparator;

        return $this;
    }

    /**
     * Get hasSeparator
     *
     * @return boolean 
     */
    public function getHasSeparator()
    {
        return $this->hasSeparator;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return Navigation
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
     * @return Navigation
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
     * Set parent
     *
     * @param \Civi\Core\Navigation $parent
     * @return Navigation
     */
    public function setParent(\Civi\Core\Navigation $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Civi\Core\Navigation 
     */
    public function getParent()
    {
        return $this->parent;
    }
}
