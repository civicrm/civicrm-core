<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Group
 *
 * @ORM\Table(name="civicrm_mailing_group", indexes={@ORM\Index(name="FK_civicrm_mailing_group_mailing_id", columns={"mailing_id"})})
 * @ORM\Entity
 */
class Group extends \Civi\Core\Entity
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
     * @ORM\Column(name="group_type", type="string", nullable=true)
     */
    private $groupType;

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
     * @ORM\Column(name="search_id", type="integer", nullable=true)
     */
    private $searchId;

    /**
     * @var string
     *
     * @ORM\Column(name="search_args", type="text", nullable=true)
     */
    private $searchArgs;

    /**
     * @var \Civi\Mailing\Mailing
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Mailing")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mailing_id", referencedColumnName="id")
     * })
     */
    private $mailing;



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
     * Set groupType
     *
     * @param string $groupType
     * @return Group
     */
    public function setGroupType($groupType)
    {
        $this->groupType = $groupType;

        return $this;
    }

    /**
     * Get groupType
     *
     * @return string 
     */
    public function getGroupType()
    {
        return $this->groupType;
    }

    /**
     * Set entityTable
     *
     * @param string $entityTable
     * @return Group
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
     * @return Group
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
     * Set searchId
     *
     * @param integer $searchId
     * @return Group
     */
    public function setSearchId($searchId)
    {
        $this->searchId = $searchId;

        return $this;
    }

    /**
     * Get searchId
     *
     * @return integer 
     */
    public function getSearchId()
    {
        return $this->searchId;
    }

    /**
     * Set searchArgs
     *
     * @param string $searchArgs
     * @return Group
     */
    public function setSearchArgs($searchArgs)
    {
        $this->searchArgs = $searchArgs;

        return $this;
    }

    /**
     * Get searchArgs
     *
     * @return string 
     */
    public function getSearchArgs()
    {
        return $this->searchArgs;
    }

    /**
     * Set mailing
     *
     * @param \Civi\Mailing\Mailing $mailing
     * @return Group
     */
    public function setMailing(\Civi\Mailing\Mailing $mailing = null)
    {
        $this->mailing = $mailing;

        return $this;
    }

    /**
     * Get mailing
     *
     * @return \Civi\Mailing\Mailing 
     */
    public function getMailing()
    {
        return $this->mailing;
    }
}
