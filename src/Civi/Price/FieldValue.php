<?php

namespace Civi\Price;

use Doctrine\ORM\Mapping as ORM;

/**
 * FieldValue
 *
 * @ORM\Table(name="civicrm_price_field_value", indexes={@ORM\Index(name="FK_civicrm_price_field_value_price_field_id", columns={"price_field_id"}), @ORM\Index(name="FK_civicrm_price_field_value_membership_type_id", columns={"membership_type_id"}), @ORM\Index(name="FK_civicrm_price_field_value_financial_type_id", columns={"financial_type_id"})})
 * @ORM\Entity
 */
class FieldValue extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="string", length=512, nullable=false)
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="count", type="integer", nullable=true)
     */
    private $count;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_value", type="integer", nullable=true)
     */
    private $maxValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=true)
     */
    private $weight = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="membership_num_terms", type="integer", nullable=true)
     */
    private $membershipNumTerms;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="deductible_amount", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $deductibleAmount = '0.00';

    /**
     * @var \Civi\Price\Field
     *
     * @ORM\ManyToOne(targetEntity="Civi\Price\Field")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="price_field_id", referencedColumnName="id")
     * })
     */
    private $priceField;

    /**
     * @var \Civi\Member\MembershipType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Member\MembershipType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="membership_type_id", referencedColumnName="id")
     * })
     */
    private $membershipType;

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
     * @return FieldValue
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
     * @return FieldValue
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
     * Set description
     *
     * @param string $description
     * @return FieldValue
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
     * Set amount
     *
     * @param string $amount
     * @return FieldValue
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set count
     *
     * @param integer $count
     * @return FieldValue
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count
     *
     * @return integer 
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set maxValue
     *
     * @param integer $maxValue
     * @return FieldValue
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    /**
     * Get maxValue
     *
     * @return integer 
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return FieldValue
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
     * Set membershipNumTerms
     *
     * @param integer $membershipNumTerms
     * @return FieldValue
     */
    public function setMembershipNumTerms($membershipNumTerms)
    {
        $this->membershipNumTerms = $membershipNumTerms;

        return $this;
    }

    /**
     * Get membershipNumTerms
     *
     * @return integer 
     */
    public function getMembershipNumTerms()
    {
        return $this->membershipNumTerms;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return FieldValue
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return FieldValue
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
     * Set deductibleAmount
     *
     * @param string $deductibleAmount
     * @return FieldValue
     */
    public function setDeductibleAmount($deductibleAmount)
    {
        $this->deductibleAmount = $deductibleAmount;

        return $this;
    }

    /**
     * Get deductibleAmount
     *
     * @return string 
     */
    public function getDeductibleAmount()
    {
        return $this->deductibleAmount;
    }

    /**
     * Set priceField
     *
     * @param \Civi\Price\Field $priceField
     * @return FieldValue
     */
    public function setPriceField(\Civi\Price\Field $priceField = null)
    {
        $this->priceField = $priceField;

        return $this;
    }

    /**
     * Get priceField
     *
     * @return \Civi\Price\Field 
     */
    public function getPriceField()
    {
        return $this->priceField;
    }

    /**
     * Set membershipType
     *
     * @param \Civi\Member\MembershipType $membershipType
     * @return FieldValue
     */
    public function setMembershipType(\Civi\Member\MembershipType $membershipType = null)
    {
        $this->membershipType = $membershipType;

        return $this;
    }

    /**
     * Get membershipType
     *
     * @return \Civi\Member\MembershipType 
     */
    public function getMembershipType()
    {
        return $this->membershipType;
    }

    /**
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return FieldValue
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
