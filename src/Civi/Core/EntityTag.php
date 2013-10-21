<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTag
 *
 * @ORM\Table(name="civicrm_entity_tag", uniqueConstraints={@ORM\UniqueConstraint(name="UI_entity_id_entity_table_tag_id", columns={"entity_id", "entity_table", "tag_id"})}, indexes={@ORM\Index(name="FK_civicrm_entity_tag_tag_id", columns={"tag_id"})})
 * @ORM\Entity
 */
class EntityTag extends \Civi\Core\Entity
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
     * @var \Civi\Core\Tag
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Tag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     * })
     */
    private $tag;



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
     * @return EntityTag
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
     * @return EntityTag
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
     * Set tag
     *
     * @param \Civi\Core\Tag $tag
     * @return EntityTag
     */
    public function setTag(\Civi\Core\Tag $tag = null)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return \Civi\Core\Tag 
     */
    public function getTag()
    {
        return $this->tag;
    }
}
