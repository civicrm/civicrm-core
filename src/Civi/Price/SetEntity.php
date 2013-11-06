<?php

namespace Civi\Price;

use Doctrine\ORM\Mapping as ORM;

/**
 * SetEntity
 *
 * @ORM\Table(name="civicrm_price_set_entity", uniqueConstraints={@ORM\UniqueConstraint(name="UI_entity", columns={"entity_table", "entity_id"})}, indexes={@ORM\Index(name="FK_civicrm_price_set_entity_price_set_id", columns={"price_set_id"})})
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="entity_table", type="string", length=64)
 * @ORM\DiscriminatorMap({"civicrm_event" = "Civi\Price\SetEventEntity", "civicrm_contribution" = "Civi\Price\SetContributionEntity"})
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
     * @var \Civi\Price\Set
     *
     * @ORM\ManyToOne(targetEntity="Civi\Price\Set", cascade={"persist"})
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
