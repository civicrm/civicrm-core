<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * BounceType
 *
 * @ORM\Table(name="civicrm_mailing_bounce_type")
 * @ORM\Entity
 */
class BounceType extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", nullable=false)
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
     * @ORM\Column(name="hold_threshold", type="integer", nullable=false)
     */
    private $holdThreshold;



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
     * @return BounceType
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
     * @return BounceType
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
     * Set holdThreshold
     *
     * @param integer $holdThreshold
     * @return BounceType
     */
    public function setHoldThreshold($holdThreshold)
    {
        $this->holdThreshold = $holdThreshold;

        return $this;
    }

    /**
     * Get holdThreshold
     *
     * @return integer 
     */
    public function getHoldThreshold()
    {
        return $this->holdThreshold;
    }
}
