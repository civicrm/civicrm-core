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
  * @group headless
  */
class CRM_Contact_Form_ContactTest extends CiviUnitTestCase {

  /**
   * Contact ID.
   *
   * @var int
   */
  protected $_individualId;

  /**
   * Test postProcess failure.
   *
   * In unit tests, the CMS user creation will always fail, but that's
   * ok because that's what we're testing here.
   */
  public function testContactFormRuleDuplicate() {

    //Make sure the default hasn't changed ('While Typing').
    $checkSimilar = Civi::settings()->get('contact_ajax_check_similar');
    $this->assertEquals(1, $checkSimilar);

    //Create our individual we will test for dupes against.
    $fields = [
      'first_name' => 'Bob',
      'middle_name' => "connie",
      'last_name' => 'dobbs',
      'contact_type' => 'Individual',
      'email' => 'bob@example.com',
      'group' => '',
    ];

    $this->_individualId = $this->individualCreate($fields);

    //Create a custom Supervised rule.
    $ruleGroup = $this->callAPISuccessGetSingle('RuleGroup', ['is_reserved' => 1, 'contact_type' => 'Individual', 'used' => 'Supervised']);

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'id' => $ruleGroup['id'],
      'used' => "General",
    ]);

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 10,
      'used' => 'Supervised',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $ruleGroup['id'],
      'rule_table' => 'civicrm_contact',
      'rule_weight' => 10,
      'rule_field' => 'middle_name',
    ]);

    //Our individual which should match on the middle name.
    $fields = [
      'first_name' => 'Greg',
      'middle_name' => "connie",
      'last_name' => 'Carrter',
      'contact_type' => 'Individual',
      'email' => 'ha@example.com',
      'group' => '',
    ];

    $_REQUEST['reset'] = 1;
    $_REQUEST['ct'] = 'Individual';
    $form = $this->getFormObject('CRM_Contact_Form_Contact', $fields);

    $errors = [];
    $contactId = 0;
    $contactType = $fields['contact_type'];

    //'While Typing'
    $errors = [];
    $form->formRule($fields, $errors, $contactId, $contactType);
    $this->assertRegExp(';One matching contact was found.;', $errors['_qf_default']);

    //'When Saving'
    Civi::settings()->set('contact_ajax_check_similar', 0);
    $errors = [];
    $form->formRule($fields, $errors, $contactId, $contactType);
    $this->assertRegExp(';One matching contact was found.;', $errors['_qf_default']);

    //'Never'
    Civi::settings()->set('contact_ajax_check_similar', 2);
    $errors = [];
    $form->formRule($fields, $errors, $contactId, $contactType);
    $this->assertEmpty($errors);

  }

}
