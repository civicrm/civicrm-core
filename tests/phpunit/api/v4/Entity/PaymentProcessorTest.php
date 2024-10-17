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
use Civi\Api4\PaymentProcessor;

/**
 * @group headless
 */
class PaymentProcessorTest extends Api4TestBase {

  public function testGetFields(): void {
    $fields = PaymentProcessor::getFields(FALSE)
      ->execute()->indexBy('name');

    $this->assertFalse($fields['title']['required']);
    $this->assertSame('empty($values.frontend_title) && empty($values.name)', $fields['title']['required_if']);

    $this->assertFalse($fields['frontend_title']['required']);
    $this->assertSame('empty($values.title) && empty($values.name)', $fields['frontend_title']['required_if']);
  }

}
