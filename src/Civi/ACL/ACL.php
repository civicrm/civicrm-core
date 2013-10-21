<?php

namespace Civi\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * ACL
 *
 * @ORM\Table(name="civicrm_acl", indexes={@ORM\Index(name="index_acl_id", columns={"acl_id"})})
 * @ORM\Entity
 */
class ACL extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deny", type="boolean", nullable=false)
     */
    private $deny = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="entity_table", type="string", length=64, nullable=false)
     */
    private $entityTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=true)
     */
    private $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="operation", type="string", nullable=false)
     */
    private $operation;

    /**
     * @var string
     *
     * @ORM\Column(name="object_table", type="string", length=64, nullable=true)
     */
    private $objectTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     */
    private $objectId;

    /**
     * @var string
     *
     * @ORM\Column(name="acl_table", type="string", length=64, nullable=true)
     */
    private $aclTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="acl_id", type="integer", nullable=true)
     */
    private $aclId;

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
     * Set name
     *
     * @param string $name
     * @return ACL
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set deny
     *
     * @param boolean $deny
     * @return ACL
     */
    public function setDeny($deny)
    {
        $this->deny = $deny;

        return $this;
    }

    /**
     * Get deny
     *
     * @return boolean 
     */
    public function getDeny()
    {
        return $this->deny;
    }

    /**
     * Set entityTable
     *
     * @param string $entityTable
     * @return ACL
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
     * @return ACL
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
     * Set operation
     *
     * @param string $operation
     * @return ACL
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation
     *
     * @return string 
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set objectTable
     *
     * @param string $objectTable
     * @return ACL
     */
    public function setObjectTable($objectTable)
    {
        $this->objectTable = $objectTable;

        return $this;
    }

    /**
     * Get objectTable
     *
     * @return string 
     */
    public function getObjectTable()
    {
        return $this->objectTable;
    }

    /**
     * Set objectId
     *
     * @param integer $objectId
     * @return ACL
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * Get objectId
     *
     * @return integer 
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set aclTable
     *
     * @param string $aclTable
     * @return ACL
     */
    public function setAclTable($aclTable)
    {
        $this->aclTable = $aclTable;

        return $this;
    }

    /**
     * Get aclTable
     *
     * @return string 
     */
    public function getAclTable()
    {
        return $this->aclTable;
    }

    /**
     * Set aclId
     *
     * @param integer $aclId
     * @return ACL
     */
    public function setAclId($aclId)
    {
        $this->aclId = $aclId;

        return $this;
    }

    /**
     * Get aclId
     *
     * @return integer 
     */
    public function getAclId()
    {
        return $this->aclId;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return ACL
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
