<?php

namespace Civi\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventInCart
 *
 * @ORM\Table(name="civicrm_events_in_carts", indexes={@ORM\Index(name="FK_civicrm_events_in_carts_event_id", columns={"event_id"}), @ORM\Index(name="FK_civicrm_events_in_carts_event_cart_id", columns={"event_cart_id"})})
 * @ORM\Entity
 */
class EventInCart extends \Civi\Core\Entity
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
     * @var \Civi\Event\Event
     *
     * @ORM\ManyToOne(targetEntity="Civi\Event\Event")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     * })
     */
    private $event;

    /**
     * @var \Civi\Event\Cart
     *
     * @ORM\ManyToOne(targetEntity="Civi\Event\Cart")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_cart_id", referencedColumnName="id")
     * })
     */
    private $eventCart;



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
     * Set event
     *
     * @param \Civi\Event\Event $event
     * @return EventInCart
     */
    public function setEvent(\Civi\Event\Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return \Civi\Event\Event 
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set eventCart
     *
     * @param \Civi\Event\Cart $eventCart
     * @return EventInCart
     */
    public function setEventCart(\Civi\Event\Cart $eventCart = null)
    {
        $this->eventCart = $eventCart;

        return $this;
    }

    /**
     * Get eventCart
     *
     * @return \Civi\Event\Cart 
     */
    public function getEventCart()
    {
        return $this->eventCart;
    }
}
