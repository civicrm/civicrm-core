<?php

namespace Civi\Price;

use Doctrine\ORM\Mapping as ORM;

/**
 * SetEventEntity
 *
 * @ORM\Entity
 *
 */
class SetEventEntity extends SetEntity
{
  /**
   * @ORM\ManyToOne(targetEntity="Civi\Event\Event")
   * @ORM\JoinColumns({
   *   @ORM\JoinColumn(name="entity_id", referencedColumnName="id")
   * })
   */
  private $event;

    /**
     * Set event
     *
     * @param \Civi\Event\Event $event
     * @return SetEventEntity
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
}
