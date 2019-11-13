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
 * $Id$
 *
 */


namespace api\v4\Spec;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class RequestSpecTest extends UnitTestCase {

  public function testRequiredFieldFetching() {
    $spec = new RequestSpec('Contact', 'get');
    $requiredField = new FieldSpec('name', 'Contact');
    $requiredField->setRequired(TRUE);
    $nonRequiredField = new FieldSpec('age', 'Contact', 'Integer');
    $nonRequiredField->setRequired(FALSE);
    $spec->addFieldSpec($requiredField);
    $spec->addFieldSpec($nonRequiredField);

    $requiredFields = $spec->getRequiredFields();

    $this->assertCount(1, $requiredFields);
    $this->assertEquals('name', array_shift($requiredFields)->getName());
  }

  public function testGettingFieldNames() {
    $spec = new RequestSpec('Contact', 'get');
    $nameField = new FieldSpec('name', 'Contact');
    $ageField = new FieldSpec('age', 'Contact', 'Integer');
    $spec->addFieldSpec($nameField);
    $spec->addFieldSpec($ageField);

    $fieldNames = $spec->getFieldNames();

    $this->assertCount(2, $fieldNames);
    $this->assertEquals(['name', 'age'], $fieldNames);
  }

}
