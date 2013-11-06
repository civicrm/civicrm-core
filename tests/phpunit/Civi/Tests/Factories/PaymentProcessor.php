<?php

namespace Civi\Tests\Factories;

class PaymentProcessor
{
  static function create()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $domain = $entity_manager->getReference('Civi\Core\Domain', 1);
    $payment_processor_type = $entity_manager->getRepository('Civi\Financial\PaymentProcessorType')->findOneBy(array('name' => 'Dummy'));
    $payment_processor = new \Civi\Financial\PaymentProcessor();
    $payment_processor->setBillingMode(1);
    $payment_processor->setDomain($domain);
    $payment_processor->setIsActive(TRUE);
    $payment_processor->setIsDefault(TRUE);
    $payment_processor->setClassName('Payment_Dummy');
    $payment_processor->setName('Test');
    $payment_processor->setPaymentProcessorType($payment_processor_type);
    return $payment_processor;
  }
}
