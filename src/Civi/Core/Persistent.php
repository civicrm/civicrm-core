<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Persistent
 *
 * @ORM\Table(name="civicrm_persistent")
 * @ORM\Entity
 */
class Persistent extends \Civi\Core\Entity
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
     * @ORM\Column(name="context", type="string", length=255, nullable=false)
     */
    private $context;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_config", type="boolean", nullable=false)
     */
    private $isConfig = '0';



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
     * Set context
     *
     * @param string $context
     * @return Persistent
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context
     *
     * @return string 
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Persistent
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
     * Set data
     *
     * @param string $data
     * @return Persistent
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
     * Set isConfig
     *
     * @param boolean $isConfig
     * @return Persistent
     */
    public function setIsConfig($isConfig)
    {
        $this->isConfig = $isConfig;

        return $this;
    }

    /**
     * Get isConfig
     *
     * @return boolean 
     */
    public function getIsConfig()
    {
        return $this->isConfig;
    }
}
