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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ProductTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Product options are serialized - make sure they are consistent between
   * create/get calls
   */
  public function testProductOptions(): void {
    $options = [
      'T26-ONI-C01' => 'Small',
      'T26-ONI-C02' => 'Medium',
      'T26-ONI-C03' => 'Large',
    ];

    $p1 = $this->createTestRecord('Product', [
      'name' => 'Tshirt',
      'imageOption' => 'noImage',
      'options' => $options,
    ]);

    $p2 = \Civi\Api4\Product::get(FALSE)
      ->addSelect('options')
      ->addWhere('id', '=', $p1['id'])
      ->execute()
      ->single();

    $this->assertEquals($options, $p2['options']);
  }

}
