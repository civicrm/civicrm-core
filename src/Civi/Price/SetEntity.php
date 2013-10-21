<?php

namespace Civi\Price;

use Doctrine\ORM\Mapping as ORM;

/**
 * SetEntity
 *
 * @ORM\Table(name="civicrm_price_set_entity", uniqueConstraints={@ORM\UniqueConstraint(name="UI_entity", columns={"entity_table", "entity_id"})}, indexes={@ORM\Index(name="FK_civicrm_price_set_entity_price_set_id", columns={"price_set_id"})})
 * @ORM\Entity
 */
class SetEntity extends \Civi\Core\Entity
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
     * @return SetEntity
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
     * @return SetEntity
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
     * Set priceSet
     *
     * @param \Civi\Price\Set $priceSet
     * @return SetEntity
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
