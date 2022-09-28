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
  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function tearDown(): void {
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    parent::tearDown();
  }

  /**
   * @dataProvider versionThreeAndFour
   */
  public function testOptionLanguage($version) {
    $this->enableMultilingual();
    $this->_apiversion = $version;

    CRM_Core_I18n_Schema::addLocale('fr_CA', 'en_US');

    $this->callAPISuccess('Setting', 'create', [
      'languageLimit' => [
        'en_US' => 1,
        'fr_CA' => 1,
      ],
    ]);

    // Take a semi-random OptionGroup and test manually changing its label
    // in one language, while making sure it stays the same in English.
    $group = $this->callAPISuccess('OptionGroup', 'getsingle', [
      'name' => 'contact_edit_options',
    ]);

    $english_original = $this->callAPISuccess('OptionValue', 'getsingle', [
      'option_group_id' => $group['id'],
      'name' => 'IM',
    ]);

    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $english_original['id'],
      'name' => 'IM',
      'label' => 'Messagerie instantanée',
      'option.language' => 'fr_CA',
    ]);

    $french = $this->callAPISuccess('OptionValue', 'getsingle', [
      'option_group_id' => $group['id'],
      'name' => 'IM',
      'options' => ['language' => 'fr_CA'],
    ]);

    // Ensure that after language is changed in previous call it will go back to the default.
    $default = $this->callAPISuccess('OptionValue', 'getsingle', [
      'option_group_id' => $group['id'],
      'name' => 'IM',
    ]);

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
    $specialEntities = [
      'Attachment' => ['id' => 13],
      'CustomValue' => ['entity_id' => 13],
      'MailingContact' => ['contact_id' => 13],
      'Profile' => ['profile_id' => 13],
      'MailingGroup' => ['mailing_id' => 13],
    ];
    // deprecated or API.Get is not supported/implemented
    $skippableEntities = [
      'Cxn',
      'CxnApp',
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
    ];
    // fetch all entities
    $entities = $this->callAPISuccess('Entity', 'get', []);
    $skippableEntities = array_merge($skippableEntities, $entities['deprecated']);

    foreach ($entities['values'] as $entity) {
      $params = ['check_permissions' => 1];
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
