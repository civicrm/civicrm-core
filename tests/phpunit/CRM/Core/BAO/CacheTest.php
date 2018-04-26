<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * Class CRM_Core_BAO_CacheTest
 * @group headless
 */
class CRM_Core_BAO_CacheTest extends CiviUnitTestCase {

  public function testSetGetItem() {
    $originalValue = array('abc' => 'def');
    CRM_Core_BAO_Cache::setItem($originalValue, __CLASS__, 'testSetGetItem');

    $return_1 = CRM_Core_BAO_Cache::getItem(__CLASS__, 'testSetGetItem');
    $this->assertEquals($originalValue, $return_1);

    // Wipe out any in-memory copies of the cache. Check to see if the SQL
    // read is correct.

    CRM_Core_BAO_Cache::$_cache = NULL;
    CRM_Utils_Cache::$_singleton = NULL;
    $return_2 = CRM_Core_BAO_Cache::getItem(__CLASS__, 'testSetGetItem');
    $this->assertEquals($originalValue, $return_2);
  }

}
