<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *  Test various payment forms.
 *
 * This class is intended to be a place to build out testing of various forms - with the
 * hope being to ensure all payment forms are consistently tested and to refine
 * helper functions into a trait that could be available to
 * extensions for testing - notably the eventcart which ideally should interact with core
 * through approved interfaces - ideally even in tests.
 *
 * An approved interface would sit in the Civi directory and would at minimum support some functions
 * to support using our processors in tests so we are testing a broader swath than just Dummy.
 * Currently Authorize.net is also testable (uses Guzzle). At some point PaypalPro & Std should also be testable
 * - allowing us to easily check payment forms work with the core processors which cover a reasonable amount of the
 * expectations held by non-core processors .
 *
 * Note that this tests eventcart but is not in eventcart because I want to be sure about whether the
 * traits supporting it make sense before making them available to extensions.
 */
class CRM_Financial_Form_PaymentFormsTest extends CiviUnitTestCase {

  use CRM_Core_Payment_AuthorizeNetTrait;

  /**
   * Generic test on event payment forms to make sure they submit without error with payment processing.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testEventPaymentForms() {
    $this->createAuthorizeNetProcessor();
    $processors = [$this->ids['PaymentProcessor']['anet']];
    $eventID = $this->eventCreatePaid([
      'end_date' => '+ 1 month',
      'registration_end_date' => '+ 1 month',
      'payment_processor' => $processors,
    ])['id'];
    $this->createLoggedInUser();

    $forms = [
      'CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices' => [
        'forms' => ['CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices', 'CRM_Event_Cart_Form_Checkout_Payment'],
        'controller' => [],
        'submitValues' => [
          'event' => [$eventID => ['participant' => [1 => ['email' => 'bob@example.com']]]],
          'event_' . $eventID . '_price_' . $this->_ids['price_field'][0] => $this->_ids['price_field_value'][0],
        ],
        'REQUEST' => [],
      ],
    ];
    $genericParams = [
      'credit_card_number' => 4111111111111111,
      'payment_processor_id' => $processors[0],
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => '1',
        'Y' => date('Y') + 1,
      ],
      'credit_card_type' => 'Visa',
      'billing_contact_email' => 'bobby@example.com',
      'billing_first_name' => 'John',
      'billing_middle_name' => '',
      'billing_last_name' => "O'Connor",
      'billing_street_address-5' => '8 Hobbitton Road',
      'billing_city-5' => 'The Shire',
      'billing_state_province_id-5' => 1012,
      'billing_postal_code-5' => 5010,
      'billing_country_id-5' => 1228,
    ];

    $cart = CRM_Event_Cart_BAO_Cart::find_or_create_for_current_session();
    $cart->add_event($eventID);

    foreach ($forms as $values) {
      $_REQUEST = $values['REQUEST'];
      $qfKey = NULL;
      foreach ($values['forms'] as $formName) {
        $formValues = array_merge($genericParams, $values['submitValues'], ['qfKey' => $qfKey]);
        $form = $this->getFormObject($formName, $formValues);
        $form->preProcess();
        $form->buildQuickForm();
        $form->postProcess();
        $qfKey = $form->controller->_key;
      }
    }
    $participant = \Civi\Api4\Participant::get(FALSE)
      ->addWhere('status_id:name', '=', 'Registered')
      ->execute()
      ->first();
    $this->assertEquals($cart->id, $participant['cart_id']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Registered'), $participant['status_id']);
    $this->assertRequestValid(['x_city' => 'The+Shire', 'x_state' => 'IL', 'x_amount' => 1.0]);
  }

}
