<?php

namespace Civi\Price;

use Doctrine\ORM\Mapping as ORM;

/**
 * Set
 *
 * @ORM\Table(name="civicrm_price_set", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name", columns={"name"})}, indexes={@ORM\Index(name="FK_civicrm_price_set_domain_id", columns={"domain_id"}), @ORM\Index(name="FK_civicrm_price_set_financial_type_id", columns={"financial_type_id"})})
 * @ORM\Entity
 */
class Set extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="help_pre", type="text", nullable=true)
     */
    private $helpPre;

    /**
     * @var string
     *
     * @ORM\Column(name="help_post", type="text", nullable=true)
     */
    private $helpPost;

    /**
     * @var string
     *
     * @ORM\Column(name="javascript", type="string", length=64, nullable=true)
     */
    private $javascript;

    /**
     * @var string
     *
     * @ORM\Column(name="extends", type="string", length=255, nullable=false)
     */
    private $extends;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_quick_config", type="boolean", nullable=true)
     */
    private $isQuickConfig = '0';

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
     * @var \Civi\Financial\Type
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Type")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="financial_type_id", referencedColumnName="id")
     * })
     */
    private $financialType;

    /**
     * 
     * @ORM\OneToMany(targetEntity="Civi\Price\Field", mappedBy="priceSet", cascade={"persist"})
     */
    private $priceFields;


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
     * Set name
     *
     * @param string $name
     * @return Set
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
     * Set title
     *
     * @param string $title
     * @return Set
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return Set
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
     * Set helpPre
     *
     * @param string $helpPre
     * @return Set
     */
    public function setHelpPre($helpPre)
    {
        $this->helpPre = $helpPre;

        return $this;
    }

    /**
     * Get helpPre
     *
     * @return string 
     */
    public function getHelpPre()
    {
        return $this->helpPre;
    }

    /**
     * Set helpPost
     *
     * @param string $helpPost
     * @return Set
     */
    public function setHelpPost($helpPost)
    {
        $this->helpPost = $helpPost;

        return $this;
    }

    /**
     * Get helpPost
     *
     * @return string 
     */
    public function getHelpPost()
    {
        return $this->helpPost;
    }

    /**
     * Set javascript
     *
     * @param string $javascript
     * @return Set
     */
    public function setJavascript($javascript)
    {
        $this->javascript = $javascript;

        return $this;
    }

    /**
     * Get javascript
     *
     * @return string 
     */
    public function getJavascript()
    {
        return $this->javascript;
    }

    /**
     * Set extends
     *
     * @param string $extends
     * @return Set
     */
    public function setExtends($extends)
    {
        $this->extends = $extends;

        return $this;
    }

    /**
     * Get extends
     *
     * @return string 
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * Set isQuickConfig
     *
     * @param boolean $isQuickConfig
     * @return Set
     */
    public function setIsQuickConfig($isQuickConfig)
    {
        $this->isQuickConfig = $isQuickConfig;

        return $this;
    }

    /**
     * Get isQuickConfig
     *
     * @return boolean 
     */
    public function getIsQuickConfig()
    {
        return $this->isQuickConfig;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return Set
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
     * @return Set
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
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return Set
     */
    public function setFinancialType(\Civi\Financial\Type $financialType = null)
    {
        $this->financialType = $financialType;

        return $this;
    }

    /**
     * Get financialType
     *
     * @return \Civi\Financial\Type 
     */
    public function getFinancialType()
    {
        return $this->financialType;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->priceFields = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add priceFields
     *
     * @param \Civi\Price\Field $priceFields
     * @return Set
     */
    public function addPriceField(\Civi\Price\Field $priceFields)
    {
        $priceFields->setPriceSet($this);
        $this->priceFields[] = $priceFields;

        return $this;
    }

    /**
     * Remove priceFields
     *
     * @param \Civi\Price\Field $priceFields
     */
    public function removePriceField(\Civi\Price\Field $priceFields)
    {
        $this->priceFields->removeElement($priceFields);
    }

    /**
     * Get priceFields
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPriceFields()
    {
        return $this->priceFields;
    }
}
