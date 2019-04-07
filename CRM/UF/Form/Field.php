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
 * Form to process actions on the field aspect of Custom.
 */
class CRM_UF_Form_Field extends CRM_Core_Form {

  /**
   * The uf group id saved to the session for an update.
   *
   * @var int
   */
  protected $_gid;

  /**
   * The field id, used when editing the field.
   *
   * @var int
   */
  protected $_id;

  /**
   * The set of fields that we can view/edit in the user field framework
   *
   * @var array
   */
  protected $_fields;

  /**
   * The title for field.
   *
   * @var int
   */
  protected $_title;

  /**
   * The set of fields sent to the select element.
   *
   * @var array
   */
  protected $_selectFields;

  /**
   * store fields with if locationtype exits status.
   *
   * @var array
   */
  protected $_hasLocationTypes;

  /**
   * Is this profile has searchable field.
   * or is any field having in selector true.
   *
   * @var boolean.
   */
  protected $_hasSearchableORInSelector;

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    if ($this->_gid) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'title');

      $this->setPageTitle(ts('Profile Field'));

      $url = CRM_Utils_System::url('civicrm/admin/uf/group/field',
        "reset=1&action=browse&gid={$this->_gid}"
      );

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($url);
      $breadCrumb = [
        [
          'title' => ts('CiviCRM Profile Fields'),
          'url' => $url,
        ],
      ];
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
    }

    $showBestResult = CRM_Utils_Request::retrieve('sbr', 'Positive', CRM_Core_DAO::$_nullArray);
    if ($showBestResult) {
      $this->assign('showBestResult', $showBestResult);
    }

    $this->_fields = CRM_Contact_BAO_Contact::importableFields('All', TRUE, TRUE, TRUE, TRUE, TRUE);
    $this->_fields = array_merge(CRM_Activity_BAO_Activity::exportableFields('Activity'), $this->_fields);

    //unset campaign related fields.
    if (isset($this->_fields['activity_campaign_id'])) {
      $this->_fields['activity_campaign_id']['title'] = ts('Campaign');
      if (isset($this->_fields['activity_campaign'])) {
        unset($this->_fields['activity_campaign']);
      }
    }

    if (CRM_Core_Permission::access('CiviContribute')) {
      $this->_fields = array_merge(CRM_Contribute_BAO_Contribution::getContributionFields(FALSE), $this->_fields);
      $this->_fields = array_merge(CRM_Core_BAO_UFField::getContribBatchEntryFields(), $this->_fields);
    }

    if (CRM_Core_Permission::access('CiviMember')) {
      $this->_fields = array_merge(CRM_Member_BAO_Membership::getMembershipFields(), $this->_fields);
    }

    if (CRM_Core_Permission::access('CiviEvent')) {
      $this->_fields = array_merge(CRM_Event_BAO_Query::getParticipantFields(), $this->_fields);
    }

    if (CRM_Core_Permission::access('CiviCase')) {
      $this->_fields = array_merge(CRM_Case_BAO_Query::getFields(), $this->_fields);
    }

    $this->_fields = array_merge($this->_fields, CRM_Contact_BAO_Query_Hook::singleton()->getFields());

    $this->_selectFields = [];
    foreach ($this->_fields as $name => $field) {
      // lets skip note for now since we dont support it
      if ($name == 'note') {
        continue;
      }
      $this->_selectFields[$name] = $field['title'];
      $this->_hasLocationTypes[$name] = CRM_Utils_Array::value('hasLocationType', $field);
    }

    // lets add group, tag and current_employer to this list
    $this->_selectFields['group'] = ts('Group(s)');
    $this->_selectFields['tag'] = ts('Tag(s)');
    $this->_selectFields['current_employer'] = ts('Current Employer');
    $this->_selectFields['phone_and_ext'] = ts('Phone and Extension');

    //CRM-4363 check for in selector or searchable fields.
    $this->_hasSearchableORInSelector = CRM_Core_BAO_UFField::checkSearchableORInSelector($this->_gid);

    $this->assign('fieldId', $this->_id);
    if ($this->_id) {
      $fieldTitle = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $this->_id, 'label');
      $this->assign('fieldTitle', $fieldTitle);
    }
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete Profile Field'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return;
    }
    $addressCustomFields = array_keys(CRM_Core_BAO_CustomField::getFieldsForImport('Address'));

    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      CRM_Core_BAO_UFField::retrieve($params, $defaults);

      // set it to null if so (avoids crappy E_NOTICE errors below
      $defaults['location_type_id'] = CRM_Utils_Array::value('location_type_id', $defaults);

      //CRM-20861 - Include custom fields defined for address to set its default location type to 0.
      $specialFields = array_merge(CRM_Core_BAO_UFGroup::getLocationFields(), $addressCustomFields);
      if (!$defaults['location_type_id'] &&
        $defaults["field_type"] != "Formatting" &&
        in_array($defaults['field_name'], $specialFields)
      ) {
        $defaults['location_type_id'] = 0;
      }

      $defaults['field_name'] = [
        $defaults['field_type'],
        ($defaults['field_type'] == "Formatting" ? "" : $defaults['field_name']),
        ($defaults['field_name'] == "url") ? $defaults['website_type_id'] : $defaults['location_type_id'],
        CRM_Utils_Array::value('phone_type_id', $defaults),
      ];
      $this->_gid = $defaults['uf_group_id'];
    }
    else {
      $defaults['is_active'] = 1;
    }

    $otherModules = array_values(CRM_Core_BAO_UFGroup::getUFJoinRecord($this->_gid));
    $this->assign('otherModules', $otherModules);

    if ($this->_action & CRM_Core_Action::ADD) {
      $fieldValues = ['uf_group_id' => $this->_gid];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_UFField', $fieldValues);
    }

    // lets trim all the whitespace
    $this->applyFilter('__ALL__', 'trim');

    //hidden field to catch the group id in profile
    $this->add('hidden', 'group_id', $this->_gid);

    //hidden field to catch the field id in profile
    $this->add('hidden', 'field_id', $this->_id);

    $fields = CRM_Core_BAO_UFField::getAvailableFields($this->_gid, $defaults);

    $noSearchable = $hasWebsiteTypes = [];

    foreach ($fields as $key => $value) {
      foreach ($value as $key1 => $value1) {
        //CRM-2676, replacing the conflict for same custom field name from different custom group.
        if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key1)) {
          $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $customFieldId, 'custom_group_id');
          $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'title');
          $this->_mapperFields[$key][$key1] = $value1['title'] . ' :: ' . $customGroupName;
          if (in_array($key1, $addressCustomFields)) {
            $noSearchable[] = $value1['title'] . ' :: ' . $customGroupName;
          }
        }
        else {
          $this->_mapperFields[$key][$key1] = $value1['title'];
        }
        $hasLocationTypes[$key][$key1] = CRM_Utils_Array::value('hasLocationType', $value1);
        $hasWebsiteTypes[$key][$key1] = CRM_Utils_Array::value('hasWebsiteType', $value1);
        // hide the 'is searchable' field for 'File' custom data
        if (isset($value1['data_type']) &&
          isset($value1['html_type']) &&
          (($value1['data_type'] == 'File' && $value1['html_type'] == 'File')
            || ($value1['data_type'] == 'Link' && $value1['html_type'] == 'Link')
          )
        ) {
          if (!in_array($value1['title'], $noSearchable)) {
            $noSearchable[] = $value1['title'];
          }
        }
      }
    }
    $this->assign('noSearchable', $noSearchable);

    $this->_location_types = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
    $this->_website_types = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');

    /**
     * FIXME: dirty hack to make the default option show up first.  This
     * avoids a mozilla browser bug with defaults on dynamically constructed
     * selector widgets.
     */
    if ($defaultLocationType) {
      $defaultLocation = $this->_location_types[$defaultLocationType->id];
      unset($this->_location_types[$defaultLocationType->id]);
      $this->_location_types = [
        $defaultLocationType->id => $defaultLocation,
      ] + $this->_location_types;
    }

    $this->_location_types = ['Primary'] + $this->_location_types;

    // since we need a hierarchical list to display contact types & subtypes,
    // this is what we going to display in first selector
    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, FALSE);
    unset($contactTypes['']);

    $contactTypes = !empty($contactTypes) ? ['Contact' => 'Contacts'] + $contactTypes : [];
    $sel1 = ['' => '- select -'] + $contactTypes;

    if (!empty($fields['Activity'])) {
      $sel1['Activity'] = 'Activity';
    }

    if (CRM_Core_Permission::access('CiviEvent')) {
      $sel1['Participant'] = 'Participants';
    }

    if (!empty($fields['Contribution'])) {
      $sel1['Contribution'] = 'Contributions';
    }

    if (!empty($fields['Membership'])) {
      $sel1['Membership'] = 'Membership';
    }

    if (!empty($fields['Case'])) {
      $sel1['Case'] = 'Case';
    }

    if (!empty($fields['Formatting'])) {
      $sel1['Formatting'] = 'Formatting';
    }

    foreach ($sel1 as $key => $sel) {
      if ($key) {
        $sel2[$key] = $this->_mapperFields[$key];
      }
    }
    $sel3[''] = NULL;
    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    ksort($phoneTypes);

    foreach ($sel1 as $k => $sel) {
      if ($k) {
        foreach ($this->_location_types as $key => $value) {
          $sel4[$k]['phone'][$key] = &$phoneTypes;
          $sel4[$k]['phone_and_ext'][$key] = &$phoneTypes;
        }
      }
    }

    foreach ($sel1 as $k => $sel) {
      if ($k) {
        if (is_array($this->_mapperFields[$k])) {
          foreach ($this->_mapperFields[$k] as $key => $value) {
            if ($hasLocationTypes[$k][$key]) {
              $sel3[$k][$key] = $this->_location_types;
            }
            elseif ($hasWebsiteTypes[$k][$key]) {
              $sel3[$k][$key] = $this->_website_types;
            }
            else {
              $sel3[$key] = NULL;
            }
          }
        }
      }
    }

    $this->_defaults = [];
    $js = "<script type='text/javascript'>\n";
    $formName = "document.{$this->_name}";

    $alreadyMixProfile = FALSE;
    if (CRM_Core_BAO_UFField::checkProfileType($this->_gid)) {
      $alreadyMixProfile = TRUE;
    }
    $this->assign('alreadyMixProfile', $alreadyMixProfile);

    $sel = &$this->addElement('hierselect', 'field_name', ts('Field Name'));

    $formValues = $this->exportValues();

    if (empty($formValues)) {
      for ($k = 1; $k < 4; $k++) {
        if (!isset($defaults['field_name'][$k])) {
          $js .= "{$formName}['field_name[$k]'].style.display = 'none';\n";
        }
      }
    }
    else {
      if (!empty($formValues['field_name'])) {
        for ($key = 1; $key < 4; $key++) {
          if (!isset($formValues['field_name'][$key])) {
            $js .= "{$formName}['field_name[$key]'].style.display = 'none';\n";
          }
          else {
            $js .= "{$formName}['field_name[$key]'].style.display = '';\n";
          }
        }
      }
      else {
        for ($k = 1; $k < 4; $k++) {
          if (!isset($defaults['field_name'][$k])) {
            $js .= "{$formName}['field_name[$k]'].style.display = 'none';\n";
          }
        }
      }
    }

    foreach ($sel2 as $k => $v) {
      if (is_array($sel2[$k])) {
        asort($sel2[$k]);
      }
    }

    $sel->setOptions([$sel1, $sel2, $sel3, $sel4]);

    // proper interpretation of spec in CRM-8732
    if (!isset($this->_id) && in_array('Search Profile', $otherModules)) {
      $defaults['visibility'] = 'Public Pages and Listings';
    }

    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    $this->add('select',
      'visibility',
      ts('Visibility'),
      CRM_Core_SelectValues::ufVisibility(),
      TRUE,
      ['onChange' => "showHideSeletorSearch(this.value);"]
    );

    //CRM-4363
    $js = ['onChange' => "mixProfile();"];
    // should the field appear in selectors (as a column)?
    $this->add('advcheckbox', 'in_selector', ts('Results Column?'), NULL, NULL, $js);
    $this->add('advcheckbox', 'is_searchable', ts('Searchable?'), NULL, NULL, $js);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFField');

    // weight
    $this->add('number', 'weight', ts('Order'), $attributes['weight'], TRUE);
    $this->addRule('weight', ts('is a numeric field'), 'numeric');

    $this->add('textarea', 'help_pre', ts('Field Pre Help'), $attributes['help_pre']);
    $this->add('textarea', 'help_post', ts('Field Post Help'), $attributes['help_post']);

    $this->add('advcheckbox', 'is_required', ts('Required?'));

    $this->add('advcheckbox', 'is_multi_summary', ts('Include in multi-record listing?'));
    $this->add('advcheckbox', 'is_active', ts('Active?'));
    $this->add('advcheckbox', 'is_view', ts('View Only?'));

    $this->add('text', 'label', ts('Field Label'), $attributes['label']);

    $js = NULL;
    if ($this->_hasSearchableORInSelector) {
      $js = ['onclick' => "return verify( );"];
    }

    // add buttons
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
        'js' => $js,
      ],
      [
        'type' => 'next',
        'name' => ts('Save and New'),
        'subName' => 'new',
        'js' => $js,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->addFormRule(['CRM_UF_Form_Field', 'formRule'], $this);

    // if view mode pls freeze it with the done button.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $this->addElement('button', 'done', ts('Done'),
        ['onclick' => "location.href='civicrm/admin/uf/group/field?reset=1&action=browse&gid=" . $this->_gid . "'"]
      );
    }

    $this->setDefaults($defaults);
  }

  /**
   * Process the form.
   *
   * @return void
   */
  public function postProcess() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      $fieldValues = ['uf_group_id' => $this->_gid];
      CRM_Utils_Weight::delWeight('CRM_Core_DAO_UFField', $this->_id, $fieldValues);
      $deleted = CRM_Core_BAO_UFField::del($this->_id);

      //update group_type every time. CRM-3608
      if ($this->_gid && $deleted) {
        //get the profile type.
        $fieldsType = CRM_Core_BAO_UFGroup::calculateGroupType($this->_gid, TRUE);
        CRM_Core_BAO_UFGroup::updateGroupTypes($this->_gid, $fieldsType);
      }

      CRM_Core_Session::setStatus(ts('Selected Profile Field has been deleted.'), ts('Profile Field Deleted'), 'success');
      return;
    }

    // store the submitted values in an array
    $params = $this->controller->exportValues('Field');
    $params['uf_group_id'] = $this->_gid;
    if ($params['visibility'] == 'User and User Admin Only') {
      $params['is_searchable'] = $params['in_selector'] = 0;
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $name = NULL;
    if (isset($params['field_name'][1]) && isset($this->_selectFields[$params['field_name'][1]])) {
      // we dont get a name for a html formatting element
      $name = $this->_selectFields[$params['field_name'][1]];
    }

    //Hack for Formatting Field Name
    if ($params['field_name'][0] == 'Formatting') {
      $fieldName = 'formatting_' . rand(1000, 9999);
    }
    else {
      $fieldName = $params['field_name'][1];
    }

    //check for duplicate fields
    $apiFormattedParams = $params;
    $apiFormattedParams['field_type'] = $params['field_name'][0];
    $apiFormattedParams['field_name'] = $fieldName;
    if (!empty($params['field_name'][2])) {
      if ($fieldName === 'url') {
        $apiFormattedParams['website_type_id'] = $params['field_name'][2];
      }
      else {
        $apiFormattedParams['location_type_id'] = $params['field_name'][2];
      }
    }
    elseif ($params['field_name'][2] == 0) {
      // 0 is Primary location type
      $apiFormattedParams['location_type_id'] = NULL;
    }
    if (!empty($params['field_name'][3])) {
      $apiFormattedParams['phone_type_id'] = $params['field_name'][3];
    }

    if ($apiFormattedParams['field_type'] != "Formatting" && CRM_Core_BAO_UFField::duplicateField($apiFormattedParams)) {
      CRM_Core_Error::statusBounce(ts('The selected field already exists in this profile.'), NULL, ts('Field Not Added'));
    }
    else {
      $apiFormattedParams['weight'] = CRM_Core_BAO_UFField::autoWeight($params);
      civicrm_api3('UFField', 'create', $apiFormattedParams);

      //reset other field is searchable and in selector settings, CRM-4363
      if ($this->_hasSearchableORInSelector &&
        in_array($apiFormattedParams['field_type'], ['Participant', 'Contribution', 'Membership', 'Activity', 'Case'])
      ) {
        CRM_Core_BAO_UFField::resetInSelectorANDSearchable($this->_gid);
      }

      $this->setMessageIfCountryNotAboveState($fieldName, CRM_Utils_Array::value('location_type_id', $apiFormattedParams), $apiFormattedParams['weight'], $apiFormattedParams['uf_group_id']);

      CRM_Core_Session::setStatus(ts('Your CiviCRM Profile Field \'%1\' has been saved to \'%2\'.',
        [1 => $name, 2 => $this->_title]
      ), ts('Profile Field Saved'), 'success');
    }
    $buttonName = $this->controller->getButtonName();

    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/uf/group/field/add',
        "reset=1&action=add&gid={$this->_gid}"
      ));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/uf/group/field',
        "reset=1&action=browse&gid={$this->_gid}"
      ));
    }
  }

  /**
   * Validation rule for subtype.
   *
   * @param string $fieldType
   *   Type of field.
   * @param array $groupType
   *   Contains all groupTypes.
   * @param array $errors
   *   List of errors to be posted back to the form.
   */
  public static function formRuleSubType($fieldType, $groupType, &$errors) {
    if (in_array($fieldType, [
      'Participant',
      'Contribution',
      'Membership',
      'Activity',
    ])) {
      $individualSubTypes = CRM_Contact_BAO_ContactType::subTypes('Individual');
      foreach ($groupType as $value) {
        if (!in_array($value, $individualSubTypes) &&
          !in_array($value, [
            'Participant',
            'Contribution',
            'Membership',
            'Individual',
            'Contact',
            'Activity',
            'Formatting',
          ])
        ) {
          $errors['field_name'] = ts('Cannot add or update profile field "%1" with combination of Household or Organization or any subtypes of Household or Organization.', [1 => $fieldType]);
          break;
        }
      }
    }
    else {
      $basicType = CRM_Contact_BAO_ContactType::getBasicType($groupType);
      if ($basicType) {
        if (!is_array($basicType)) {
          $basicType = [$basicType];
        }
        if (!in_array($fieldType, $basicType) && $fieldType != 'Contact') {
          $errors['field_name'] = ts('Cannot add or update profile field type "%1" with combination of subtype other than "%1".',
            [1 => $fieldType]
          );
        }
      }
    }
  }

  /**
   * Validation rule for custom data extends entity column values.
   *
   * @param Object $customField
   *   Custom field.
   * @param int $gid
   *   Group Id.
   * @param string $fieldType
   *   Group type of the field.
   * @param array $errors
   *   Collect errors.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRuleCustomDataExtentColumnValue($customField, $gid, $fieldType, &$errors) {
    // fix me : check object $customField
    if (in_array($fieldType, [
      'Participant',
      'Contribution',
      'Membership',
      'Activity',
      'Case',
    ])) {
      $params = ['id' => $customField->custom_group_id];
      $customGroup = [];
      CRM_Core_BAO_CustomGroup::retrieve($params, $customGroup);
      if (($fieldType != CRM_Utils_Array::value('extends', $customGroup)) || empty($customGroup['extends_entity_column_value'])) {
        return $errors;
      }

      $extendsColumnValues = [];
      foreach (explode(CRM_Core_DAO::VALUE_SEPARATOR, $customGroup['extends_entity_column_value']) as $val) {
        if ($val) {
          $extendsColumnValues[] = $val;
        }
      }

      if (empty($extendsColumnValues)) {
        return $errors;
      }

      $fieldTypeValues = CRM_Core_BAO_UFGroup::groupTypeValues($gid, $fieldType);
      if (empty($fieldTypeValues[$fieldType])) {
        return $errors;
      }

      $disallowedTypes = array_diff($extendsColumnValues, $fieldTypeValues[$fieldType]);
      if (!empty($disallowedTypes)) {
        $errors['field_name'] = ts('Profile is already having custom fields extending different group types, you can not add or update this custom field.');
      }
    }
  }

  /**
   * Validation rule to prevent multiple fields of primary location type within the same communication type.
   *
   * @param array $fields
   *   Submitted fields.
   * @param string $profileFieldName
   *   Group Id.
   * @param array $groupFields
   *   List of fields already in the group.
   * @param array $errors
   *   Collect errors.
   *
   */
  public static function formRulePrimaryCheck($fields, $profileFieldName, $groupFields, &$errors) {
    //FIXME: This may need to also apply to website fields if they are refactored to allow more than one per profile
    $checkPrimary = ['phone' => 'civicrm_phone.phone', 'phone_and_ext' => 'civicrm_phone.phone'];
    $whereCheck = NULL;
    $primaryOfSameTypeFound = NULL;
    $fieldID = empty($fields['field_id']) ? 0 : $fields['field_id'];
    // Is this a primary location type field of interest
    if (array_key_exists($profileFieldName, $checkPrimary)) {
      $whereCheck = $checkPrimary[$profileFieldName];
    }
    $potentialLocationType = CRM_Utils_Array::value(2, $fields['field_name']);

    if ($whereCheck && $potentialLocationType == 0) {
      $primaryOfSameTypeFound = '';

      foreach ($groupFields as $groupField) {
        // if it is a phone
        if ($groupField['where'] == $whereCheck && is_null($groupField['location_type_id']) && $groupField['field_id'] != $fieldID) {
          $primaryOfSameTypeFound = $groupField['title'];
          break;
        }
      }
      if ($primaryOfSameTypeFound) {
        $errors['field_name'] = ts('You have already added a primary location field of this type: %1', [1 => $primaryOfSameTypeFound]);
      }
    }
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $files
   * @param $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $is_required = CRM_Utils_Array::value('is_required', $fields, FALSE);
    $is_registration = CRM_Utils_Array::value('is_registration', $fields, FALSE);
    $is_view = CRM_Utils_Array::value('is_view', $fields, FALSE);
    $in_selector = CRM_Utils_Array::value('in_selector', $fields, FALSE);
    $is_active = CRM_Utils_Array::value('is_active', $fields, FALSE);

    $errors = [];
    if ($is_view && $is_registration) {
      $errors['is_registration'] = ts('View Only cannot be selected if this field is to be included on the registration form');
    }
    if ($is_view && $is_required) {
      $errors['is_view'] = ts('A View Only field cannot be required');
    }

    $entityName = $fields['field_name'][0];
    if (!$entityName) {
      $errors['field_name'] = ts('Please select a field name');
    }

    if ($in_selector && in_array($entityName, [
      'Contribution',
      'Participant',
      'Membership',
      'Activity',
    ])
    ) {
      $errors['in_selector'] = ts("'Results Column' cannot be checked for %1 fields.", [1 => $entityName]);
    }

    $isCustomField = FALSE;
    $profileFieldName = CRM_Utils_Array::value(1, $fields['field_name']);
    if ($profileFieldName) {
      //get custom field id
      $customFieldId = explode('_', $profileFieldName);
      if ($customFieldId[0] == 'custom') {
        $customField = new CRM_Core_DAO_CustomField();
        $customField->id = $customFieldId[1];
        $customField->find(TRUE);
        $isCustomField = TRUE;
        if (!empty($fields['field_id']) && !$customField->is_active && $is_active) {
          $errors['field_name'] = ts('Cannot set this field "Active" since the selected custom field is disabled.');
        }

        //check if profile already has a different multi-record custom set field configured
        $customGroupId = CRM_Core_BAO_CustomField::isMultiRecordField($profileFieldName);
        if ($customGroupId) {
          if ($profileMultiRecordCustomGid = CRM_Core_BAO_UFField::checkMultiRecordFieldExists($self->_gid)) {
            if ($customGroupId != $profileMultiRecordCustomGid) {
              $errors['field_name'] = ts("You cannot configure multi-record custom fields belonging to different custom sets in one profile");
            }
          }
        }
      }
    }

    // Get list of fields already in the group
    $groupFields = CRM_Core_BAO_UFGroup::getFields($fields['group_id'], FALSE, NULL, NULL, NULL, TRUE, NULL, TRUE);
    // Check if we already added a primary field of the same communication type
    self::formRulePrimaryCheck($fields, $profileFieldName, $groupFields, $errors);

    //check profile is configured for double option process
    //adding group field, email field should be present in the group
    //fixed for  issue CRM-2861 & CRM-4153
    if (CRM_Core_BAO_UFGroup::isProfileDoubleOptin()) {
      if (CRM_Utils_Array::value(1, $fields['field_name']) == 'group') {
        $dao = new CRM_Core_BAO_UFField();
        $dao->uf_group_id = $fields['group_id'];
        $dao->find();
        $emailField = FALSE;
        while ($dao->fetch()) {
          //check email field is present in the group
          if ($dao->field_name == 'email') {
            $emailField = TRUE;
            break;
          }
        }

        if (!$emailField) {
          $disableSettingURL = CRM_Utils_System::url(
            'civicrm/admin/setting/preferences/mailing',
            'reset=1'
          );

          $errors['field_name'] = ts('Your site is currently configured to require double-opt in when users join (subscribe) to Group(s) via a Profile form. In this mode, you need to include an Email field in a Profile BEFORE you can add the Group(s) field. This ensures that an opt-in confirmation email can be sent. Your site administrator can disable double opt-in on the civimail admin settings: <em>%1</em>', [1 => $disableSettingURL]);
        }
      }
    }

    //fix for CRM-3037
    $fieldType = $fields['field_name'][0];

    //get the group type.
    $groupType = CRM_Core_BAO_UFGroup::calculateGroupType($self->_gid, FALSE, CRM_Utils_Array::value('field_id', $fields));

    switch ($fieldType) {
      case 'Contact':
        self::formRuleSubType($fieldType, $groupType, $errors);
        break;

      case 'Individual':
        if (in_array('Activity', $groupType) ||
          in_array('Household', $groupType) ||
          in_array('Organization', $groupType)
        ) {

          //CRM-7603 - need to support activity + individual.
          //$errors['field_name'] =
          //ts( 'Cannot add or update profile field type Individual with combination of Household or Organization or Activity' );
          if (in_array('Household', $groupType) ||
            in_array('Organization', $groupType)
          ) {
            $errors['field_name'] = ts('Cannot add or update profile field type Individual with combination of Household or Organization');
          }
        }
        else {
          self::formRuleSubType($fieldType, $groupType, $errors);
        }
        break;

      case 'Household':
        if (in_array('Activity', $groupType) || in_array('Individual', $groupType) || in_array('Organization', $groupType)) {
          $errors['field_name'] = ts('Cannot add or update profile field type Household with combination of Individual or Organization or Activity');
        }
        else {
          self::formRuleSubType($fieldType, $groupType, $errors);
        }
        break;

      case 'Organization':
        if (in_array('Activity', $groupType) || in_array('Household', $groupType) || in_array('Individual', $groupType)) {
          $errors['field_name'] = ts('Cannot add or update profile field type Organization with combination of Household or Individual or Activity');
        }
        else {
          self::formRuleSubType($fieldType, $groupType, $errors);
        }
        break;

      case 'Activity':
        if (in_array('Individual', $groupType) ||
          in_array('Membership', $groupType) ||
          in_array('Contribution', $groupType) ||
          in_array('Organization', $groupType) ||
          in_array('Household', $groupType) ||
          in_array('Participant', $groupType)
        ) {

          //CRM-7603 - need to support activity + contact type.
          //$errors['field_name'] =
          //ts( 'Cannot add or update profile field type Activity with combination Participant or Membership or Contribution or Household or Organization or Individual' );
          if (in_array('Membership', $groupType) ||
            in_array('Contribution', $groupType) ||
            in_array('Participant', $groupType)
          ) {
            $errors['field_name'] = ts('Cannot add or update profile field type Activity with combination Participant or Membership or Contribution');
          }
        }
        else {
          self::formRuleSubType($fieldType, $groupType, $errors);
        }

        if ($isCustomField && !isset($errors['field_name'])) {
          self::formRuleCustomDataExtentColumnValue($customField, $self->_gid, $fieldType, $errors);
        }
        break;

      case 'Participant':
        if (in_array('Membership', $groupType) || in_array('Contribution', $groupType)
          || in_array('Organization', $groupType) || in_array('Household', $groupType) || in_array('Activity', $groupType)
        ) {
          $errors['field_name'] = ts('Cannot add or update profile field type Participant with combination of Activity or Membership or Contribution or Household or Organization.');
        }
        else {
          self::formRuleSubType($fieldType, $groupType, $errors);
        }
        break;

      case 'Contribution':
        //special case where in we allow contribution + oganization fields, for on behalf feature
        $profileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup',
          'on_behalf_organization', 'id', 'name'
        );

        if (in_array('Participant', $groupType) || in_array('Membership', $groupType)
          || ($profileId != $self->_gid && in_array('Organization', $groupType)) || in_array('Household', $groupType) || in_array('Activity', $groupType)
        ) {
          $errors['field_name'] = ts('Cannot add or update profile field type Contribution with combination of Activity or Membership or Participant or Household or Organization');
        }
        else {
          self::formRuleSubType($fieldType, $groupType, $errors);
        }
        break;

      case 'Membership':
        //special case where in we allow contribution + oganization fields, for on behalf feature
        $profileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup',
          'on_behalf_organization', 'id', 'name'
        );

        if (in_array('Participant', $groupType) || in_array('Contribution', $groupType)
          || ($profileId != $self->_gid && in_array('Organization', $groupType)) || in_array('Household', $groupType) || in_array('Activity', $groupType)
        ) {
          $errors['field_name'] = ts('Cannot add or update profile field type Membership with combination of Activity or Participant or Contribution or Household or Organization');
        }
        else {
          self::formRuleSubType($fieldType, $groupType, $errors);
        }
        break;

      default:
        $profileType = CRM_Core_BAO_UFField::getProfileType($fields['group_id'], TRUE, FALSE, TRUE);
        if (CRM_Contact_BAO_ContactType::isaSubType($fieldType)) {
          if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
            if ($fieldType != $profileType) {
              $errors['field_name'] = ts('Cannot add or update profile field type "%1" with combination of "%2".', [
                1 => $fieldType,
                2 => $profileType,
              ]);
            }
          }
          else {
            $basicType = CRM_Contact_BAO_ContactType::getBasicType($fieldType);
            if ($profileType &&
              $profileType != $basicType &&
              $profileType != 'Contact'
            ) {
              $errors['field_name'] = ts('Cannot add or update profile field type "%1" with combination of "%2".', [
                1 => $fieldType,
                2 => $profileType,
              ]);
            }
          }
        }
        elseif (
          CRM_Utils_Array::value(1, $fields['field_name']) == 'contact_sub_type' &&
          !in_array($profileType, ['Individual', 'Household', 'Organization']) &&
          !in_array($profileType, CRM_Contact_BAO_ContactType::subTypes())
        ) {
          $errors['field_name'] = ts('Cannot add or update profile field Contact Subtype as profile type is not one of Individual, Household or Organization.');
        }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set a message warning the user about putting country first to render states, if required.
   *
   * @param string $fieldName
   * @param int $locationTypeID
   * @param int $weight
   * @param int $ufGroupID
   */
  protected function setMessageIfCountryNotAboveState($fieldName, $locationTypeID, $weight, $ufGroupID) {
    $message = ts('For best results, the Country field should precede the State-Province field in your Profile form. You can use the up and down arrows on field listing page for this profile to change the order of these fields or manually edit weight for Country/State-Province Field.');

    if (in_array($fieldName, [
      'country',
      'state_province',
    ]) && count(CRM_Core_Config::singleton()->countryLimit) > 1
    ) {
      // get state or country field weight if exists
      $ufFieldDAO = new CRM_Core_DAO_UFField();
      $ufFieldDAO->field_name = ($fieldName == 'state_province' ? 'country' : 'state_province');
      $ufFieldDAO->location_type_id = $locationTypeID;
      $ufFieldDAO->uf_group_id = $ufGroupID;

      if ($ufFieldDAO->find(TRUE)) {
        if ($ufFieldDAO->field_name == 'country' && $ufFieldDAO->weight > $weight) {
          CRM_Core_Session::setStatus($message);
        }
        elseif ($ufFieldDAO->field_name == 'state_province' && $ufFieldDAO->weight < $weight) {
          CRM_Core_Session::setStatus($message);
        }
      }
    }
  }

}
