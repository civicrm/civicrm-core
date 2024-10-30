<?php

use Civi\Api4\Discount;
use Civi\Test\EventTestTrait;

/**
 * Class CRM_Core_BAO_DiscountTest
 * @group headless
 */
class CRM_Core_BAO_DiscountTest extends CiviUnitTestCase {

  use EventTestTrait;

  /**
   * Test the discount getOptions filters by event.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDiscountGetOptions(): void {
    $this->eventCreatePaid();
    $this->addDiscountPriceSet();
    $options = Discount::getFields(TRUE)
      ->setLoadOptions(TRUE)
      ->addValue('entity_id', 1)
      ->addValue('entity_table', 'civicrm_event')
      ->addWhere('name', '=', 'price_set_id')
      ->execute()->first()['options'];
    $this->assertEquals([$this->ids['PriceSet']['discount']], array_keys($options));
  }

}
