<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mapping
 *
 * @ORM\Table(name="civicrm_mapping", indexes={@ORM\Index(name="UI_name", columns={"name"})})
 * @ORM\Entity
 */
class Mapping extends \Civi\Core\Entity
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
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="mapping_type_id", type="integer", nullable=true)
     */
    private $mappingTypeId;



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
     * @return Mapping
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
     * Set description
     *
     * @param string $description
     * @return Mapping
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set mappingTypeId
     *
     * @param integer $mappingTypeId
     * @return Mapping
     */
    public function setMappingTypeId($mappingTypeId)
    {
        $this->mappingTypeId = $mappingTypeId;

        return $this;
    }

    /**
     * Get mappingTypeId
     *
     * @return integer 
     */
    public function getMappingTypeId()
    {
        return $this->mappingTypeId;
    }
}
