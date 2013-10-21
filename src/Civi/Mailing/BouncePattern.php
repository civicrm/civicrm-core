<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * BouncePattern
 *
 * @ORM\Table(name="civicrm_mailing_bounce_pattern", indexes={@ORM\Index(name="FK_civicrm_mailing_bounce_pattern_bounce_type_id", columns={"bounce_type_id"})})
 * @ORM\Entity
 */
class BouncePattern extends \Civi\Core\Entity
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
     * @ORM\Column(name="pattern", type="string", length=255, nullable=true)
     */
    private $pattern;

    /**
     * @var \Civi\Mailing\BounceType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\BounceType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="bounce_type_id", referencedColumnName="id")
     * })
     */
    private $bounceType;



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
     * Set pattern
     *
     * @param string $pattern
     * @return BouncePattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Get pattern
     *
     * @return string 
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Set bounceType
     *
     * @param \Civi\Mailing\BounceType $bounceType
     * @return BouncePattern
     */
    public function setBounceType(\Civi\Mailing\BounceType $bounceType = null)
    {
        $this->bounceType = $bounceType;

        return $this;
    }

    /**
     * Get bounceType
     *
     * @return \Civi\Mailing\BounceType 
     */
    public function getBounceType()
    {
        return $this->bounceType;
    }
}
