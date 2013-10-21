<?php

namespace Civi\Mailing\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Bounce
 *
 * @ORM\Table(name="civicrm_mailing_event_bounce", indexes={@ORM\Index(name="FK_civicrm_mailing_event_bounce_event_queue_id", columns={"event_queue_id"})})
 * @ORM\Entity
 */
class Bounce extends \Civi\Core\Entity
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
     * @ORM\Column(name="bounce_type_id", type="integer", nullable=true)
     */
    private $bounceTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="bounce_reason", type="string", length=255, nullable=true)
     */
    private $bounceReason;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_stamp", type="datetime", nullable=false)
     */
    private $timeStamp;

    /**
     * @var \Civi\Mailing\Event\Queue
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Event\Queue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_queue_id", referencedColumnName="id")
     * })
     */
    private $eventQueue;



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
     * Set bounceTypeId
     *
     * @param integer $bounceTypeId
     * @return Bounce
     */
    public function setBounceTypeId($bounceTypeId)
    {
        $this->bounceTypeId = $bounceTypeId;

        return $this;
    }

    /**
     * Get bounceTypeId
     *
     * @return integer 
     */
    public function getBounceTypeId()
    {
        return $this->bounceTypeId;
    }

    /**
     * Set bounceReason
     *
     * @param string $bounceReason
     * @return Bounce
     */
    public function setBounceReason($bounceReason)
    {
        $this->bounceReason = $bounceReason;

        return $this;
    }

    /**
     * Get bounceReason
     *
     * @return string 
     */
    public function getBounceReason()
    {
        return $this->bounceReason;
    }

    /**
     * Set timeStamp
     *
     * @param \DateTime $timeStamp
     * @return Bounce
     */
    public function setTimeStamp($timeStamp)
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    /**
     * Get timeStamp
     *
     * @return \DateTime 
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }

    /**
     * Set eventQueue
     *
     * @param \Civi\Mailing\Event\Queue $eventQueue
     * @return Bounce
     */
    public function setEventQueue(\Civi\Mailing\Event\Queue $eventQueue = null)
    {
        $this->eventQueue = $eventQueue;

        return $this;
    }

    /**
     * Get eventQueue
     *
     * @return \Civi\Mailing\Event\Queue 
     */
    public function getEventQueue()
    {
        return $this->eventQueue;
    }
}
