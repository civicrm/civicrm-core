<?php

namespace Civi\Mailing\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Confirm
 *
 * @ORM\Table(name="civicrm_mailing_event_confirm", indexes={@ORM\Index(name="FK_civicrm_mailing_event_confirm_event_subscribe_id", columns={"event_subscribe_id"})})
 * @ORM\Entity
 */
class Confirm extends \Civi\Core\Entity
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
     * @var \DateTime
     *
     * @ORM\Column(name="time_stamp", type="datetime", nullable=false)
     */
    private $timeStamp;

    /**
     * @var \Civi\Mailing\Event\Subscribe
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Event\Subscribe")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_subscribe_id", referencedColumnName="id")
     * })
     */
    private $eventSubscribe;



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
     * Set timeStamp
     *
     * @param \DateTime $timeStamp
     * @return Confirm
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
     * Set eventSubscribe
     *
     * @param \Civi\Mailing\Event\Subscribe $eventSubscribe
     * @return Confirm
     */
    public function setEventSubscribe(\Civi\Mailing\Event\Subscribe $eventSubscribe = null)
    {
        $this->eventSubscribe = $eventSubscribe;

        return $this;
    }

    /**
     * Get eventSubscribe
     *
     * @return \Civi\Mailing\Event\Subscribe 
     */
    public function getEventSubscribe()
    {
        return $this->eventSubscribe;
    }
}
