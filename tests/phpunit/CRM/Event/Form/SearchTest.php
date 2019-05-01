<?php

/**
 * @group headless
 */
class CRM_Event_Form_SearchTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->individualID = $this->individualCreate();
  }

  /**
   *  Test that search form returns correct number of rows for complex regex filters.
   */
  public function testSearch() {
    $priceFieldValues = $this->createPriceSet('event', NULL, array(
      'html_type'    => 'Radio',
      'option_label' => array('1' => 'Radio Label A (inc. GST)', '2' => 'Radio Label B (inc. GST)'),
      'option_name'  => array('1' => 'Radio Label A', '2' => 'Radio Label B'),
    ));

    $priceFieldValues = $priceFieldValues['values'];
    $participantPrice = NULL;
    foreach ($priceFieldValues as $priceFieldValue) {
      $participantPrice = $priceFieldValue;
      break;
    }

    $event = $this->eventCreate();
    $individualID = $this->individualCreate();
    $today = new DateTime();
    $this->participantCreate(array(
      'event_id'      => $event['id'],
      'contact_id'    => $individualID,
      'status_id'     => 1,
      'fee_level'     => $participantPrice['label'],
      'fee_amount'    => $participantPrice['amount'],
      'fee_currency'  => 'USD',
      'register_date' => $today->format('YmdHis'),
    ));

    $form = new CRM_Event_Form_Search();
    $form->controller = new CRM_Event_Controller_Search();
    $form->preProcess();
    $form->testSubmit(array(
      'participant_test' => 0,
      'participant_fee_id' => array(
        $participantPrice['id'],
      ),
      'radio_ts'         => 'ts_all',
    ));
    $rows = $form->controller->get('rows');
    $this->assertEquals(1, count($rows), 'Exactly one row should be returned for given price field value.');
  }

}
