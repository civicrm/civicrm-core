<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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



require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'tools/drupal/modules/civicrm_giftaid/GiftAid/Utils/GiftAid.php';

/**
 * Test class for GiftAid Declaration functions - civicrm_giftAid_*
 *
 *  @package   CiviCRM
 */
class DeclarationTest extends CiviUnitTestCase {

  public function dataProvider() {
    return array(
      // 01-jan-10 user makes contrib, says yes
      // input => cid, eligible, start-date, end-date
      array('ip' => array(1, 1, '2010-01-01', NULL),
        // output
        'op' => array(array(1, 1, '2010-01-01 00:00:00', '2013-01-01 00:00:00')),
      ),
      // 01-jun-10 user makes contrib, says yes
      // input
      array('ip' => array(1, 1, '2010-06-01', NULL),
        // output
        'op' => array(array(1, 1, '2010-01-01 00:00:00', '2013-06-01 00:00:00')),
      ),
      // 01-jan-11 user makes contrib, says no
      // input
      array('ip' => array(1, 0, '2011-01-11', NULL),
        // output row1
        'op' => array(array(1, 1, '2010-01-01 00:00:00', '2011-01-11 00:00:00'),
          array(1, 0, '2011-01-11 00:00:00', NULL),
          // output row2
        ),
      ),
      // 01-jun-11 user makes contrib, says no
      // input
      array('ip' => array(1, 0, '2011-06-11', NULL),
        // output row1
        'op' => array(array(1, 1, '2010-01-01 00:00:00', '2011-01-11 00:00:00'),
          array(1, 0, '2011-01-11 00:00:00', NULL),
          // output row2
        ),
      ),
      // 01-jan-12 user sets up monthly regular contrib, says yes
      // input
      array('ip' => array(1, 1, '2012-01-01', NULL),
        // output row1
        'op' => array(array(1, 1, '2010-01-01 00:00:00', '2011-01-11 00:00:00'),
          array(1, 0, '2011-01-11 00:00:00', '2012-01-01 00:00:00'),
          array(1, 1, '2012-01-01 00:00:00', '2015-01-01 00:00:00'),
          // output row2
        ),
      ),
    );
  }

  /**
   * @dataProvider dataProvider
   */
  public function testDeclarations($input, $output) {
    $count             = 0;
    $params            = $tableRows = array();
    $declarationFields = array('entity_id', 'eligible_for_gift_aid', 'start_date', 'end_date');
    foreach ($declarationFields as $field) {
      $params[$field] = $input[$count];
      $count++;
    }

    $result = GiftAid_Utils_GiftAid::setDeclaration($params);

    $sql = "select * from civicrm_value_gift_aid_declaration";
    $dao = &CRM_Core_DAO::executeQuery($sql);

    $count = 0;
    while ($dao->fetch()) {
      foreach ($declarationFields as $field) {
        $tableRows[$count][] = $dao->$field;
      }
      $count++;
    }

    $this->assertEquals($output, $tableRows, 'In line ' . __LINE__);
  }
}

