<?php

namespace Civi\Tests\Event\Form\Registration;

use \Civi\Tests\Factories;

class ConfirmTest extends \Civi\Tests\IsolatedTestCase
{
  function testPostProcess()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $config = \CRM_Core_Config::singleton();
    $event = Factories\Event\Event::create();
    $price_set = $event->getPriceSets()->first();
    $price_field = $price_set->getPriceFields()->first();
    $price_field_value = $price_field->getPriceFieldValues()->first();
    $key = \CRM_Core_Key::get('CRM_Event_Controller_Registration', 1);
    $_REQUEST['id'] = $event->getId();
    $_REQUEST['fee'] = $price_set->getId();
    $_REQUEST['qfKey'] = $key;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $controller = new \CRM_Event_Controller_Registration();
    $form = new \CRM_Event_Form_Registration_Confirm();
    $form->controller = $controller;
    $params = array(
      array(
        'amount' => '22.00',
        'amount_level' => '1',
        'billing_first_name' => 'Test',
        'billing_middle_name' => '',
        'billing_last_name' => 'Test',
        'billing_street_address-5' => 'Test',
        'billing_city-5' => 'Test',
        'billing_state_province_id-5' => '1004',
        'billing_postal_code-5' => '94703',
        'billing_country_id-5' => '1228',
        'credit_card_exp_date' => array('M' => '1', 'Y' => '2014'),
        'credit_card_number' => '4111111111111111',
        'credit_card_type' => 'Visa',
        'currencyID' => $config->defaultCurrency,
        'cvv2' => '411',
        'is_primary' => 1,
        'invoice_id' => 'Test!!!!',
      ),
    );
    $line_item = array(
      array(
        $price_field_value->getId() => array(
          'price_field_id' => $price_field->getId(),
          'price_field_value_id' => $price_field_value->getId(),
          'label' => $price_field_value->getLabel(),
          'field_title' => $price_field->getLabel(),
          'description' => '',
          'qty' => 1,
          'unit_price' => 22,
          'line_total' => 22,
          'participant_count' => 1,
          'max_value' => '',
          'membership_type_id' => '',
          'auto_renew' => '',
          'html_type' => 'Text',
        )
      )
    );
    $form->set('lineItem', $line_item);
    $form->set('params', $params);
    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
    $entity_manager->refresh($event);
    $this->assertEquals(1, $event->getParticipants()->count());
  }
}
