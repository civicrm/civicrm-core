<?php

namespace Civi\Financial;

use Doctrine\ORM\Mapping as ORM;

/**
 * EntityFinancialAccount
 *
 * @ORM\Table(name="civicrm_entity_financial_account", indexes={@ORM\Index(name="FK_civicrm_entity_financial_account_financial_account_id", columns={"financial_account_id"})})
 * @ORM\Entity
 */
class EntityFinancialAccount extends \Civi\Core\Entity
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
     * @var integer
     *
     * @ORM\Column(name="account_relationship", type="integer", nullable=false)
     */
    private $accountRelationship;

    /**
     * @var \Civi\Financial\Account
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="financial_account_id", referencedColumnName="id")
     * })
     */
    private $financialAccount;



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
     * @return EntityFinancialAccount
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
     * @return EntityFinancialAccount
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
     * Set accountRelationship
     *
     * @param integer $accountRelationship
     * @return EntityFinancialAccount
     */
    public function setAccountRelationship($accountRelationship)
    {
        $this->accountRelationship = $accountRelationship;

        return $this;
    }

    /**
     * Get accountRelationship
     *
     * @return integer 
     */
    public function getAccountRelationship()
    {
        return $this->accountRelationship;
    }

    /**
     * Set financialAccount
     *
     * @param \Civi\Financial\Account $financialAccount
     * @return EntityFinancialAccount
     */
    public function setFinancialAccount(\Civi\Financial\Account $financialAccount = null)
    {
        $this->financialAccount = $financialAccount;

        return $this;
    }

    /**
     * Get financialAccount
     *
     * @return \Civi\Financial\Account 
     */
    public function getFinancialAccount()
    {
        return $this->financialAccount;
    }
}
