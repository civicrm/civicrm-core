<?php

/**
 * @group headless
 */
class CRM_Event_Form_SearchTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $individualID = $this->individualCreate();
    $event = $this->eventCreatePaid();
    $this->createPriceSet('event', $event['id'], [
      'html_type'    => 'Radio',
      'option_label' => ['1' => 'Radio Label A (inc. GST)', '2' => 'Radio Label B (inc. GST)'],
      'option_name'  => ['1' => 'Radio Label A', '2' => 'Radio Label B'],
    ]);

    $this->callAPISuccess('Participant', 'create', [
      'event_id'  => $event['id'],
      'contact_id' => $individualID,
      'status_id.name' => 'Registered',
      'fee_level' => 'Radio Label A (inc. GST)',
      'fee_amount' => 100,
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
   */
  public function testSearch(): void {
    $this->getTestForm('CRM_Event_Form_Search', [
      'participant_test' => 0,
      'participant_fee_id' => [$this->ids['PriceFieldValue'][0]],
      'radio_ts' => 'ts_all',
    ])->processForm();
    $rows = $this->getValueSetOnForm('rows');
    $this->assertCount(1, $rows, 'Exactly one row should be returned for given price field value.');
  }

  /**
   * Confirm that even tho we have changed the label for the price field value the query still works.
   */
  public function testSearchWithPriceLabelChange(): void {
    $this->callAPISuccess('PriceFieldValue', 'create', [
      'label' => 'Radio Label C',
      'id' => $this->ids['PriceFieldValue'][0],
    ]);
    $this->getTestForm('CRM_Event_Form_Search', [
      'participant_test' => 0,
      'participant_fee_id' => [$this->ids['PriceFieldValue'][0]],
      'radio_ts' => 'ts_all',
    ])->processForm();
    $rows = $this->getValueSetOnForm('rows');
    $this->assertCount(1, $rows, 'Exactly one row should be returned for given price field value.');
  }

}
