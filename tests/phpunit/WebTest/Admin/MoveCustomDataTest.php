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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Admin_MoveCustomDataTest
 */
class WebTest_Admin_MoveCustomDataTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCreateCustomFields() {
    $this->webtestLogin();

    $cid_all = $this->_createContact("all_data", "move_custom_data");
    $cid_from_missing = $this->_createContact("source_missing", "move_custom_data");
    $cid_to_missing = $this->_createContact("destination_missing", "move_custom_data");

    $from_group_id = $this->_buildCustomFieldSet("source");
    CRM_Utils_System::flushCache();
    $to_group_id = $this->_buildCustomFieldSet("destination");

    $this->_fillCustomDataForContact($cid_all, $from_group_id);
    $this->_fillCustomDataForContact($cid_to_missing, $from_group_id);

    $this->_fillCustomDataForContact($cid_all, $to_group_id);
    $this->_fillCustomDataForContact($cid_from_missing, $to_group_id);

    //to verify data hasn't been lost, we load the values for each contact
    $pre_move_values = array();
    $pre_move_values[$cid_all]['source'] = $this->_loadDataFromApi($cid_all, $from_group_id);
    $pre_move_values[$cid_all]['destination'] = $this->_loadDataFromApi($cid_all, $to_group_id);
    $pre_move_values[$cid_from_missing]['source'] = $this->_loadDataFromApi($cid_from_missing, $from_group_id);
    $pre_move_values[$cid_from_missing]['destination'] = $this->_loadDataFromApi($cid_from_missing, $to_group_id);
    $pre_move_values[$cid_to_missing]['source'] = $this->_loadDataFromApi($cid_to_missing, $from_group_id);
    $pre_move_values[$cid_to_missing]['destination'] = $this->_loadDataFromApi($cid_to_missing, $to_group_id);

    //ok, so after all that setup, we are now good to actually move a field

    //first, pick a random field from the source group to move
    $fields = $this->webtest_civicrm_api("CustomField", "get", array('custom_group_id' => $from_group_id));
    $field_to_move = array_rand($fields['values']);

    //move the field
    $this->_moveCustomField($field_to_move, $from_group_id, $to_group_id);

    //now lets verify the data, load up the new values from the api...
    $post_move_values = array();
    $post_move_values[$cid_all]['source'] = $this->_loadDataFromApi($cid_all, $from_group_id, TRUE);
    $post_move_values[$cid_all]['destination'] = $this->_loadDataFromApi($cid_all, $to_group_id);
    $post_move_values[$cid_from_missing]['source'] = $this->_loadDataFromApi($cid_from_missing, $from_group_id);
    $post_move_values[$cid_from_missing]['destination'] = $this->_loadDataFromApi($cid_from_missing, $to_group_id);
    $post_move_values[$cid_to_missing]['source'] = $this->_loadDataFromApi($cid_to_missing, $from_group_id);
    $post_move_values[$cid_to_missing]['destination'] = $this->_loadDataFromApi($cid_to_missing, $to_group_id);

    // Make sure that only the appropriate values have changed.
    foreach (array(
               $cid_all,
               $cid_from_missing,
               $cid_to_missing,
             ) as $cid) {
      foreach (array(
                 'source',
                 'destination',
               ) as $fieldset) {
        foreach ($pre_move_values[$cid][$fieldset] as $id => $value) {
          if ($id != $field_to_move) {
            //All fields that were there should still be there
            $this->assertTrue(isset($post_move_values[$cid][$fieldset][$id]), "A custom field that was not moved is missing!");
            //All fields should have the same value as when we started
            $this->assertTrue($post_move_values[$cid][$fieldset][$id] == $value, "A custom field value has changed in the source custom field set");
          }
        }
      }
      //check that the field is actually moved
      $this->assertTrue(!isset($post_move_values[$cid]['source'][$field_to_move]), "Moved field is still present in the source fieldset");
      $this->assertTrue(isset($post_move_values[$cid]['destination'][$field_to_move]), "Moved field is not present in the destination fieldset");
      $this->assertTrue($pre_move_values[$cid]['source'][$field_to_move] == $post_move_values[$cid]['destination'][$field_to_move], "The moved field has changed values!");
    }

    //Go to the contacts page and check that the custom field is in the right group
    $this->openCiviPage('contact/view', "reset=1&cid={$cid_all}");

    //load the names of the custom fieldsets
    $source = $this->webtest_civicrm_api("CustomGroup", "get", array('id' => $from_group_id));
    $source = $source['values'][$from_group_id];
    $destination = $this->webtest_civicrm_api("CustomGroup", "get", array('id' => $to_group_id));
    $destination = $destination['values'][$to_group_id];

    //assert that the moved custom field is missing from the source fieldset
    $this->assertElementNotContainsText("css=div." . $source['name'], $fields['values'][$field_to_move]['label'], "Moved value still displays in the old fieldset on the contact record");
    $this->assertElementContainsText("css=div." . $destination['name'], $fields['values'][$field_to_move]['label'], "Moved value does not display in the new fieldset on the contact record");
  }

  /**
   * moves a field from one field to another.
   * @param $field_to_move
   * @param int $from_group_id
   * @param int $to_group_id
   */
  public function _moveCustomField($field_to_move, $from_group_id, $to_group_id) {
    //go to the move field page
    $this->openCiviPage('admin/custom/group/field/move', "reset=1&fid={$field_to_move}");

    //select the destination field set from select box
    $this->click("dst_group_id");
    $this->select("dst_group_id", "value=" . $to_group_id);
    $this->click("//option[@value='" . $to_group_id . "']");

    //click the save button
    $this->click("_qf_MoveField_next");

    //assert that the success text is present
    $this->waitForText('crm-notification-container', "has been moved");

    //assert that the custom field not on old data set page

    $this->assertTrue(!$this->isElementPresent("CustomField-" . $field_to_move), "The moved custom field still displays on the old fieldset page");

    //go to the destination fieldset and make sure the field is present
    $this->openCiviPage('admin/custom/group/field', "reset=1&action=browse&gid={$to_group_id}");
    $this->assertTrue($this->isElementPresent("CustomField-" . $field_to_move), "The moved custom field does not display on the new fieldset page");
  }

  /**
   * create a contact and return the contact id.
   * @param string $firstName
   * @param string $lastName
   *
   * @return mixed
   */
  public function _createContact($firstName = "John", $lastName = "Doe") {
    $firstName .= "_" . substr(sha1(rand()), 0, 5);
    $lastName .= "_" . substr(sha1(rand()), 0, 5);
    $this->webtestAddContact($firstName, $lastName);
    $url = $this->parseURL();
    $cid = $url['queryString']['cid'];
    $this->assertType('numeric', $cid);
    return $cid;
  }

  /**
   * Get all custom field values for a given contact and custom group id using the api.
   * @param int $contact_id
   * @param int $group_id
   * @param bool $reset_cache
   *
   * @return array
   */
  public function _loadDataFromApi($contact_id, $group_id, $reset_cache = FALSE) {
    // cache the fields, just to speed things up a little
    static $field_ids = array();

    if ($reset_cache) {
      $field_ids = array();
    }

    //if the field ids havent been cached yet, grab them
    if (!isset($field_ids[$group_id])) {
      $fields = $this->webtest_civicrm_api("CustomField", "get", array('custom_group_id' => $group_id));
      $field_ids[$group_id] = array();
      foreach ($fields['values'] as $id => $field) {
        $field_ids[$group_id][] = $id;
      }
    }

    $params = array('contact_id' => $contact_id);
    foreach ($field_ids[$group_id] as $id) {
      $params['return.custom_' . $id] = 1;
    }

    $contact = $this->webtest_civicrm_api("Contact", "get", $params);

    //clean up the api results a bit....
    $results = array();
    foreach ($field_ids[$group_id] as $id) {
      if (isset($contact['values'][$contact_id]['custom_' . $id])) {
        $results[$id] = $contact['values'][$contact_id]['custom_' . $id];
      }
    }

    return $results;
  }

  /**
   * creates a new custom group and fields in that group, and returns the group Id
   * @param $prefix
   *
   * @return null
   */
  public function _buildCustomFieldset($prefix) {
    $group_id = $this->_createCustomGroup($prefix);
    $field_ids[] = $this->_addCustomFieldToGroup($group_id, 'Alphanumeric', "CheckBox", $prefix);
    $field_ids[] = $this->_addCustomFieldToGroup($group_id, 'Alphanumeric', "Radio", $prefix);
    $field_ids[] = $this->_addCustomFieldToGroup($group_id, 'Alphanumeric', "Text", $prefix);
    $field_ids[] = $this->_addCustomFieldToGroup($group_id, 'Note', "Text", $prefix);
    $field_ids[] = $this->_addCustomFieldToGroup($group_id, 'Date', "Date", $prefix);
    return $group_id;
  }

  /**
   * Creates a custom field group for a specific entity type and returns the custom group Id.
   * @param string $prefix
   * @param string $entity
   *
   * @return null
   */
  public function _createCustomGroup($prefix = "custom", $entity = "Contact") {

    $this->openCiviPage('admin/custom/group', 'action=add&reset=1');

    //fill custom group title
    $customGroupTitle = $prefix . '_' . substr(sha1(rand()), 0, 7);
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=" . $entity);
    $this->click("//option[@value='" . $entity . "']");
    $this->click("_qf_Group_next-bottom");
    $this->waitForElementPresent("_qf_Field_cancel-bottom");

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");

    return $this->urlArg('gid');
  }

  /**
   * Adds a new custom field to a specfied custom field group, using the given
   * datatype and widget.
   * @param int $group_id
   * @param string $type
   * @param string $widget
   * @param string $prefix
   *
   * @return mixed
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  public function _addCustomFieldToGroup($group_id, $type = 'Alphanumeric', $widget = 'CheckBox', $prefix = '') {
    //A mapping of data type names to integer keys
    $type_map = array(
      'alphanumeric' => array(
        'id' => 0,
        'widgets' => array('Text', 'Select', 'Radio', 'CheckBox', 'Multi-Select'),
        'options' => array(
          'option_01',
          'option_02',
          'option_03',
          'option_04',
          'option_05',
          'option_06',
          'option_07',
          'option_08',
          'option_09',
          'option_10',
        ),
      ),
      'integer' => array(
        'id' => 1,
        'widgets' => array('Text', 'Select', 'Radio'),
        'options' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10),
      ),
      'number' => array(
        'id' => 2,
        'widgets' => array('Text', 'Select', 'Radio'),
        'options' => array(1.01, 2.02, 3.03, 4.04, 5.05, 6.06, 7.07, 8.08 . 9.09, 10.1),
      ),
      'money' => array(
        'id' => 3,
        'widgets' => array('Text', 'Select', 'Radio'),
        'options' => array(1.01, 2.02, 3.03, 4.04, 5.05, 6.06, 7.07, 8.08 . 9.09, 10.1),
      ),
      'note' => array(
        'id' => 4,
        'widgets' => array('TextArea'),
      ),
      'date' => array(
        'id' => 5,
        'widgets' => array('Date'),
      ),
      'yes or no' => array(
        'id' => 6,
        'widgets' => array('Radio'),
      ),
      //'state/province'    => array(
      //  'id' => 7,
      //),
      //'country'           => array(
      //  'id' => 8,
      //),
      //'link'              => array(
      //  'id' => 10,
      //),
      //'contact reference' => array(
      //  'id' => 11,
      //),
      //'file' => 9, hahaha im not doing files.
    );

    //downcase the type
    $type = strtolower($type);

    //make sure that a supported html type was entered
    if (!isset($type_map[$type])) {
      $this->fail("The custom field html type $type is not supported.  Supported types are: " . implode(", ", array_keys($type_map)));
    }
    $html_type_id = $type_map[$type]['id'];

    //make sure the widget type can be used for this data type
    //if an invalid widget is selected and only 1 widget is available, use that
    if (!in_array($widget, $type_map[$type]['widgets'])) {
      if (count($type_map[$type]['widgets']) == 1) {
        $widget = $type_map[$type]['widgets'][0];
      }
      else {
        $this->fail("Cannot use $widget for $type fields.  Available widgets are: " . implode(", ", $type_map[$type]['widgets']));
      }
    }

    //Go to the add custom field page for the given group id
    $this->openCiviPage('admin/custom/group/field/add', "action=add&reset=1&gid={$group_id}");

    //Do common setup for all field types

    //set the field label
    $fieldLabel = (isset($prefix) ? $prefix . "_" : "") . $widget . "_" . substr(sha1(rand()), 0, 6);
    $this->click("label");
    $this->type("label", $fieldLabel);

    //enter pre help message
    $this->type("help_pre", "this is field pre help for " . $fieldLabel);

    //enter post help message
    $this->type("help_post", "this field post help for " . $fieldLabel);

    //Is searchable?
    $this->click("is_searchable");

    //Fill in the html type and widget type
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=" . $html_type_id);
    $this->click("//option[@value='" . $html_type_id . "']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=" . $widget);
    $this->click("//option[@value='" . $widget . "']");

    //fill in specific elements for different widgets
    switch ($widget) {
      case 'CheckBox':
        $this->_createFieldOptions(rand(3, 7), 'option', $type_map[$type]['options']);
        $this->type("options_per_line", "2");
        break;

      case 'Radio':
        $this->_createFieldOptions(rand(3, 7), 'option', $type_map[$type]['options']);
        $this->type("options_per_line", "1");
        break;

      case 'Date':
        $this->click("date_format");
        $this->select("date_format", "value=yy-mm-dd");
        $this->click("//option[@value='yy-mm-dd']");
        break;

      //TODO allow for more things....
    }

    //clicking save
    $this->click("_qf_Field_done-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created?
    $this->waitForText('crm-notification-container', "Custom field '$fieldLabel' has been saved.");

    //get the custom id of the custom field that was just created
    $results = $this->webtest_civicrm_api("CustomField", "get", array(
        'label' => $fieldLabel,
        'custom_group_id' => $group_id,
      ));
    //While there _technically_ could be two fields with the same name, its highly unlikely
    //so assert that exactly one result is return
    $this->assertTrue($results['count'] == 1, "Could not uniquely get custom field id");
    return $results['id'];
  }

  /**
   * Populates $count options for a custom field on the add custom field page
   * @param int $count
   * @param string $prefix
   * @param array $values
   */
  public function _createFieldOptions($count = 3, $prefix = "option", $values = array()) {
    // Only support up to 10 options on the creation form
    $count = $count > 10 ? 10 : $count;

    for ($i = 1; $i <= $count; $i++) {
      $label = $prefix . '_' . substr(sha1(rand()), 0, 6);
      $this->type("option_label_" . $i, $label);
      $this->type("option_value_" . $i, (isset($values[$i]) ? $values[$i] : $i));
    }
  }

  /**
   * randomly generates data for a specific custom field.
   * @param int $contact_id
   * @param int $group_id
   */
  public function _fillCustomDataForContact($contact_id, $group_id) {
    //edit the given contact
    $this->openCiviPage('contact/add', "reset=1&action=update&cid={$contact_id}");

    $this->click("expand");
    $this->waitForElementPresent("address_1_street_address");

    //get the custom fields for the group
    $fields = $this->webtest_civicrm_api("CustomField", "get", array('custom_group_id' => $group_id));
    $fields = $fields['values'];

    //we need the id the contact's record in the table for this custom group.
    //Recent (4.0.6+, i think?) versions of the api return this when getting
    //custom data for a contact.  So we do that.
    $field_ids = array_keys($fields);
    $contact = $this->webtest_civicrm_api("Contact", "get", array(
        'contact_id' => $contact_id,
        'return.custom_' . $field_ids[0] => 1,
      ));
    $group = $this->webtest_civicrm_api("CustomGroup", "get", array('id' => $group_id, 'return.table_name' => 1));

    //if the contact has not been saved since this fieldset has been creative,
    //the form uses id = -1. In this case the table pk wont be in the api results
    $customValueId = $contact['values'][$contact_id][$group['values'][$group_id]['table_name'] . "_id"];
    if (isset($customValueId) && !empty($customValueId)) {
      $table_pk = $customValueId;
    }
    else {
      $table_pk = -1;
    }

    //fill a value in for each field
    foreach ($fields as $field_id => $field) {
      //if there is an option group id, we grab the labels and select on randomly
      if ($field['data_type'] == 'Date') {
        $this->webtestFillDate("custom_" . $field['id'] . "_" . $table_pk, "+1 week");
      }
      elseif (isset($field['option_group_id'])) {
        $options = $this->webtest_civicrm_api("OptionValue", "get", array('option_group_id' => $field['option_group_id']));
        $options = $options['values'];
        $pick_me = $options[array_rand($options)]['label'];
        $this->click("xpath=//table//tr/td/label[text()=\"$pick_me\"]");
      }
      else {
        //gonna go ahead and assume its an alphanumeric text question.  This
        //will really only work if the custom data group has not yet been
        //filled out for this contact
        $this->type("custom_" . $field['id'] . '_' . $table_pk, sha1(rand()));
      }
    }

    //save the form
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //assert success
    $this->waitForText('crm-notification-container', "has been updated");
  }

}
