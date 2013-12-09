<?php

namespace Civi\Tests\Factories\Event;

use \Civi\Tests\Factories;

class Event extends Factories\Base
{
  static function build()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $event = new \Civi\Event\Event();
    $event->setTitle('Test Event');
    $event->setIsActive(TRUE);
    $event->setIsOnlineRegistration(TRUE);
    $event->setIsMultipleRegistrations(FALSE);
    $event->setIsMonetary(TRUE);
    $event->addPaymentProcessor(Factories\Financial\PaymentProcessor::build());
    $event->addPriceSet(Factories\Price\Set::build());
    $participant_role = $entity_manager->getRepository('Civi\Core\OptionValue')->findOne('participant_role', 'Attendee');
    $event->setDefaultRoleId($participant_role->getValue());
    $event->setIsEmailConfirm(TRUE);
    $financial_type = $entity_manager->getRepository('Civi\Financial\Type')->findOneBy(array('name' => 'Event Fee'));
    $event->setFinancialType($financial_type);
    return $event;
  }
}
