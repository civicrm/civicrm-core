<?php

namespace Civi\Batch;

use Doctrine\ORM\Mapping as ORM;

/**
 * EntityBatch
 *
 * @ORM\Table(name="civicrm_entity_batch", uniqueConstraints={@ORM\UniqueConstraint(name="UI_batch_entity", columns={"batch_id", "entity_id", "entity_table"})}, indexes={@ORM\Index(name="index_entity", columns={"entity_table", "entity_id"}), @ORM\Index(name="IDX_6B543499F39EBE7A", columns={"batch_id"})})
 * @ORM\Entity
 */
class EntityBatch extends \Civi\Core\Entity
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
     * @var \Civi\Batch\Batch
     *
     * @ORM\ManyToOne(targetEntity="Civi\Batch\Batch")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="batch_id", referencedColumnName="id")
     * })
     */
    private $batch;



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
     * @return EntityBatch
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
     * @return EntityBatch
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
     * Set batch
     *
     * @param \Civi\Batch\Batch $batch
     * @return EntityBatch
     */
    public function setBatch(\Civi\Batch\Batch $batch = null)
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * Get batch
     *
     * @return \Civi\Batch\Batch 
     */
    public function getBatch()
    {
        return $this->batch;
    }
}
