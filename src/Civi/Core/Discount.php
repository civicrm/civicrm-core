<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Discount
 *
 * @ORM\Table(name="civicrm_discount", indexes={@ORM\Index(name="index_entity", columns={"entity_table", "entity_id"}), @ORM\Index(name="index_entity_option_id", columns={"entity_table", "entity_id", "price_set_id"}), @ORM\Index(name="FK_civicrm_discount_price_set_id", columns={"price_set_id"})})
 * @ORM\Entity
 */
class Discount extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity_table", type="string", length=64, nullable=true)
     */
    private $entityTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    private $entityId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="date", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    private $endDate;

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
     * Set entityTable
     *
     * @param string $entityTable
     * @return Discount
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
     * @return Discount
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Discount
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Discount
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set priceSet
     *
     * @param \Civi\Price\Set $priceSet
     * @return Discount
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
