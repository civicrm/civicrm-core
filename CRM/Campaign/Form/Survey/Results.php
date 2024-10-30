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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for processing a survey.
 */
class CRM_Campaign_Form_Survey_Results extends CRM_Campaign_Form_Survey {

  protected $_reportId;

  protected $_reportTitle;

  /**
   * values
   *
   * @var array
   *
   */

  public $_values;

  const NUM_OPTION = 11;

  public function preProcess() {
    parent::preProcess();

    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = [];
      if ($this->_surveyId) {
        $params = ['id' => $this->_surveyId];
        CRM_Campaign_BAO_Survey::retrieve($params, $this->_values);
      }
      $this->set('values', $this->_values);
    }

    $query = "SELECT MAX(id) as id, title FROM civicrm_report_instance WHERE name = %1 GROUP BY id";
    $params = [1 => ["survey_{$this->_surveyId}", 'String']];
    $result = CRM_Core_DAO::executeQuery($query, $params);
    if ($result->fetch()) {
      $this->_reportId = $result->id;
      $this->_reportTitle = $result->title;
    }
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database.
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    // set defaults for weight.
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      $defaults["option_weight[{$i}]"] = $i;
    }

    $defaults['create_report'] = 1;
    if ($this->_reportId) {
      $defaults['report_title'] = $this->_reportTitle;
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $optionGroups = CRM_Campaign_BAO_Survey::getResultSets();

    if (empty($optionGroups)) {
      $optionTypes = ['1' => ts('Create new result set')];
    }
    else {
      $optionTypes = [
        '1' => ts('Create new result set'),
        '2' => ts('Use existing result set'),
      ];
      $this->add('select',
        'option_group_id',
        ts('Select Result Set'),
        [
          '' => ts('- select -'),
        ] + $optionGroups, FALSE,
        ['onChange' => 'loadOptionGroup( )']
      );
    }

    $element = &$this->addRadio('option_type',
      ts('Survey Responses'),
      $optionTypes,
      [
        'onclick' => "showOptionSelect();",
      ], '<br/>', TRUE
    );

    if (empty($optionGroups) || empty($this->_values['result_id'])) {
      $this->setdefaults(['option_type' => 1]);
    }
    elseif (!empty($this->_values['result_id'])) {
      $this->setdefaults([
        'option_type' => 2,
        'option_group_id' => $this->_values['result_id'],
      ]);
    }

    // form fields of Custom Option rows
    $defaultOptionValues = [];
    $_showHide = new CRM_Core_ShowHideBlocks();

    $optionAttributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue');
    $optionAttributes['label']['size'] = $optionAttributes['value']['size'] = 25;
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      //the show hide blocks
      $showBlocks = 'optionField_' . $i;
      if ($i > 2) {
        $_showHide->addHide($showBlocks);
        if ($i == self::NUM_OPTION) {
          $_showHide->addHide('additionalOption');
        }
      }
      else {
        $_showHide->addShow($showBlocks);
      }

      $this->add('text', 'option_label[' . $i . ']', ts('Label'),
        $optionAttributes['label']
      );

      // value
      $this->add('text', 'option_value[' . $i . ']', ts('Value'),
        $optionAttributes['value']
      );

      // weight
      $this->add('number', "option_weight[$i]", ts('Order'),
        $optionAttributes['weight']
      );

      $this->add('text', 'option_interval[' . $i .
        ']', ts('Recontact Interval'),
        CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'release_frequency')
      );

      $defaultOptionValues[$i] = NULL;
    }

    //default option selection
    $this->addRadio('default_option', '', $defaultOptionValues);

    $_showHide->addToTemplate();

    $this->addElement('checkbox', 'create_report', ts('Create Report'));
    $this->addElement('text', 'report_title', ts('Report Title'));

    if ($this->_reportId) {
      $this->freeze('create_report');
      $this->freeze('report_title');
    }

    $this->addFormRule([
      'CRM_Campaign_Form_Survey_Results',
      'formRule',
    ], $this);

    parent::buildQuickForm();
  }

  /**
   * Global validation rules for the form.
   *
   * @param $fields
   * @param $files
   * @param $form
   *
   * @return array|bool
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];
    if (!empty($fields['option_label']) && !empty($fields['option_value']) &&
      (count(array_filter($fields['option_label'])) == 0) &&
      (count(array_filter($fields['option_value'])) == 0)
    ) {
      $errors['option_label[1]'] = ts('Enter at least one result option.');
      return $errors;
    }
    elseif (empty($fields['option_label']) && empty($fields['option_value'])) {
      return $errors;
    }

    if (
      $fields['option_type'] == 2 && empty($fields['option_group_id'])
    ) {
      $errors['option_group_id'] = ts("Please select a Survey Result Set.");
      return $errors;
    }

    $_flagOption = $_rowError = 0;
    $_showHide = new CRM_Core_ShowHideBlocks();

    //capture duplicate Custom option values
    if (!empty($fields['option_value'])) {
      $countValue = count($fields['option_value']);
      $uniqueCount = count(array_unique($fields['option_value']));

      if ($countValue > $uniqueCount) {
        $start = 1;
        while ($start < self::NUM_OPTION) {
          $nextIndex = $start + 1;

          while ($nextIndex <= self::NUM_OPTION) {
            if ($fields['option_value'][$start] ==
              $fields['option_value'][$nextIndex] &&
              !empty($fields['option_value'][$nextIndex])
            ) {

              $errors['option_value[' . $start .
              ']'] = ts('Duplicate Option values');
              $errors['option_value[' . $nextIndex .
              ']'] = ts('Duplicate Option values');
              $_flagOption = 1;
            }
            $nextIndex++;
          }
          $start++;
        }
      }
    }

    //capture duplicate Custom Option label
    if (!empty($fields['option_label'])) {
      $countValue = count($fields['option_label']);
      $uniqueCount = count(array_unique($fields['option_label']));

      if ($countValue > $uniqueCount) {
        $start = 1;
        while ($start < self::NUM_OPTION) {
          $nextIndex = $start + 1;

          while ($nextIndex <= self::NUM_OPTION) {
            if ($fields['option_label'][$start] ==
              $fields['option_label'][$nextIndex] &&
              !empty($fields['option_label'][$nextIndex])
            ) {
              $errors['option_label[' . $start .
              ']'] = ts('Duplicate Option label');
              $errors['option_label[' . $nextIndex .
              ']'] = ts('Duplicate Option label');
              $_flagOption = 1;
            }
            $nextIndex++;
          }
          $start++;
        }
      }
    }

    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      if (!$fields['option_label'][$i]) {
        if ($fields['option_value'][$i]) {
          $errors['option_label[' . $i .
          ']'] = ts('Option label cannot be empty');
          $_flagOption = 1;
        }
        else {
          $_emptyRow = 1;
        }
      }
      elseif (!strlen(trim($fields['option_value'][$i]))) {
        if (!$fields['option_value'][$i]) {
          $errors['option_value[' . $i .
          ']'] = ts('Option value cannot be empty');
          $_flagOption = 1;
        }
      }

      if (!empty($fields['option_interval'][$i]) &&
        !CRM_Utils_Rule::integer($fields['option_interval'][$i])
      ) {
        $_flagOption = 1;
        $errors['option_interval[' . $i .
        ']'] = ts('Please enter a valid integer.');
      }

      $showBlocks = 'optionField_' . $i;
      if ($_flagOption) {
        $_showHide->addShow($showBlocks);
        $_rowError = 1;
      }

      if (!empty($_emptyRow)) {
        $_showHide->addHide($showBlocks);
      }
      else {
        $_showHide->addShow($showBlocks);
      }

      if ($i == self::NUM_OPTION) {
        $hideBlock = 'additionalOption';
        $_showHide->addHide($hideBlock);
      }

      $_flagOption = $_emptyRow = 0;
    }
    $_showHide->addToTemplate();

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // store the submitted values in an array
    $status = '';
    $params = $this->controller->exportValues($this->_name);
    $params['id'] = $this->_surveyId;

    $updateResultSet = FALSE;
    $resultSetOptGrpId = NULL;
    if ((($params['option_type'] ?? NULL) == 2) &&
      !empty($params['option_group_id'])
    ) {
      $updateResultSet = TRUE;
      $resultSetOptGrpId = $params['option_group_id'];
    }

    if ($updateResultSet) {
      $optionValue = new CRM_Core_DAO_OptionValue();
      $optionValue->option_group_id = $resultSetOptGrpId;
      $optionValue->delete();

      $params['result_id'] = $resultSetOptGrpId;
    }
    else {
      $opGroupName = 'civicrm_survey_' . rand(10, 1000) . '_' . date('YmdHis');

      $optionGroup = new CRM_Core_DAO_OptionGroup();
      $optionGroup->name = $opGroupName;
      $optionGroup->title = $this->_values['title'] . ' Result Set';
      $optionGroup->is_active = 1;
      $optionGroup->save();

      $params['result_id'] = $optionGroup->id;
    }

    foreach ($params['option_value'] as $k => $v) {
      if (strlen(trim($v))) {
        $optionValue = new CRM_Core_DAO_OptionValue();
        $optionValue->option_group_id = $params['result_id'];
        $optionValue->label = $params['option_label'][$k];
        $optionValue->name = CRM_Utils_String::titleToVar($params['option_label'][$k]);
        $optionValue->value = trim($v);
        $optionValue->weight = $params['option_weight'][$k];
        $optionValue->is_active = 1;
        $optionValue->filter = $params['option_interval'][$k];

        if (!empty($params['default_option']) &&
          $params['default_option'] == $k
        ) {
          $optionValue->is_default = 1;
        }

        $optionValue->save();

      }
    }

    $survey = CRM_Campaign_BAO_Survey::writeRecord($params);

    // create report if required.
    if (!$this->_reportId && $survey->id && !empty($params['create_report'])) {
      $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
      $activityStatus = array_flip($activityStatus);
      $this->_params = [
        'name' => "survey_{$survey->id}",
        'title' => $params['report_title'] ?: $this->_values['title'],
        'status_id_op' => 'eq',
        // reserved status
        'status_id_value' => $activityStatus['Scheduled'],
        'survey_id_value' => [$survey->id],
        'description' => ts('Detailed report for canvassing, phone-banking, walk lists or other surveys.'),
      ];
      //Default value of order by
      $this->_params['order_bys'] = [
        1 => [
          'column' => 'sort_name',
          'order' => 'ASC',
        ],
      ];
      // for WalkList or default
      $displayFields = [
        'id',
        'sort_name',
        'result',
        'street_number',
        'street_name',
        'street_unit',
        'survey_response',
      ];
      if (CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'WalkList') ==
        $this->_values['activity_type_id']
      ) {
        $this->_params['order_bys'] = [
          1 => [
            'column' => 'street_name',
            'order' => 'ASC',
          ],
          2 => [
            'column' => 'street_number_odd_even',
            'order' => 'ASC',
          ],
          3 => [
            'column' => 'street_number',
            'order' => 'ASC',
          ],
          4 => [
            'column' => 'sort_name',
            'order' => 'ASC',
          ],
        ];
      }
      elseif (CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'PhoneBank') ==
        $this->_values['activity_type_id']
      ) {
        array_push($displayFields, 'phone');
      }
      elseif ((CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Survey') ==
          $this->_values['activity_type_id']) ||
        (CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Canvass') ==
          $this->_values['activity_type_id'])
      ) {
        array_push($displayFields, 'phone', 'city', 'state_province_id', 'postal_code', 'email');
      }
      foreach ($displayFields as $key) {
        $this->_params['fields'][$key] = 1;
      }
      $this->_createNew = TRUE;
      $this->_id = CRM_Report_Utils_Report::getInstanceIDForValue('survey/detail');
      CRM_Report_Form_Instance::setDefaultValues($this, $this->_defaults);
      $this->_params = array_merge($this->_params, $this->_defaults);
      CRM_Report_Form_Instance::postProcess($this, FALSE);

      $query = "SELECT MAX(id) FROM civicrm_report_instance WHERE name = %1";
      $reportID = CRM_Core_DAO::singleValueQuery($query, [
        1 => [
          "survey_{$survey->id}",
          'String',
        ],
      ]);
      if ($reportID) {
        $url = CRM_Utils_System::url("civicrm/report/instance/{$reportID}", 'reset=1');
        $status = ts("A Survey Detail Report <a href='%1'>%2</a> has been created.",
          [1 => $url, 2 => $this->_params['title']]);
      }
    }

    if ($status) {
      // reset status as we don't want status set by Instance::postProcess
      $session = CRM_Core_Session::singleton();
      $session->getStatus(TRUE);
      // set new status
      CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
    }

    parent::endPostProcess();
  }

}
