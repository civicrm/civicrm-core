<?php

namespace Civi\Mailing\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackableURLOpen
 *
 * @ORM\Table(name="civicrm_mailing_event_trackable_url_open", indexes={@ORM\Index(name="FK_civicrm_mailing_event_trackable_url_open_event_queue_id", columns={"event_queue_id"}), @ORM\Index(name="FK_civicrm_mailing_event_trackable_url_open_trackable_url_id", columns={"trackable_url_id"})})
 * @ORM\Entity
 */
class TrackableURLOpen extends \Civi\Core\Entity
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
     * @var \Civi\Mailing\Event\Queue
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Event\Queue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_queue_id", referencedColumnName="id")
     * })
     */
    private $eventQueue;

    /**
     * @var \Civi\Mailing\TrackableURL
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\TrackableURL")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="trackable_url_id", referencedColumnName="id")
     * })
     */
    private $trackableUrl;



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
     * @return TrackableURLOpen
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
     * @return TrackableURLOpen
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

    /**
     * Set trackableUrl
     *
     * @param \Civi\Mailing\TrackableURL $trackableUrl
     * @return TrackableURLOpen
     */
    public function setTrackableUrl(\Civi\Mailing\TrackableURL $trackableUrl = null)
    {
        $this->trackableUrl = $trackableUrl;

        return $this;
    }

    /**
     * Get trackableUrl
     *
     * @return \Civi\Mailing\TrackableURL 
     */
    public function getTrackableUrl()
    {
        return $this->trackableUrl;
    }
}
