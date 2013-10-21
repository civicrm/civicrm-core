<?php

namespace Civi\Price;

use Doctrine\ORM\Mapping as ORM;

/**
 * Field
 *
 * @ORM\Table(name="civicrm_price_field", indexes={@ORM\Index(name="index_name", columns={"name"}), @ORM\Index(name="FK_civicrm_price_field_price_set_id", columns={"price_set_id"})})
 * @ORM\Entity
 */
class Field extends \Civi\Core\Entity
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
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="html_type", type="string", nullable=false)
     */
    private $htmlType;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_enter_qty", type="boolean", nullable=true)
     */
    private $isEnterQty = '0';

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
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=true)
     */
    private $weight = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_display_amounts", type="boolean", nullable=true)
     */
    private $isDisplayAmounts = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="options_per_line", type="integer", nullable=true)
     */
    private $optionsPerLine = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_required", type="boolean", nullable=true)
     */
    private $isRequired = '1';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="active_on", type="datetime", nullable=true)
     */
    private $activeOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expire_on", type="datetime", nullable=true)
     */
    private $expireOn;

    /**
     * @var string
     *
     * @ORM\Column(name="javascript", type="string", length=255, nullable=true)
     */
    private $javascript;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility_id", type="integer", nullable=true)
     */
    private $visibilityId = '1';

    /**
     * @var \Civi\Price\Set
     *
     * @ORM\ManyToOne(targetEntity="Civi\Price\Set")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="price_set_id", referencedColumnName="id")
     * })
     */
    private $priceSet;



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
     * @return Field
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
     * Set label
     *
     * @param string $label
     * @return Field
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
     * Set htmlType
     *
     * @param string $htmlType
     * @return Field
     */
    public function setHtmlType($htmlType)
    {
        $this->htmlType = $htmlType;

        return $this;
    }

    /**
     * Get htmlType
     *
     * @return string 
     */
    public function getHtmlType()
    {
        return $this->htmlType;
    }

    /**
     * Set isEnterQty
     *
     * @param boolean $isEnterQty
     * @return Field
     */
    public function setIsEnterQty($isEnterQty)
    {
        $this->isEnterQty = $isEnterQty;

        return $this;
    }

    /**
     * Get isEnterQty
     *
     * @return boolean 
     */
    public function getIsEnterQty()
    {
        return $this->isEnterQty;
    }

    /**
     * Set helpPre
     *
     * @param string $helpPre
     * @return Field
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
     * @return Field
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
     * Set weight
     *
     * @param integer $weight
     * @return Field
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
     * Set isDisplayAmounts
     *
     * @param boolean $isDisplayAmounts
     * @return Field
     */
    public function setIsDisplayAmounts($isDisplayAmounts)
    {
        $this->isDisplayAmounts = $isDisplayAmounts;

        return $this;
    }

    /**
     * Get isDisplayAmounts
     *
     * @return boolean 
     */
    public function getIsDisplayAmounts()
    {
        return $this->isDisplayAmounts;
    }

    /**
     * Set optionsPerLine
     *
     * @param integer $optionsPerLine
     * @return Field
     */
    public function setOptionsPerLine($optionsPerLine)
    {
        $this->optionsPerLine = $optionsPerLine;

        return $this;
    }

    /**
     * Get optionsPerLine
     *
     * @return integer 
     */
    public function getOptionsPerLine()
    {
        return $this->optionsPerLine;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Field
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
     * Set isRequired
     *
     * @param boolean $isRequired
     * @return Field
     */
    public function setIsRequired($isRequired)
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * Get isRequired
     *
     * @return boolean 
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * Set activeOn
     *
     * @param \DateTime $activeOn
     * @return Field
     */
    public function setActiveOn($activeOn)
    {
        $this->activeOn = $activeOn;

        return $this;
    }

    /**
     * Get activeOn
     *
     * @return \DateTime 
     */
    public function getActiveOn()
    {
        return $this->activeOn;
    }

    /**
     * Set expireOn
     *
     * @param \DateTime $expireOn
     * @return Field
     */
    public function setExpireOn($expireOn)
    {
        $this->expireOn = $expireOn;

        return $this;
    }

    /**
     * Get expireOn
     *
     * @return \DateTime 
     */
    public function getExpireOn()
    {
        return $this->expireOn;
    }

    /**
     * Set javascript
     *
     * @param string $javascript
     * @return Field
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
     * Set visibilityId
     *
     * @param integer $visibilityId
     * @return Field
     */
    public function setVisibilityId($visibilityId)
    {
        $this->visibilityId = $visibilityId;

        return $this;
    }

    /**
     * Get visibilityId
     *
     * @return integer 
     */
    public function getVisibilityId()
    {
        return $this->visibilityId;
    }

    /**
     * Set priceSet
     *
     * @param \Civi\Price\Set $priceSet
     * @return Field
     */
    public function setPriceSet(\Civi\Price\Set $priceSet = null)
    {
        $this->priceSet = $priceSet;

        return $this;
    }

    /**
     * Get priceSet
     *
     * @return \Civi\Price\Set 
     */
    public function getPriceSet()
    {
        return $this->priceSet;
    }
}
