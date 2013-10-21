<?php

namespace Civi\Price;

use Doctrine\ORM\Mapping as ORM;

/**
 * LineItem
 *
 * @ORM\Table(name="civicrm_line_item", uniqueConstraints={@ORM\UniqueConstraint(name="UI_line_item_value", columns={"entity_table", "entity_id", "price_field_value_id", "price_field_id"})}, indexes={@ORM\Index(name="index_entity", columns={"entity_table", "entity_id"}), @ORM\Index(name="FK_civicrm_line_item_price_field_id", columns={"price_field_id"}), @ORM\Index(name="FK_civicrm_line_item_price_field_value_id", columns={"price_field_value_id"}), @ORM\Index(name="FK_civicrm_line_item_financial_type_id", columns={"financial_type_id"})})
 * @ORM\Entity
 */
class LineItem extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity_table", type="string", length=64, nullable=false)
     */
    private $entityTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    private $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="qty", type="integer", nullable=false)
     */
    private $qty;

    /**
     * @var string
     *
     * @ORM\Column(name="unit_price", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $unitPrice;

    /**
     * @var string
     *
     * @ORM\Column(name="line_total", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $lineTotal;

    /**
     * @var integer
     *
     * @ORM\Column(name="participant_count", type="integer", nullable=true)
     */
    private $participantCount;

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
     * @var \Civi\Price\FieldValue
     *
     * @ORM\ManyToOne(targetEntity="Civi\Price\FieldValue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="price_field_value_id", referencedColumnName="id")
     * })
     */
    private $priceFieldValue;

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
     * Set entityTable
     *
     * @param string $entityTable
     * @return LineItem
     */
    public function setEntityTable($entityTable)
    {
        $this->entityTable = $entityTable;

        return $this;
    }

    /**
     * Get entityTable
     *
     * @return string 
     */
    public function getEntityTable()
    {
        return $this->entityTable;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     * @return LineItem
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer 
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return LineItem
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
     * Set qty
     *
     * @param integer $qty
     * @return LineItem
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * Get qty
     *
     * @return integer 
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Set unitPrice
     *
     * @param string $unitPrice
     * @return LineItem
     */
    public function setUnitPrice($unitPrice)
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * Get unitPrice
     *
     * @return string 
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * Set lineTotal
     *
     * @param string $lineTotal
     * @return LineItem
     */
    public function setLineTotal($lineTotal)
    {
        $this->lineTotal = $lineTotal;

        return $this;
    }

    /**
     * Get lineTotal
     *
     * @return string 
     */
    public function getLineTotal()
    {
        return $this->lineTotal;
    }

    /**
     * Set participantCount
     *
     * @param integer $participantCount
     * @return LineItem
     */
    public function setParticipantCount($participantCount)
    {
        $this->participantCount = $participantCount;

        return $this;
    }

    /**
     * Get participantCount
     *
     * @return integer 
     */
    public function getParticipantCount()
    {
        return $this->participantCount;
    }

    /**
     * Set deductibleAmount
     *
     * @param string $deductibleAmount
     * @return LineItem
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
     * @return LineItem
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
     * Set priceFieldValue
     *
     * @param \Civi\Price\FieldValue $priceFieldValue
     * @return LineItem
     */
    public function setPriceFieldValue(\Civi\Price\FieldValue $priceFieldValue = null)
    {
        $this->priceFieldValue = $priceFieldValue;

        return $this;
    }

    /**
     * Get priceFieldValue
     *
     * @return \Civi\Price\FieldValue 
     */
    public function getPriceFieldValue()
    {
        return $this->priceFieldValue;
    }

    /**
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return LineItem
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
