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


namespace api\v4\Query;

use Civi\Api4\Query\Api4SelectQuery;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class OptionValueJoinTest extends UnitTestCase {

  public function setUpHeadless() {
    $relatedTables = [
      'civicrm_address',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_openid',
      'civicrm_im',
      'civicrm_website',
      'civicrm_activity',
      'civicrm_activity_contact',
    ];

    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    $this->loadDataSet('SingleContact');

    return parent::setUpHeadless();
  }

  public function testCommunicationMethodJoin() {
    $query = new Api4SelectQuery('Contact', FALSE, civicrm_api4('Contact', 'getFields', ['includeCustom' => FALSE, 'checkPermissions' => FALSE, 'action' => 'get'], 'name'));
    $query->select[] = 'first_name';
    $query->select[] = 'preferred_communication_method.label';
    $query->where[] = ['preferred_communication_method', 'IS NOT NULL'];
    $results = $query->run();
    $first = array_shift($results);
    $firstPreferredMethod = array_shift($first['preferred_communication_method']);

    $this->assertEquals(
      'Phone',
      $firstPreferredMethod['label']
    );
  }

}
