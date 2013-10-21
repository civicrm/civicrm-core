<?php

namespace Civi\Financial;

use Doctrine\ORM\Mapping as ORM;

/**
 * EntityFinancialTrxn
 *
 * @ORM\Table(name="civicrm_entity_financial_trxn", indexes={@ORM\Index(name="UI_entity_financial_trxn_entity_table", columns={"entity_table"}), @ORM\Index(name="UI_entity_financial_trxn_entity_id", columns={"entity_id"}), @ORM\Index(name="FK_civicrm_entity_financial_trxn_financial_trxn_id", columns={"financial_trxn_id"})})
 * @ORM\Entity
 */
class EntityFinancialTrxn extends \Civi\Core\Entity
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
     * @ORM\Column(name="amount", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $amount;

    /**
     * @var \Civi\Financial\Trxn
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Trxn")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="financial_trxn_id", referencedColumnName="id")
     * })
     */
    private $financialTrxn;



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
     * @return EntityFinancialTrxn
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
     * @return EntityFinancialTrxn
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
     * Set amount
     *
     * @param string $amount
     * @return EntityFinancialTrxn
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
     * Set financialTrxn
     *
     * @param \Civi\Financial\Trxn $financialTrxn
     * @return EntityFinancialTrxn
     */
    public function setFinancialTrxn(\Civi\Financial\Trxn $financialTrxn = null)
    {
        $this->financialTrxn = $financialTrxn;

        return $this;
    }

    /**
     * Get financialTrxn
     *
     * @return \Civi\Financial\Trxn 
     */
    public function getFinancialTrxn()
    {
        return $this->financialTrxn;
    }
}
