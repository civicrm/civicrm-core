<?php

namespace Civi\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * EntityRole
 *
 * @ORM\Table(name="civicrm_acl_entity_role", indexes={@ORM\Index(name="index_role", columns={"acl_role_id"}), @ORM\Index(name="index_entity", columns={"entity_table", "entity_id"})})
 * @ORM\Entity
 */
class EntityRole extends \Civi\Core\Entity
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
     * @var integer
     *
     * @ORM\Column(name="acl_role_id", type="integer", nullable=false)
     */
    private $aclRoleId;

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
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;



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
     * Set aclRoleId
     *
     * @param integer $aclRoleId
     * @return EntityRole
     */
    public function setAclRoleId($aclRoleId)
    {
        $this->aclRoleId = $aclRoleId;

        return $this;
    }

    /**
     * Get aclRoleId
     *
     * @return integer 
     */
    public function getAclRoleId()
    {
        return $this->aclRoleId;
    }

    /**
     * Set entityTable
     *
     * @param string $entityTable
     * @return EntityRole
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
     * @return EntityRole
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return EntityRole
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
}
