<?php

/**
 * @group headless
 */
class CRM_Event_Form_SearchTest extends CiviUnitTestCase {

  /**
   * @var array
   */
  private $participantPrice;

  public function setUp(): void {
    parent::setUp();
    $individualID = $this->individualCreate();
    $event = $this->eventCreatePaid();
    $priceFieldValues = $this->createPriceSet('event', $event['id'], [
      'html_type'    => 'Radio',
      'option_label' => ['1' => 'Radio Label A (inc. GST)', '2' => 'Radio Label B (inc. GST)'],
      'option_name'  => ['1' => 'Radio Label A', '2' => 'Radio Label B'],
    ]);

    $priceFieldValues = $priceFieldValues['values'];
    $this->participantPrice = reset($priceFieldValues);

    $this->callAPISuccess('Participant', 'create', [
      'event_id'  => $event['id'],
      'contact_id' => $individualID,
      'status_id.name' => 'Registered',
      'fee_level' => $this->participantPrice['label'],
      'fee_amount' => $this->participantPrice['amount'],
      'fee_currency' => 'USD',
      'register_date' => 'now',
    ]);
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test that search form returns correct number of rows for complex regex
   * filters.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearch(): void {
    $form = new CRM_Event_Form_Search();
    $form->controller = new CRM_Event_Controller_Search();
    $form->preProcess();
    $form->testSubmit([
      'participant_test' => 0,
      'participant_fee_id' => [
        $this->participantPrice['id'],
      ],
      'radio_ts'         => 'ts_all',
    ]);
    $rows = $form->controller->get('rows');
    $this->assertCount(1, $rows, 'Exactly one row should be returned for given price field value.');
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testSearchWithPriceLabelChange(): void {
    $this->callAPISuccess('PriceFieldValue', 'create', [
      'label' => 'Radio Label C',
      'id' => $this->participantPrice['id'],
    ]);
    $form = new CRM_Event_Form_Search();
    $form->controller = new CRM_Event_Controller_Search();
    $form->preProcess();
    $form->testSubmit([
      'participant_test' => 0,
      'participant_fee_id' => [
        $this->participantPrice['id'],
      ],
      'radio_ts'         => 'ts_all',
    ]);
    // Confirm that even tho we have changed the label for the price field value the query still works
    $rows = $form->controller->get('rows');
    $this->assertCount(1, $rows, 'Exactly one row should be returned for given price field value.');
  }

}
