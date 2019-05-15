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
 * Test class for the option.language API parameter in multilingual.
 *
 * @package CiviCRM
 * @group headless
 */
class api_v3_MultilingualTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  public $DBResetRequired = FALSE;

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function tearDown() {
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    parent::tearDown();
  }

  public function testOptionLanguage() {
    $this->enableMultilingual();

    CRM_Core_I18n_Schema::addLocale('fr_CA', 'en_US');

    $this->callAPISuccess('Setting', 'create', array(
      'languageLimit' => array(
        'en_US',
        'fr_CA',
      ),
    ));

    // Take a semi-random OptionGroup and test manually changing its label
    // in one language, while making sure it stays the same in English.
    $group = $this->callAPISuccess('OptionGroup', 'getsingle', array(
      'name' => 'contact_edit_options',
    ));

    $english_original = $this->callAPISuccess('OptionValue', 'getsingle', array(
      'option_group_id' => $group['id'],
      'name' => 'IM',
    ));

    $this->callAPISuccess('OptionValue', 'create', array(
      'id' => $english_original['id'],
      'name' => 'IM',
      'label' => 'Messagerie instantanée',
      'option.language' => 'fr_CA',
    ));

    $french = $this->callAPISuccess('OptionValue', 'getsingle', array(
      'option_group_id' => $group['id'],
      'name' => 'IM',
      'option.language' => 'fr_CA',
    ));

    $default = $this->callAPISuccess('OptionValue', 'getsingle', array(
      'option_group_id' => $group['id'],
      'name' => 'IM',
      'option.language' => 'en_US',
    ));

    $this->assertEquals($french['label'], 'Messagerie instantanée');
    $this->assertEquals($default['label'], $english_original['label']);
  }

  /**
   * CRM-19677: Ensure that entity apis are not affected on Multilingual setup
   *  with check_permissions = TRUE
   */
  public function testAllEntities() {
    $this->enableMultilingual();

    // list of entities which has mandatory attributes
    $specialEntities = array(
      'Attachment' => array('id' => 13),
      'CustomValue' => array('entity_id' => 13),
      'MailingContact' => array('contact_id' => 13),
      'Profile' => array('profile_id' => 13),
      'MailingGroup' => array('mailing_id' => 13),
    );
    // deprecated or API.Get is not supported/implemented
    $skippableEntities = array(
      'Logging',
      'MailingEventConfirm',
      'MailingEventResubscribe',
      'MailingEventSubscribe',
      'MailingEventUnsubscribe',
      'Location',
      'Pcp',
      'Survey',
      // throw error for help_post column
      'UFField',
      //throw error for title
      'UFGroup',
      // need loggedIn user id
      'User',
    );
    // fetch all entities
    $entities = $this->callAPISuccess('Entity', 'get', array());
    $skippableEntities = array_merge($skippableEntities, $entities['deprecated']);

    foreach ($entities['values'] as $entity) {
      $params = array('check_permissions' => 1);
      if (in_array($entity, $skippableEntities) && $entity != 'MailingGroup') {
        continue;
      }
      if (array_key_exists($entity, $specialEntities)) {
        $params = array_merge($params, $specialEntities[$entity]);
      }
      $this->callAPISuccess($entity, 'get', $params);
    }
  }

}
