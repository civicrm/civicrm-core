<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * PreferencesDate
 *
 * @ORM\Table(name="civicrm_preferences_date", indexes={@ORM\Index(name="index_name", columns={"name"})})
 * @ORM\Entity
 */
class PreferencesDate extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
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
     * @ORM\Column(name="start", type="integer", nullable=false)
     */
    private $start;

    /**
     * @var integer
     *
     * @ORM\Column(name="end", type="integer", nullable=false)
     */
    private $end;

    /**
     * @var string
     *
     * @ORM\Column(name="date_format", type="string", length=64, nullable=true)
     */
    private $dateFormat;

    /**
     * @var string
     *
     * @ORM\Column(name="time_format", type="string", length=64, nullable=true)
     */
    private $timeFormat;



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
     * @return PreferencesDate
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
     * @return PreferencesDate
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
     * Set start
     *
     * @param integer $start
     * @return PreferencesDate
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return integer 
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param integer $end
     * @return PreferencesDate
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return integer 
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set dateFormat
     *
     * @param string $dateFormat
     * @return PreferencesDate
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Get dateFormat
     *
     * @return string 
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * Set timeFormat
     *
     * @param string $timeFormat
     * @return PreferencesDate
     */
    public function setTimeFormat($timeFormat)
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }

    /**
     * Get timeFormat
     *
     * @return string 
     */
    public function getTimeFormat()
    {
        return $this->timeFormat;
    }
}
