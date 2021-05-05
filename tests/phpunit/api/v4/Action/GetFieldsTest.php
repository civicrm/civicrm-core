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


namespace api\v4\Action;

use api\v4\UnitTestCase;

/**
 * @group headless
 */
class GetFieldsTest extends UnitTestCase {

  public function testComponentFields() {
    \CRM_Core_BAO_ConfigSetting::disableComponent('CiviCampaign');
    $fields = \Civi\Api4\Event::getFields()
      ->addWhere('name', 'CONTAINS', 'campaign')
      ->execute();
    $this->assertCount(0, $fields);
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $fields = \Civi\Api4\Event::getFields()
      ->addWhere('name', 'CONTAINS', 'campaign')
      ->execute();
    $this->assertCount(1, $fields);
  }

}
