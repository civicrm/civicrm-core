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
 */

/**
 * Files required.
 */
class CRM_Campaign_Form_Search_Campaign extends CRM_Core_Form {

  /**
   * Explicitly declare the entity api name.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Campaign';
  }

  /**
   * Are we forced to run a search.
   *
   * @var int
   */
  protected $_force;

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->_search = CRM_Utils_Array::value('search', $_GET);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE, FALSE);
    $this->_searchTab = CRM_Utils_Request::retrieve('type', 'String', $this, FALSE, 'campaign');

    //when we do load tab, lets load the default objects.
    $this->assign('force', ($this->_force || $this->_searchTab) ? TRUE : FALSE);
    $this->assign('searchParams', json_encode($this->get('searchParams')));
    $this->assign('buildSelector', $this->_search);
    $this->assign('searchFor', $this->_searchTab);
    $this->assign('campaignTypes', json_encode($this->get('campaignTypes')));
    $this->assign('campaignStatus', json_encode($this->get('campaignStatus')));
    $this->assign('suppressForm', TRUE);

    //set the form title.
    CRM_Utils_System::setTitle(ts('Find Campaigns'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_search) {
      return;
    }

    $customfields = self::preprocessCustomFields('Campaign');
    if (!empty($customfields)) {
      // Assign a variable to the template to notify that we have some customfields
      $this->assign('has_customfields', TRUE);
      // Also add a variable so that we can analyse it later
      $this->has_customfields = TRUE;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign');
    $this->add('text', 'campaign_title', ts('Title'), $attributes['title']);

    //campaign description.
    $this->add('text', 'description', ts('Description'), $attributes['description']);

    $this->add('datepicker', 'start_date', ts('Campaign Start Date'), [], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', ts('Campaign End Date'), [], FALSE, ['time' => FALSE]);

    //campaign type.
    $campaignTypes = CRM_Campaign_PseudoConstant::campaignType();
    $this->add('select', 'campaign_type_id', ts('Campaign Type'),
      [
        '' => ts('- select -'),
      ] + $campaignTypes
    );

    $this->set('campaignTypes', $campaignTypes);
    $this->assign('campaignTypes', json_encode($campaignTypes));

    //campaign status
    $campaignStatus = CRM_Campaign_PseudoConstant::campaignStatus();
    $this->addElement('select', 'status_id', ts('Campaign Status'),
      [
        '' => ts('- select -'),
      ] + $campaignStatus
    );
    $this->set('campaignStatus', $campaignStatus);
    $this->assign('campaignStatus', json_encode($campaignStatus));

    //active campaigns
    $this->addElement('select', 'is_active', ts('Is Active?'), [
      '' => ts('- select -'),
      '0' => ts('Yes'),
      '1' => ts('No'),
    ]);

    // Render the customfields into the form, if there are any
    if ($this->has_customfields) {
      self::renderCustomFieldsInForm($this, 'Campaign');
    }

    //build the array of all search params.
    $this->_searchParams = [];
    foreach ($this->_elements as $element) {
      if (isset($element->_attributes['name'])) {
        $name = $element->_attributes['name'];
      }
      elseif (isset($element->_name)) {
        // For some reason, a specific form element is not returning its name
        //in the attributes, therefore we need to take care of this
        $name = $element->_name;
      }
      $label = $element->_label;
      if ($name == 'qfKey') {
        continue;
      }
      $this->_searchParams[$name] = ($label) ? $label : $name;
    }
    $this->set('searchParams', $this->_searchParams);
    $this->assign('searchParams', json_encode($this->_searchParams));
  }

  /**
   * This function will enrich the customfield data with some more information
   * that is vital to us.
   *
   * TODO: implement 'is_searchable' in the function CRM_Core_BAO_CustomField::getFields
   *
   * @param string $entity 'Campaign'
   *
   * @return array
   *
   */
  public static function preprocessCustomFields($entity) {
    if (!$entity) {
      return [];
    }

    $customfields = CRM_Core_BAO_CustomField::getFields($entity);
    // We need to enrich the customfields array with some more information
    foreach ($customfields as $customfieldkey => $dnc) {
      if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $customfieldkey, 'is_searchable')) {
        $customfields[$customfieldkey]['is_searchable'] = TRUE;
      }
      else {
        $customfields[$customfieldkey]['is_searchable'] = FALSE;
      }
    }
    return $customfields;
  }

  /**
   * This function will add customfields into a form (passed as parameter)
   * and will render only the searchable ones
   *
   * Currently works for the following html types:
   *
   *   * Multi-Select State/Province (though you cannot pickup the country yet)
   *   * Multi-Select
   *   * Select
   *   * Checkbox
   *   * Radio
   *   * Text
   *
   * TODO: Dates, search ranges, multi-Select Country, Multi-Select State/Province based on Country
   *
   * @param obj $form
   * @param string $entity 'Campaign'
   *
   */
  public static function renderCustomFieldsInForm($form, $entity) {

    if (!$entity) {
      return;
    }

    $customfields = self::preprocessCustomFields($entity);

    if (is_array($customfields)) {
      foreach ($customfields as $dnc => $customFieldData) {
        // First check if customfield is searchable
        if ($customFieldData['is_searchable']) {
          $options = [];
          switch ($customFieldData['html_type']) {
            case 'Multi-Select State/Province':
              // Is this a state province ?
              if ($customFieldData['data_type'] == 'StateProvince') {
                // Fetch the state province list
                $options = CRM_Core_BAO_Address::buildOptions('state_province_id', NULL, NULL);
                $form->addElement('select', $customFieldData['name'], $customFieldData['label'], $options, ['class' => 'crm-select2', 'multiple' => TRUE, 'placeholder' => ts('- none -')]);
              }
              break;

            case 'Multi-Select':
              if ($customFieldData['option_group_id']) {
                // Get the option values from that optiongroup id
                $options = CRM_Core_OptionGroup::valuesByID(
                    $customFieldData['option_group_id'], FALSE, FALSE, FALSE, 'label', TRUE
                );
                $attributes = ['class' => 'crm-select2', 'multiple' => TRUE, 'placeholder' => ts('- none -')];
                $form->addElement('select', $customFieldData['name'], $customFieldData['label'], $options, $attributes);
              }
              break;

            case 'Select':
              if ($customFieldData['option_group_id']) {
                // Get the option values from that optiongroup id
                $options = CRM_Core_OptionGroup::valuesByID(
                    $customFieldData['option_group_id'], FALSE, FALSE, FALSE, 'label', TRUE
                );
                $attributes = ['class' => 'crm-select2', 'multiple' => FALSE, 'placeholder' => ts('- none -')];
                $form->addElement('select', $customFieldData['name'], $customFieldData['label'], $options, $attributes);
              }
              break;

            case 'CheckBox':
              if ($customFieldData['option_group_id']) {
                $options = CRM_Core_OptionGroup::valuesByID(
                    $customFieldData['option_group_id'], FALSE, FALSE, FALSE, 'label', TRUE
                );
                $form->addCheckBox($customFieldData['name'], $customFieldData['label'], array_flip($options), NULL, NULL, NULL, NULL, ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>']
                );
              }
              break;

            case 'Radio':
              if ($customFieldData['data_type'] == 'Boolean') {
                $options = ['' => '- any -', 0 => 'No', 1 => 'Yes'];
              }
              else {
                $options = CRM_Core_OptionGroup::values($customFieldData['name'], FALSE, FALSE, TRUE);
              }
              $attributes = ['placeholder' => ts('- none -')];
              $form->addElement('select', $customFieldData['name'], $customFieldData['label'], $options, $attributes);
              break;

            default:
              $form->add($customFieldData['html_type'], $customFieldData['name'], $customFieldData['label']);
          }
        }
      }
    }
  }

}
