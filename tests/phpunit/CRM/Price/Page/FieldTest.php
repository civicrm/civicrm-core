<?php

/**
 * @group headless
 */
class CRM_Price_Page_FieldTest extends CiviUnitTestCase {

  protected function tearDown(): void {
    $this->disableTaxAndInvoicing();
    $this->quickCleanUpFinancialEntities();
    $_REQUEST = [];
    parent::tearDown();
  }

  /**
   * The price field list shows the tax of a taxed text field.
   */
  public function testTaxColumnsForTextField(): void {
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'));
    $priceSet = $this->createTestEntity('PriceSet', [
      'title' => 'Taxed price set',
      'name' => 'taxed_price_set',
      'extends:name' => ['CiviEvent'],
      'financial_type_id:name' => 'Donation',
    ]);
    $priceField = $this->createTestEntity('PriceField', [
      'price_set_id' => $priceSet['id'],
      'label' => 'Ticket',
      'name' => 'ticket',
      'html_type' => 'Text',
      'financial_type_id:name' => 'Donation',
    ]);
    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $priceField['id'],
      'label' => 'Ticket',
      'name' => 'ticket',
      'amount' => 100,
      'financial_type_id:name' => 'Donation',
    ]);

    $_REQUEST = ['sid' => $priceSet['id'], 'action' => 'browse', 'reset' => 1];
    $page = new CRM_Price_Page_Field();
    ob_start();
    $page->run();
    $html = ob_get_clean();

    $this->assertStringContainsString('Sales Tax (10.00%)', $html);
    $this->assertStringContainsString('$10.00', $html);
  }

}
