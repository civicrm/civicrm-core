<?php

namespace Civi\Contribute;

use Doctrine\ORM\Mapping as ORM;

/**
 * Product
 *
 * @ORM\Table(name="civicrm_product", indexes={@ORM\Index(name="FK_civicrm_product_financial_type_id", columns={"financial_type_id"})})
 * @ORM\Entity
 */
class Product extends \Civi\Core\Entity
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
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="sku", type="string", length=50, nullable=true)
     */
    private $sku;

    /**
     * @var string
     *
     * @ORM\Column(name="options", type="text", nullable=true)
     */
    private $options;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var string
     *
     * @ORM\Column(name="thumbnail", type="string", length=255, nullable=true)
     */
    private $thumbnail;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="min_contribution", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $minContribution;

    /**
     * @var string
     *
     * @ORM\Column(name="cost", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $cost;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=false)
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="period_type", type="string", nullable=true)
     */
    private $periodType = 'rolling';

    /**
     * @var integer
     *
     * @ORM\Column(name="fixed_period_start_day", type="integer", nullable=true)
     */
    private $fixedPeriodStartDay = '101';

    /**
     * @var string
     *
     * @ORM\Column(name="duration_unit", type="string", nullable=true)
     */
    private $durationUnit = 'year';

    /**
     * @var integer
     *
     * @ORM\Column(name="duration_interval", type="integer", nullable=true)
     */
    private $durationInterval;

    /**
     * @var string
     *
     * @ORM\Column(name="frequency_unit", type="string", nullable=true)
     */
    private $frequencyUnit = 'month';

    /**
     * @var integer
     *
     * @ORM\Column(name="frequency_interval", type="integer", nullable=true)
     */
    private $frequencyInterval;

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
     * @return Product
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
     * @return Product
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
     * Set sku
     *
     * @param string $sku
     * @return Product
     */
    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get sku
     *
     * @return string 
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Set options
     *
     * @param string $options
     * @return Product
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options
     *
     * @return string 
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return Product
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set thumbnail
     *
     * @param string $thumbnail
     * @return Product
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /**
     * Get thumbnail
     *
     * @return string 
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set price
     *
     * @param string $price
     * @return Product
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Product
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set minContribution
     *
     * @param string $minContribution
     * @return Product
     */
    public function setMinContribution($minContribution)
    {
        $this->minContribution = $minContribution;

        return $this;
    }

    /**
     * Get minContribution
     *
     * @return string 
     */
    public function getMinContribution()
    {
        return $this->minContribution;
    }

    /**
     * Set cost
     *
     * @param string $cost
     * @return Product
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return string 
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Product
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
     * Set periodType
     *
     * @param string $periodType
     * @return Product
     */
    public function setPeriodType($periodType)
    {
        $this->periodType = $periodType;

        return $this;
    }

    /**
     * Get periodType
     *
     * @return string 
     */
    public function getPeriodType()
    {
        return $this->periodType;
    }

    /**
     * Set fixedPeriodStartDay
     *
     * @param integer $fixedPeriodStartDay
     * @return Product
     */
    public function setFixedPeriodStartDay($fixedPeriodStartDay)
    {
        $this->fixedPeriodStartDay = $fixedPeriodStartDay;

        return $this;
    }

    /**
     * Get fixedPeriodStartDay
     *
     * @return integer 
     */
    public function getFixedPeriodStartDay()
    {
        return $this->fixedPeriodStartDay;
    }

    /**
     * Set durationUnit
     *
     * @param string $durationUnit
     * @return Product
     */
    public function setDurationUnit($durationUnit)
    {
        $this->durationUnit = $durationUnit;

        return $this;
    }

    /**
     * Get durationUnit
     *
     * @return string 
     */
    public function getDurationUnit()
    {
        return $this->durationUnit;
    }

    /**
     * Set durationInterval
     *
     * @param integer $durationInterval
     * @return Product
     */
    public function setDurationInterval($durationInterval)
    {
        $this->durationInterval = $durationInterval;

        return $this;
    }

    /**
     * Get durationInterval
     *
     * @return integer 
     */
    public function getDurationInterval()
    {
        return $this->durationInterval;
    }

    /**
     * Set frequencyUnit
     *
     * @param string $frequencyUnit
     * @return Product
     */
    public function setFrequencyUnit($frequencyUnit)
    {
        $this->frequencyUnit = $frequencyUnit;

        return $this;
    }

    /**
     * Get frequencyUnit
     *
     * @return string 
     */
    public function getFrequencyUnit()
    {
        return $this->frequencyUnit;
    }

    /**
     * Set frequencyInterval
     *
     * @param integer $frequencyInterval
     * @return Product
     */
    public function setFrequencyInterval($frequencyInterval)
    {
        $this->frequencyInterval = $frequencyInterval;

        return $this;
    }

    /**
     * Get frequencyInterval
     *
     * @return integer 
     */
    public function getFrequencyInterval()
    {
        return $this->frequencyInterval;
    }

    /**
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return Product
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
}
