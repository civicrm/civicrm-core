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
use Civi\Api4\Survey;
use Civi\Test\TransactionalInterface;

/**
 * Test Address functionality
 *
 * @group headless
 */
class SurveyTest extends Api4TestBase implements TransactionalInterface {

  public function setUp():void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    parent::setUp();
  }

  public function testGetFields(): void {
    $fields = Survey::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->execute()
      ->indexBy('name');

    // Ensure activity type options are limited to those from CiviCampaign
    $this->assertContains('Survey', $fields['activity_type_id']['options']);
    $this->assertContains('PhoneBank', $fields['activity_type_id']['options']);
    $this->assertNotContains('Meeting', $fields['activity_type_id']['options']);
    $this->assertNotContains('Contribution', $fields['activity_type_id']['options']);
  }

}
