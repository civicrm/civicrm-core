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
 * Test class for API functions.
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_CustomApiTest extends CiviUnitTestCase {

  protected $_apiversion = 3;

  public function setUp() {
    parent::setUp();
    $this->installApi();
  }

  public function tearDown() {
    parent::tearDown();
    CRM_Core_DAO::executeQuery('DROP TABLE civicrm_mailing_provider_data');
  }

  /**
   * Test that a custom api, as one would add in an extension works.
   *
   * This api is a bit 'special' in that it has a composite primary key rather
   * than using 'id', make sure that works too....
   */
  public function testCustomApi() {
    $this->installApi();
    $this->callAPISuccess('MailingProviderData', 'create', array(
      'contact_identifier' => 'xyz',
      'mailing_identifier' => 'abx',
    ));
    $this->callAPISuccess('Mailing', 'create', array('name' => 'CiviMail', 'hash' => 'abx'));
    $result = $this->callAPISuccess('MailingProviderData', 'get', array('return' => array('mailing_identifier.name', 'contact_identifier', 'mailing_identifier')));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals('xyzabx2017-01-01 00:00:00', $result['id']);
    $this->assertEquals('xyzabx2017-01-01 00:00:00', $result['id']);
    $this->assertEquals(array(
      'contact_identifier' => 'xyz',
      'mailing_identifier' => 'abx',
      'mailing_identifier.name' => 'CiviMail',
    ), reset($result['values']));
  }

  /**
   * * Implements hook_civicrm_EntityTypes().
   *
   * @param array $entityTypes
   */
  public function hookEntityTypes(&$entityTypes) {
    $entityTypes['CRM_Omnimail_DAO_MailingProviderData'] = array(
      'name' => 'MailingProviderData',
      'class' => 'CRM_Omnimail_DAO_MailingProviderData',
      'table' => 'civicrm_maiing_provider_data',
    );
  }

  /**
   * Install the custom api.
   */
  public function installApi() {
    require_once __DIR__ . '/custom_api/MailingProviderData.php';
    $this->hookClass->setHook('civicrm_entityTypes', array($this, 'hookEntityTypes'));
    CRM_Core_DAO_AllCoreTables::init(TRUE);
    CRM_Core_DAO::executeQuery(
      "CREATE TABLE IF NOT EXISTS `civicrm_mailing_provider_data` (
    `contact_identifier` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
   `mailing_identifier` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   `event_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `recipient_action_datetime` timestamp NOT NULL DEFAULT '2017-01-01 00:00:00',
   `contact_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   `is_civicrm_updated`  TINYINT(4) DEFAULT '0',
 PRIMARY KEY (`contact_identifier`,`recipient_action_datetime`,`event_type`),
   KEY `contact_identifier` (`contact_identifier`),
   KEY `mailing_identifier` (`mailing_identifier`),
   KEY `contact_id` (`contact_id`),
   KEY `email` (`email`),
   KEY `event_type` (`event_type`),
   KEY `recipient_action_datetime` (`recipient_action_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
    );
  }

}
