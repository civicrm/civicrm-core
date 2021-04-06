<?php

/**
 * Class CRM_Custom_Form_OptionTest
 * @group headless
 */
class CRM_Custom_Form_OptionTest extends CiviUnitTestCase {

  /**
   * Test the `name` field doesn't get changed when editing an existing option.
   */
  public function testEditCustomFieldOptionValue() {
    // Create a custom field for contacts with some option choices
    $customGroup = $this->customGroupCreate(['extends' => 'Contact', 'title' => 'contact stuff']);
    $customField = $this->customFieldOptionValueCreate($customGroup, 'myCustomField');
    $fid = $customField['id'];
    $option_group_id = $customField['values'][$fid]['option_group_id'];
    $optionValue = $this->callAPISuccess('OptionValue', 'get', [
      'option_group_id' => $option_group_id,
      'sequential' => 1,
    ])['values'][0];

    // Run the form
    $form = new CRM_Custom_Form_Option();
    $form->controller = new CRM_Core_Controller_Simple('CRM_Custom_Form_Option', 'Custom Option');

    $form->set('id', $optionValue['id']);
    $form->set('fid', $customField['id']);
    $form->set('gid', $customGroup['id']);

    ob_start();
    $form->controller->_actions['display']->perform($form, 'display');
    $contents = ob_get_contents();
    ob_end_clean();
    // We could check for something in $contents, but we don't really care
    // what the form looks like here.

    // Submit the form
    //
    // This might not work if postProcess does something like access certain
    // properties that here won't have been rebuilt from the full http post
    // etc process. But at the moment it doesn't.
    $container = &$form->controller->container();
    $container['values']['Option'] = [
      'label' => 'Label changed',
      'value' => $optionValue['value'],
      'description' => '',
      'weight' => $optionValue['value'],
      'is_active' => '1',
      // unchecked checkboxes don't submit any actual value
      // 'default_value' => $optionValue['is_default'],
      'optionId' => $optionValue['id'],
      'fieldId' => $fid,
    ];
    $form->mainProcess();

    $newOptionValue = $this->callAPISuccess('OptionValue', 'get', [
      'id' => $optionValue['id'],
    ])['values'][$optionValue['id']];
    $this->assertEquals($optionValue['name'], $newOptionValue['name']);
    $this->assertEquals($optionValue['value'], $newOptionValue['value']);
    $this->assertEquals('Label changed', $newOptionValue['label']);
  }

}
