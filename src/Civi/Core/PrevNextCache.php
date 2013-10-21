<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * PrevNextCache
 *
 * @ORM\Table(name="civicrm_prevnext_cache", indexes={@ORM\Index(name="index_all", columns={"cacheKey", "entity_id1", "entity_id2", "entity_table", "is_selected"})})
 * @ORM\Entity
 */
class PrevNextCache extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity_id1", type="integer", nullable=false)
     */
    private $entityId1;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id2", type="integer", nullable=false)
     */
    private $entityId2;

    /**
     * @var string
     *
     * @ORM\Column(name="cacheKey", type="string", length=255, nullable=true)
     */
    private $cachekey;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_selected", type="boolean", nullable=true)
     */
    private $isSelected = '0';



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
     * @return PrevNextCache
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
     * Set entityId1
     *
     * @param integer $entityId1
     * @return PrevNextCache
     */
    public function setEntityId1($entityId1)
    {
        $this->entityId1 = $entityId1;

        return $this;
    }

    /**
     * Get entityId1
     *
     * @return integer 
     */
    public function getEntityId1()
    {
        return $this->entityId1;
    }

    /**
     * Set entityId2
     *
     * @param integer $entityId2
     * @return PrevNextCache
     */
    public function setEntityId2($entityId2)
    {
        $this->entityId2 = $entityId2;

        return $this;
    }

    /**
     * Get entityId2
     *
     * @return integer 
     */
    public function getEntityId2()
    {
        return $this->entityId2;
    }

    /**
     * Set cachekey
     *
     * @param string $cachekey
     * @return PrevNextCache
     */
    public function setCachekey($cachekey)
    {
        $this->cachekey = $cachekey;

        return $this;
    }

    /**
     * Get cachekey
     *
     * @return string 
     */
    public function getCachekey()
    {
        return $this->cachekey;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return PrevNextCache
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set isSelected
     *
     * @param boolean $isSelected
     * @return PrevNextCache
     */
    public function setIsSelected($isSelected)
    {
        $this->isSelected = $isSelected;

        return $this;
    }

    /**
     * Get isSelected
     *
     * @return boolean 
     */
    public function getIsSelected()
    {
        return $this->isSelected;
    }
}
