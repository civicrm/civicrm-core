<?php

namespace Civi\Tests\Event;

use Civi\Tests\Factories;

class EventTest extends \Civi\Tests\IsolatedTestCase
{
  public function testAddPaymentProcessor()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $payment_processor = Factories\PaymentProcessor::create();
    $event = new \Civi\Event\Event();
    $event->addPaymentProcessor($payment_processor);
    $entity_manager->persist($event);
    $entity_manager->flush();
    $entity_manager->refresh($event);
    $this->assertEquals(1, count($event->getPaymentProcessors()));
    $payment_processors = $event->getPaymentProcessors();
    $this->assertEquals('Test', $payment_processors[0]->getName());
  }

  public function testAddExistingPaymentProcessor()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $payment_processor = Factories\PaymentProcessor::create();
    $entity_manager->persist($payment_processor);
    $entity_manager->flush();
    $entity_manager->refresh($payment_processor);
    $event = new \Civi\Event\Event();
    $event->addPaymentProcessor($payment_processor);
    $entity_manager->persist($event);
    $entity_manager->flush();
    $entity_manager->refresh($event);
    $this->assertEquals(1, count($event->getPaymentProcessors()));
    $payment_processors = $event->getPaymentProcessors();
    $this->assertEquals('Test', $payment_processors[0]->getName());
  }

  public function testAddTwoPaymentProcessors()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $event = new \Civi\Event\Event();
    $payment_processor = Factories\PaymentProcessor::create();
    $payment_processor->setName('Test 1');
    $event->addPaymentProcessor($payment_processor);
    $payment_processor = Factories\PaymentProcessor::create();
    $payment_processor->setName('Test 2');
    $event->addPaymentProcessor($payment_processor);
    $entity_manager->persist($event);
    $entity_manager->flush();
    $entity_manager->refresh($event);
    $this->assertEquals(2, count($event->getPaymentProcessors()));
    $payment_processors = $event->getPaymentProcessors();
    $names = array_map(function($pp) { return $pp->getName(); }, $payment_processors->toArray());
    $this->assertTrue(in_array('Test 1', $names));
    $this->assertTrue(in_array('Test 2', $names));
  }

  public function testRemovePaymentProcessor()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $event = new \Civi\Event\Event();
    $payment_processor = Factories\PaymentProcessor::create();
    $payment_processor->setName('Test 1');
    $event->addPaymentProcessor($payment_processor);
    $payment_processor = Factories\PaymentProcessor::create();
    $payment_processor->setName('Test 2');
    $event->addPaymentProcessor($payment_processor);
    $entity_manager->persist($event);
    $entity_manager->flush();
    $entity_manager->refresh($event);
    $this->assertEquals(2, count($event->getPaymentProcessors()));
    $event->removePaymentProcessor($payment_processor);
    $entity_manager->flush();
    $entity_manager->refresh($event);
    $this->assertEquals(1, count($event->getPaymentProcessors()));
    $payment_processors = $event->getPaymentProcessors();
    $this->assertEquals('Test 1', $payment_processors[0]->getName());
  }
}
