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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class is for search builder processing.
 */
class CRM_Contact_Form_Search_Builder extends CRM_Contact_Form_Search {

  /**
   * Number of columns in where.
   *
   * @var int
   */
  public $_columnCount;

  /**
   * Number of blocks to be shown.
   *
   * @var int
   */
  public $_blockCount;

  /**
   * Build the form object.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Builder');

    $this->set('context', 'builder');
    parent::preProcess();

    // Get the block count
    $this->_blockCount = $this->get('blockCount');
    // Initialize new form
    if (!$this->_blockCount) {
      $this->_blockCount = 4;
      $this->set('newBlock', 1);
    }

    //get the column count
    $this->_columnCount = $this->get('columnCount');

    for ($i = 1; $i < $this->_blockCount; $i++) {
      if (empty($this->_columnCount[$i])) {
        $this->_columnCount[$i] = 5;
      }
    }

    $this->_loadedMappingId = $this->get('savedMapping');

    if ($this->get('showSearchForm')) {
      $this->assign('showSearchForm', TRUE);
    }
    else {
      $this->assign('showSearchForm', FALSE);
    }
  }

  /**
   * Build quick form.
   */
  public function buildQuickForm() {
    $fields = self::fields();
    // Get fields of type date
    // FIXME: This is a hack until our fields contain this meta-data
    $dateFields = array();
    foreach ($fields as $name => $field) {
      if (strpos($name, '_date') || CRM_Utils_Array::value('data_type', $field) == 'Date') {
        $dateFields[] = $name;
      }
    }
    // Add javascript
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Contact/Form/Search/Builder.js', 1, 'html-header')
      ->addSetting(array(
        'searchBuilder' => array(
          // Index of newly added/expanded block (1-based index)
          'newBlock' => $this->get('newBlock'),
          'dateFields' => $dateFields,
          'fieldOptions' => self::fieldOptions(),
        ),
      ));
    //get the saved search mapping id
    $mappingId = NULL;
    if ($this->_ssID) {
      $mappingId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $this->_ssID, 'mapping_id');
    }

    CRM_Core_BAO_Mapping::buildMappingForm($this, 'Search Builder', $mappingId, $this->_columnCount, $this->_blockCount);

    parent::buildQuickForm();
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Contact_Form_Search_Builder', 'formRule'), $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   * @param array $files
   * @param CRM_Core_Form $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    if (!empty($values['addMore']) || !empty($values['addBlock'])) {
      return TRUE;
    }
    $fields = self::fields();
    $fld = CRM_Core_BAO_Mapping::formattedFields($values, TRUE);

    $errorMsg = array();
    foreach ($fld as $k => $v) {
      if (!$v[1]) {
        $errorMsg["operator[$v[3]][$v[4]]"] = ts("Please enter the operator.");
      }
      else {
        // CRM-10338
        $v[2] = self::checkArrayKeyEmpty($v[2]);

        if (in_array($v[1], array(
            'IS NULL',
            'IS NOT NULL',
            'IS EMPTY',
            'IS NOT EMPTY',
          )) &&
          !empty($v[2])
        ) {
          $errorMsg["value[$v[3]][$v[4]]"] = ts('Please clear your value if you want to use %1 operator.', array(1 => $v[1]));
        }
        elseif (substr($v[0], 0, 7) === 'do_not_' or substr($v[0], 0, 3) === 'is_') {
          if (isset($v[2])) {
            $v2 = array($v[2]);
            if (!isset($v[2])) {
              $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
            }

            $error = CRM_Utils_Type::validate($v2[0], 'Integer', FALSE);
            if ($error != $v2[0]) {
              $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a valid value.");
            }
          }
          else {
            $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
          }
        }
        else {
          if (substr($v[0], 0, 7) == 'custom_') {
            // Get rid of appended location type id
            list($fieldKey) = explode('-', $v[0]);
            $type = $fields[$fieldKey]['data_type'];

            // hack to handle custom data of type state and country
            if (in_array($type, array(
              'Country',
              'StateProvince',
            ))) {
              $type = "Integer";
            }
          }
          else {
            $fldName = $v[0];
            // FIXME: no idea at this point what to do with this,
            // FIXME: but definitely needs fixing.
            if (substr($v[0], 0, 13) == 'contribution_') {
              $fldName = substr($v[0], 13);
            }

            $fldValue = CRM_Utils_Array::value($fldName, $fields);
            $fldType = CRM_Utils_Array::value('type', $fldValue);
            $type = CRM_Utils_Type::typeToString($fldType);

            if (strstr($v[1], 'IN')) {
              if (empty($v[2])) {
                $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
              }
            }
            // Check Empty values for Integer Or Boolean Or Date type For operators other than IS NULL and IS NOT NULL.
            elseif (!in_array($v[1],
              array('IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'))
            ) {
              if ((($type == 'Int' || $type == 'Boolean') && !is_array($v[2]) && !trim($v[2])) && $v[2] != '0') {
                $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
              }
              elseif ($type == 'Date' && !trim($v[2])) {
                $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
              }
            }
          }

          if ($type && empty($errorMsg)) {
            // check for valid format while using IN Operator
            if (strstr($v[1], 'IN')) {
              if (!is_array($v[2])) {
                $inVal = trim($v[2]);
                //checking for format to avoid db errors
                if ($type == 'Int') {
                  if (!preg_match('/^[A-Za-z0-9\,]+$/', $inVal)) {
                    $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter correct Data (in valid format).");
                  }
                }
                else {
                  if (!preg_match('/^[A-Za-z0-9åäöÅÄÖüÜœŒæÆøØ()\,\s]+$/', $inVal)) {
                    $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter correct Data (in valid format).");
                  }
                }
              }

              // Validate each value in parenthesis to avoid db errors
              if (empty($errorMsg)) {
                $parenValues = array();
                $parenValues = is_array($v[2]) ? (array_key_exists($v[1], $v[2])) ? $v[2][$v[1]] : $v[2] : explode(',', trim($inVal, "(..)"));
                foreach ($parenValues as $val) {
                  if ($type == 'Date' || $type == 'Timestamp') {
                    $val = CRM_Utils_Date::processDate($val);
                    if ($type == 'Date') {
                      $val = substr($val, 0, 8);
                    }
                  }
                  else {
                    $val = trim($val);
                  }
                  if (!$val && $val != '0') {
                    $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter the values correctly.");
                  }
                  if (empty($errorMsg)) {
                    $error = CRM_Utils_Type::validate($val, $type, FALSE);
                    if ($error != $val) {
                      $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a valid value.");
                    }
                  }
                }
              }
            }
            elseif (trim($v[2])) {
              //else check value for rest of the Operators
              $error = CRM_Utils_Type::validate($v[2], $type, FALSE);
              if ($error != $v[2]) {
                $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a valid value.");
              }
            }
          }
        }
      }
    }

    if (!empty($errorMsg)) {
      $self->set('showSearchForm', TRUE);
      $self->assign('rows', NULL);
      return $errorMsg;
    }

    return TRUE;
  }

  /**
   * Normalise form values.
   */
  public function normalizeFormValues() {
  }

  /**
   * Convert form values.
   *
   * @param array $formValues
   *
   * @return array
   */
  public function convertFormValues(&$formValues) {
    return CRM_Core_BAO_Mapping::formattedFields($formValues);
  }

  /**
   * Get return properties.
   *
   * @return array
   */
  public function &returnProperties() {
    return CRM_Core_BAO_Mapping::returnProperties($this->_formValues);
  }

  /**
   * Process the uploaded file.
   */
  public function postProcess() {
    $this->set('isAdvanced', '2');
    $this->set('isSearchBuilder', '1');
    $this->set('showSearchForm', FALSE);

    $params = $this->controller->exportValues($this->_name);
    if (!empty($params)) {
      // Add another block
      if (!empty($params['addBlock'])) {
        $this->set('newBlock', $this->_blockCount);
        $this->_blockCount += 3;
        $this->set('blockCount', $this->_blockCount);
        $this->set('showSearchForm', TRUE);
        return;
      }
      // Add another field
      $addMore = CRM_Utils_Array::value('addMore', $params);
      for ($x = 1; $x <= $this->_blockCount; $x++) {
        if (!empty($addMore[$x])) {
          $this->set('newBlock', $x);
          $this->_columnCount[$x] = $this->_columnCount[$x] + 5;
          $this->set('columnCount', $this->_columnCount);
          $this->set('showSearchForm', TRUE);
          return;
        }
      }
      $this->set('newBlock', NULL);
      $checkEmpty = NULL;
      foreach ($params['mapper'] as $key => $value) {
        foreach ($value as $k => $v) {
          if ($v[0]) {
            $checkEmpty++;
          }
        }
      }

      if (!$checkEmpty) {
        $this->set('newBlock', 1);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/search/builder', '_qf_Builder_display=true'));
      }
    }

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);

      // set the group if group is submitted
      if (!empty($this->_formValues['uf_group_id'])) {
        $this->set('id', $this->_formValues['uf_group_id']);
      }
      else {
        $this->set('id', '');
      }
    }

    // we dont want to store the sortByCharacter in the formValue, it is more like
    // a filter on the result set
    // this filter is reset if we click on the search button
    if ($this->_sortByCharacter !== NULL && empty($_POST)) {
      if (strtolower($this->_sortByCharacter) == 'all') {
        $this->_formValues['sortByCharacter'] = NULL;
      }
      else {
        $this->_formValues['sortByCharacter'] = $this->_sortByCharacter;
      }
    }
    else {
      $this->_sortByCharacter = NULL;
    }

    $this->_params = $this->convertFormValues($this->_formValues);
    $this->_returnProperties = &$this->returnProperties();

    // CRM-10338 check if value is empty array
    foreach ($this->_params as $k => $v) {
      $this->_params[$k][2] = self::checkArrayKeyEmpty($v[2]);
    }

    parent::postProcess();
  }

  /**
   * Get fields.
   *
   * @return array
   */
  public static function fields() {
    $fields = array_merge(
      CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE),
      CRM_Core_Component::getQueryFields(),
      CRM_Contact_BAO_Query_Hook::singleton()->getFields(),
      CRM_Activity_BAO_Activity::exportableFields()
    );
    return $fields;
  }

  /**
   * CRM-9434 Hackish function to fetch fields with options.
   *
   * FIXME: When our core fields contain reliable metadata this will be much simpler.
   * @return array
   *   (string => string) key: field_name value: api entity name
   *   Note: options are fetched via ajax using the api "getoptions" method
   */
  public static function fieldOptions() {
    // Hack to add options not retrieved by getfields
    // This list could go on and on, but it would be better to fix getfields
    $options = array(
      'group' => 'group_contact',
      'tag' => 'entity_tag',
      'on_hold' => 'yesno',
      'is_bulkmail' => 'yesno',
      'payment_instrument' => 'contribution',
      'membership_status' => 'membership',
      'membership_type' => 'membership',
      'member_campaign_id' => 'membership',
      'member_is_test' => 'yesno',
      'member_is_pay_later' => 'yesno',
      'is_override' => 'yesno',
    );
    $entities = array(
      'contact',
      'address',
      'activity',
      'participant',
      'pledge',
      'member',
      'contribution',
      'case',
      'grant',
    );
    CRM_Contact_BAO_Query_Hook::singleton()->alterSearchBuilderOptions($entities, $options);
    foreach ($entities as $entity) {
      $fields = civicrm_api3($entity, 'getfields');
      foreach ($fields['values'] as $field => $info) {
        if (!empty($info['options']) || !empty($info['pseudoconstant']) || !empty($info['option_group_id'])) {
          $options[$field] = $entity;
          // Hack for when search field doesn't match db field - e.g. "country" instead of "country_id"
          if (substr($field, -3) == '_id') {
            $options[substr($field, 0, -3)] = $entity;
          }
        }
        elseif (!empty($info['data_type']) && in_array($info['data_type'], array('StateProvince', 'Country'))) {
          $options[$field] = $entity;
        }
        elseif (in_array(substr($field, 0, 3), array(
              'is_',
              'do_',
            )) || CRM_Utils_Array::value('data_type', $info) == 'Boolean'
        ) {
          $options[$field] = 'yesno';
          if ($entity != 'contact') {
            $options[$entity . '_' . $field] = 'yesno';
          }
        }
        elseif (strpos($field, '_is_')) {
          $options[$field] = 'yesno';
        }
      }
    }
    return $options;
  }

  /**
   * CRM-10338 tags and groups use array keys for selection list.
   *
   * if using IS NULL/NOT NULL, an array with no array key is created
   * convert that to simple NULL so processing can proceed
   *
   * @param string $val
   *
   * @return null
   */
  public static function checkArrayKeyEmpty($val) {
    if (is_array($val)) {
      $v2empty = TRUE;
      foreach ($val as $vk => $vv) {
        if (!empty($vk)) {
          $v2empty = FALSE;
        }
      }
      if ($v2empty) {
        $val = NULL;
      }
    }
    return $val;
  }

}
