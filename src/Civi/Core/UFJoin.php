<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * UFJoin
 *
 * @ORM\Table(name="civicrm_uf_join", indexes={@ORM\Index(name="index_entity", columns={"entity_table", "entity_id"}), @ORM\Index(name="FK_civicrm_uf_join_uf_group_id", columns={"uf_group_id"})})
 * @ORM\Entity
 */
class UFJoin extends \Civi\Core\Entity
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
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="module", type="string", length=64, nullable=false)
     */
    private $module;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_table", type="string", length=64, nullable=true)
     */
    private $entityTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=true)
     */
    private $entityId;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight = '1';

    /**
     * @var \Civi\Core\UFGroup
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\UFGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="uf_group_id", referencedColumnName="id")
     * })
     */
    private $ufGroup;



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
     * Set isActive
     *
     * @param boolean $isActive
     * @return UFJoin
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
     * Set module
     *
     * @param string $module
     * @return UFJoin
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module
     *
     * @return string 
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set entityTable
     *
     * @param string $entityTable
     * @return UFJoin
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
     * @return UFJoin
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
     * Set weight
     *
     * @param integer $weight
     * @return UFJoin
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
     * Set ufGroup
     *
     * @param \Civi\Core\UFGroup $ufGroup
     * @return UFJoin
     */
    public function setUfGroup(\Civi\Core\UFGroup $ufGroup = null)
    {
        $this->ufGroup = $ufGroup;

        return $this;
    }

    /**
     * Get ufGroup
     *
     * @return \Civi\Core\UFGroup 
     */
    public function getUfGroup()
    {
        return $this->ufGroup;
    }
}
