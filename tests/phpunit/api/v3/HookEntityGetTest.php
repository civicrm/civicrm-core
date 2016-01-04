<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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

/**
 * Class api_v3_HookEntityGetTest
 */
class api_v3_HookEntityGetTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  private $hookEntity;
  private $mergeQuery;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    CRM_Utils_Hook::singleton()->setHook('civicrm_entityGet', array($this, 'hook_civicrm_entityGet'));
  }

  public function testEmailGetWithAdditionalClause() {
    $this->hookEntity = 'Email';
    $this->mergeQuery = NULL;

    $cid = $this->individualCreate();
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $cid, 'api.Email.get' => 1));
    $email = $contact['api.Email.get']['id'];

    $this->mergeQuery = CRM_Utils_SQL_Select::fragment()->where("a.contact_id = $cid");

    $result = $this->callAPISuccess('Email', 'get', array('id' => $email));
    $this->assertEquals($email, $result['id']);

    $this->mergeQuery = CRM_Utils_SQL_Select::fragment()->where("a.location_type_id = 99");

    $result = $this->callAPISuccess('Email', 'get', array('id' => $email));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * @param string $entity
   * @param CRM_Utils_SQL_Select $query
   * @param bool $checkPermissions
   */
  public function hook_civicrm_entityGet($entity, $query, $checkPermissions) {
    if ($entity == $this->hookEntity && $this->mergeQuery) {
      $query->merge($this->mergeQuery);
    }
  }

}
