<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
