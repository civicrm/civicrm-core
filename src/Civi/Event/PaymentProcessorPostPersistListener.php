<?php

namespace Civi\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class PaymentProcessorPostPersistListener
{
  public $event = NULL;

  public function __construct($event)
  {
    $this->event = $event;
  }

  public function postPersist(LifecycleEventArgs $event_args)
  {
    $entity = $event_args->getEntity();
    if (get_class($entity) == 'Civi\Financial\PaymentProcessor')
    {
      $result = $this->event->updatePaymentProcessorField();
      if ($result == TRUE)
      {
        $entity_manager = $event_args->getEntityManager();
        $unit_of_work = $entity_manager->getUnitOfWork();
        $event_metadata = $entity_manager->getClassMetadata(get_class($this->event));
        $unit_of_work->recomputeSingleEntityChangeSet($event_metadata, $this->event);
        $event_manager = $entity_manager->getEventManager();
        $event_manager->removeEventListener(array(Events::postPersist), $this);
      }
    }
  }
}
