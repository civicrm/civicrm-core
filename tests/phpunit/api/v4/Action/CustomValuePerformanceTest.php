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


namespace api\v4\Action;

use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use api\v4\Traits\QueryCounterTrait;

/**
 * @group headless
 */
class CustomValuePerformanceTest extends BaseCustomValueTest {

  use QueryCounterTrait;

  public function testQueryCount() {

    $this->markTestIncomplete();

    $customGroupId = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'MyContactFields')
      ->addValue('title', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->execute()
      ->first()['id'];

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('options', ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'])
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'FavAnimal')
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'FavLetter')
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'FavFood')
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    $this->beginQueryCount();

    Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Red')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyContactFields.FavColor', 'r')
      ->addValue('MyContactFields.FavAnimal', 'Sheep')
      ->addValue('MyContactFields.FavLetter', 'z')
      ->addValue('MyContactFields.FavFood', 'Coconuts')
      ->execute();

    Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('display_name')
      ->addSelect('MyContactFields.FavColor.label')
      ->addSelect('MyContactFields.FavColor.weight')
      ->addSelect('MyContactFields.FavColor.is_default')
      ->addSelect('MyContactFields.FavAnimal')
      ->addSelect('MyContactFields.FavLetter')
      ->addWhere('MyContactFields.FavColor', '=', 'r')
      ->addWhere('MyContactFields.FavFood', '=', 'Coconuts')
      ->addWhere('MyContactFields.FavAnimal', '=', 'Sheep')
      ->addWhere('MyContactFields.FavLetter', '=', 'z')
      ->execute()
      ->first();

    // FIXME: This count is artificially high due to the line
    // $this->entity = Tables::getBriefName(Tables::getClassForTable($targetTable));
    // In class Joinable. TODO: Investigate why.
  }

}
