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
 * form to process actions on the field aspect of Custom
 */
class CRM_Custom_Form_Field extends CRM_Core_Form {

  /**
   * Constants for number of options for data types of multiple option.
   */
  const NUM_OPTION = 11;

  /**
   * The custom group id saved to the session for an update.
   *
   * @var int
   */
  public $_gid;

  /**
   * The field id, used when editing the field
   *
   * @var int
   */
  protected $_id;

  /**
   * Array of custom field values if update mode.
   * @var array
   */
  protected $_values;

  /**
   * Array for valid combinations of data_type & html_type
   *
   * @var array
   */
  public static $htmlTypesWithOptions = ['Select', 'Radio', 'CheckBox', 'Autocomplete-Select'];

  /**
   * Maps each data_type to allowed html_type options
   *
   * @var array[]
   */
  public static $_dataToHTML = [
    'String' => ['Text', 'Select', 'Radio', 'CheckBox', 'Autocomplete-Select', 'Hidden'],
    'Int' => ['Text', 'Select', 'Radio', 'Hidden'],
    'Float' => ['Text', 'Select', 'Radio', 'Hidden'],
    'Money' => ['Text', 'Select', 'Radio', 'Hidden'],
    'Memo' => ['TextArea', 'RichTextEditor'],
    'Date' => ['Select Date'],
    'Boolean' => ['Radio'],
    'StateProvince' => ['Select'],
    'Country' => ['Select'],
    'File' => ['File'],
    'Link' => ['Link'],
    'ContactReference' => ['Autocomplete-Select'],
    'EntityReference' => ['Autocomplete-Select'],
  ];

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->setAction($this->_id ? CRM_Core_Action::UPDATE : CRM_Core_Action::ADD);

    $this->assign('dataToHTML', self::$_dataToHTML);

    $this->_values = [];
    //get the values form db if update.
    if ($this->_id) {
      CRM_Core_BAO_CustomField::retrieve(['id' => $this->_id], $this->_values);
      // note_length is an alias for the text_length field
      $this->_values['note_length'] = $this->_values['text_length'] ?? NULL;
      // custom group id
      $this->_gid = $this->_values['custom_group_id'];
    }
    else {
      // custom group id
      $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this);
    }

    if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'is_reserved')) {
      // I think this does not have ts() because the only time you would see
      // this is if you manually made a url you weren't supposed to.
      CRM_Core_Error::statusBounce("You cannot add or edit fields in a reserved custom field-set.");
    }

    if ($this->_gid) {
      $url = CRM_Utils_System::url('civicrm/admin/custom/group/field',
        "reset=1&gid={$this->_gid}"
      );

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($url);
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    // Defaults for update mode
    if ($this->_id) {
      $this->assign('id', $this->_id);
      $this->_gid = $defaults['custom_group_id'];
      $defaultValue = $defaults['default_value'] ?? NULL;

      if ($defaults['data_type'] == 'ContactReference' && !empty($defaults['filter'])) {
        $contactRefFilter = 'Advance';
        if (strpos($defaults['filter'], 'action=lookup') !== FALSE &&
          strpos($defaults['filter'], 'group=') !== FALSE
        ) {
          $filterParts = explode('&', $defaults['filter']);

          if (count($filterParts) == 2) {
            $contactRefFilter = 'Group';
            foreach ($filterParts as $part) {
              if (strpos($part, 'group=') === FALSE) {
                continue;
              }
              $groups = substr($part, strpos($part, '=') + 1);
              foreach (explode(',', $groups) as $grp) {
                if (CRM_Utils_Rule::positiveInteger($grp)) {
                  $defaults['group_id'][] = $grp;
                }
              }
            }
          }
        }
        $defaults['filter_selected'] = $contactRefFilter;
      }

      $defaults['option_type'] = 2;
    }

    // Defaults for create mode
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['data_type'] = 'String';
      $defaults['html_type'] = 'Text';
      $defaults['is_active'] = 1;
      $defaults['option_type'] = 1;
      $defaults['is_search_range'] = 0;
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_CustomField', ['custom_group_id' => $this->_gid]);
      $defaults['text_length'] = 255;
      $defaults['note_columns'] = 60;
      $defaults['note_rows'] = 4;
      $defaults['is_view'] = 0;
      $defaults['fk_entity_on_delete'] = CRM_Core_DAO_CustomField::fields()['fk_entity_on_delete']['default'];

      if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'is_multiple')) {
        $defaults['in_selector'] = 1;
      }
    }

    // Set defaults for option values.
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      $defaults['option_status[' . $i . ']'] = 1;
      $defaults['option_weight[' . $i . ']'] = $i;
      $defaults['option_value[' . $i . ']'] = $i;
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if ($this->_gid) {
      $this->_title = CRM_Core_BAO_CustomGroup::getGroup(['id' => $this->_gid])['title'];
      $this->setTitle($this->_title . ' - ' . ($this->_id ? ts('Edit Field') : ts('New Field')));
    }
    $this->assign('gid', $this->_gid);

    // lets trim all the whitespace
    $this->applyFilter('__ALL__', 'trim');

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_CustomField');

    // label
    $this->add('text',
      'label',
      ts('Field Label'),
      $attributes['label'],
      TRUE
    );

    // FIXME: Switch addField to use APIv4 so we don't get those legacy options from v3
    $htmlOptions = CRM_Core_BAO_CustomField::buildOptions('html_type', 'create');

    $this->addField('data_type', ['class' => 'twenty'], TRUE);
    $this->addField('html_type', ['class' => 'twenty', 'options' => $htmlOptions], TRUE);

    if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'is_multiple')) {
      $this->add('advcheckbox', 'in_selector', ts('Display in Table?'));
    }

    $optionGroupParams = [
      'is_reserved' => 0,
      'is_active' => 1,
      'options' => ['limit' => 0, 'sort' => "title ASC"],
      'return' => ['title'],
    ];

    $this->add('advcheckbox', 'serialize', ts('Multi-Select'));

    $this->addAutocomplete('fk_entity', ts('Entity'), [
      'class' => 'twenty',
      // Don't allow entity to be changed once field is created
      'disabled' => $this->_action == CRM_Core_Action::UPDATE && !empty($this->_values['fk_entity']),
      'entity' => 'Entity',
      'select' => ['minimumInputLength' => 0],
    ]);

    $this->addField('fk_entity_on_delete');

    $isUpdateAction = $this->_action == CRM_Core_Action::UPDATE;
    if ($isUpdateAction) {
      $this->freeze('data_type');
      if (!empty($this->_values['option_group_id'])) {
        $this->assign('hasOptionGroup', in_array($this->_values['html_type'], self::$htmlTypesWithOptions));
        // Before dev/core#155 we didn't set the is_reserved flag properly, which should be handled by the upgrade script...
        //  but it is still possible that existing installs may have optiongroups linked to custom fields that are marked reserved.
        $optionGroupParams['id'] = $this->_values['option_group_id'];
        $optionGroupParams['options']['or'] = [["is_reserved", "id"]];
      }
    }
    $this->assign('existingMultiValueCount', ($isUpdateAction && !empty($this->_values['serialize'])) ? $this->getMultiValueCount() : NULL);
    $this->assign('originalSerialize', $isUpdateAction ? $this->_values['serialize'] : NULL);
    $this->assign('originalHtmlType', $isUpdateAction ? $this->_values['html_type'] : NULL);

    // Retrieve optiongroups for selection list
    $optionGroupMetadata = civicrm_api3('OptionGroup', 'get', $optionGroupParams);

    // OptionGroup selection
    $optionTypes = ['1' => ts('Create a new set of options')];

    if (!empty($optionGroupMetadata['values'])) {
      $emptyOptGroup = FALSE;
      $optionGroups = CRM_Utils_Array::collect('title', $optionGroupMetadata['values']);
      $optionTypes['2'] = ts('Reuse an existing set');

      $this->add('select',
        'option_group_id',
        ts('Multiple Choice Option Sets'),
        [
          '' => ts('- select -'),
        ] + $optionGroups
      );
    }
    else {
      // No custom (non-reserved) option groups
      $emptyOptGroup = TRUE;
    }

    $element = &$this->addRadio('option_type',
      ts('Option Type'),
      $optionTypes,
      [
        'onclick' => "showOptionSelect();",
      ], '<br/>'
    );
    // if empty option group freeze the option type.
    if ($emptyOptGroup) {
      $element->freeze();
    }

    $contactGroups = CRM_Core_PseudoConstant::group();
    asort($contactGroups);

    $this->add('select',
      'group_id',
      ts('Limit List to Group'),
      $contactGroups,
      FALSE,
      ['multiple' => 'multiple', 'class' => 'crm-select2']
    );

    $this->add('text',
      'filter',
      ts('Advanced Filter'),
      $attributes['filter']
    );

    $this->add('hidden', 'filter_selected', 'Group', ['id' => 'filter_selected']);

    // form fields of Custom Option rows
    $defaultOption = [];
    $_showHide = new CRM_Core_ShowHideBlocks();
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

      $optionAttributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue');
      // label
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

      // is active ?
      $this->add('checkbox', "option_status[$i]", ts('Active?'));

      $defaultOption[$i] = NULL;

      //for checkbox handling of default option
      $this->add('checkbox', "default_checkbox_option[$i]", NULL);
    }

    //default option selection
    $this->addRadio('default_option', NULL, $defaultOption);

    $_showHide->addToTemplate();

    // text length for alpha numeric data types
    $this->add('number',
      'text_length',
      ts('Database field length'),
      $attributes['text_length'] + ['min' => 1],
      FALSE
    );
    $this->addRule('text_length', ts('Value should be a positive number'), 'integer');

    $this->add('text',
      'start_date_years',
      ts('Dates may be up to'),
      $attributes['start_date_years'],
      FALSE
    );
    $this->add('text',
      'end_date_years',
      ts('Dates may be up to'),
      $attributes['end_date_years'],
      FALSE
    );

    $this->addRule('start_date_years', ts('Value should be a positive number'), 'integer');
    $this->addRule('end_date_years', ts('Value should be a positive number'), 'integer');

    $this->add('select', 'date_format', ts('Date Format'),
      ['' => ts('- select -')] + CRM_Core_SelectValues::getDatePluginInputFormats()
    );

    $this->add('select', 'time_format', ts('Time'),
      ['' => ts('- none -')] + CRM_Core_SelectValues::getTimeFormats()
    );

    // for Note field
    $this->add('number',
      'note_columns',
      ts('Width (columns)') . ' ',
      $attributes['note_columns'],
      FALSE
    );
    $this->add('number',
      'note_rows',
      ts('Height (rows)') . ' ',
      $attributes['note_rows'],
      FALSE
    );
    $this->add('number',
      'note_length',
      ts('Maximum length') . ' ',
      // note_length is an alias for the text-length field
      $attributes['text_length'],
      FALSE
    );

    $this->addRule('note_columns', ts('Value should be a positive number'), 'positiveInteger');
    $this->addRule('note_rows', ts('Value should be a positive number'), 'positiveInteger');
    $this->addRule('note_length', ts('Value should be a positive number'), 'positiveInteger');

    // weight
    $this->add('number', 'weight', ts('Order'),
      $attributes['weight'],
      TRUE
    );
    $this->addRule('weight', ts('is a numeric field'), 'numeric');

    // checkbox / radio options per line
    $this->add('number', 'options_per_line', ts('Options Per Line'), ['min' => 0]);
    $this->addRule('options_per_line', ts('must be a numeric value'), 'numeric');

    // default value, help pre, help post
    $this->add('text', 'default_value', ts('Default Value'),
      $attributes['default_value']
    );
    $this->add('textarea', 'help_pre', ts('Field Pre Help'),
      $attributes['help_pre']
    );
    $this->add('textarea', 'help_post', ts('Field Post Help'),
      $attributes['help_post']
    );

    $this->add('advcheckbox', 'is_required', ts('Required'));
    $this->addElement('advcheckbox', 'is_searchable', ts('Optimize for Search'));
    $this->addRadio('is_search_range', ts('Search by Range'), [ts('No'), ts('Yes')]);
    $this->add('advcheckbox', 'is_active', ts('Active'));
    $this->add('advcheckbox', 'is_view', ts('View Only'));

    $buttons = [
      [
        'type' => 'done',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'next',
        'name' => ts('Save and New'),
        'subName' => 'new',
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];
    // Save & new only applies to adding a field
    if ($this->_id) {
      unset($buttons[1]);
    }

    // add buttons
    $this->addButtons($buttons);

    // add a form rule to check default value
    $this->addFormRule(['CRM_Custom_Form_Field', 'formRule'], $this);

    // if view mode pls freeze it with the done button.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $url = CRM_Utils_System::url('civicrm/admin/custom/group/field', 'reset=1&gid=' . $this->_gid);
      $this->addElement('xbutton',
        'done',
        ts('Done'),
        [
          'type' => 'button',
          'onclick' => "location.href='$url'",
        ]
      );
    }
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $files
   * @param self $self
   *
   * @return array
   *   if errors then list of errors to be posted back to the form,
   *                  true otherwise
   */
  public static function formRule($fields, $files, $self) {
    $default = $fields['default_value'] ?? NULL;

    $errors = [];

    self::clearEmptyOptions($fields);

    //validate field label as well as name.
    $title = $fields['label'];
    $name = CRM_Utils_String::munge($title, '_', 64);
    // CRM-7564
    $gId = $self->_gid;
    $query = 'select count(*) from civicrm_custom_field where ( name like %1 OR label like %2 ) and id != %3 and custom_group_id = %4';
    $fldCnt = CRM_Core_DAO::singleValueQuery($query, [
      1 => [$name, 'String'],
      2 => [$title, 'String'],
      3 => [(int) $self->_id, 'Integer'],
      4 => [$gId, 'Integer'],
    ]);
    if ($fldCnt) {
      $errors['label'] = ts('Custom field \'%1\' already exists in Database.', [1 => $title]);
    }

    //checks the given custom field name doesnot start with digit
    if (!empty($title)) {
      // gives the ascii value
      $asciiValue = ord($title[0]);
      if ($asciiValue >= 48 && $asciiValue <= 57) {
        $errors['label'] = ts("Name cannot not start with a digit");
      }
    }

    // ensure that the label is not 'id'
    if (strtolower($title) == 'id') {
      $errors['label'] = ts("You cannot use 'id' as a field label.");
    }

    $dataType = $fields['data_type'];

    if ($default || $dataType == 'ContactReference') {
      switch ($dataType) {
        case 'Int':
          if (!CRM_Utils_Rule::integer($default)) {
            $errors['default_value'] = ts('Please enter a valid integer.');
          }
          break;

        case 'Float':
          if (!CRM_Utils_Rule::numeric($default)) {
            $errors['default_value'] = ts('Please enter a valid number.');
          }
          break;

        case 'Money':
          if (!CRM_Utils_Rule::money($default)) {
            $errors['default_value'] = ts('Please enter a valid number.');
          }
          break;

        case 'Link':
          if (!CRM_Utils_Rule::url($default)) {
            $errors['default_value'] = ts('Please enter a valid link.');
          }
          break;

        case 'Date':
          if (!CRM_Utils_Rule::date($default)) {
            $errors['default_value'] = ts('Please enter a valid date as default value using YYYY-MM-DD format. Example: 2004-12-31.');
          }
          break;

        case 'Boolean':
          if ($default != '1' && $default != '0') {
            $errors['default_value'] = ts('Please enter 1 (for Yes) or 0 (for No) if you want to set a default value.');
          }
          break;

        case 'Country':
          if (!empty($default)) {
            $query = "SELECT count(*) FROM civicrm_country WHERE id = %1";
            $params = [1 => [$fields['default_value'], 'Int']];
            if (CRM_Core_DAO::singleValueQuery($query, $params) <= 0) {
              $errors['default_value'] = ts('Invalid default value for country.');
            }
          }
          break;

        case 'StateProvince':
          if (!empty($default)) {
            $query = "
SELECT count(*)
  FROM civicrm_state_province
 WHERE id = %1";
            $params = [1 => [$fields['default_value'], 'Int']];
            if (CRM_Core_DAO::singleValueQuery($query, $params) <= 0) {
              $errors['default_value'] = ts('The invalid default value for State/Province data type');
            }
          }
          break;

        case 'ContactReference':
          if ($fields['filter_selected'] == 'Advance' && !empty($fields['filter'])) {
            if (strpos($fields['filter'], 'entity=') !== FALSE) {
              $errors['filter'] = ts("Please do not include entity parameter (entity is always 'contact')");
            }
            elseif (strpos($fields['filter'], 'action=get') === FALSE) {
              $errors['filter'] = ts("Only 'get' action is supported.");
            }
          }
          $self->setDefaults(['filter_selected', $fields['filter_selected']]);
          break;
      }
    }

    if ($dataType === 'EntityReference' && $self->_action == CRM_Core_Action::ADD) {
      if (empty($fields['fk_entity'])) {
        $errors['fk_entity'] = ts('Selecting an entity is required');
      }
    }

    if ($dataType == 'Date') {
      if (!$fields['date_format']) {
        $errors['date_format'] = ts('Please select a date format.');
      }
    }

    /** Check the option values entered
     *  Appropriate values are required for the selected datatype
     *  Incomplete row checking is also required.
     */
    $_flagOption = $_rowError = 0;
    $_showHide = new CRM_Core_ShowHideBlocks();
    $htmlType = $fields['html_type'];

    if (isset($fields['option_type']) && $fields['option_type'] == 1) {
      //capture duplicate Custom option values
      if (!empty($fields['option_value'])) {
        $countValue = count($fields['option_value']);
        $uniqueCount = count(array_unique($fields['option_value']));

        if ($countValue > $uniqueCount) {

          $start = 1;
          while ($start < self::NUM_OPTION) {
            $nextIndex = $start + 1;
            while ($nextIndex <= self::NUM_OPTION) {
              if ($fields['option_value'][$start] == $fields['option_value'][$nextIndex] &&
                strlen($fields['option_value'][$nextIndex])
              ) {
                $errors['option_value[' . $start . ']'] = ts('Duplicate Option values');
                $errors['option_value[' . $nextIndex . ']'] = ts('Duplicate Option values');
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
              if ($fields['option_label'][$start] == $fields['option_label'][$nextIndex] &&
                !empty($fields['option_label'][$nextIndex])
              ) {
                $errors['option_label[' . $start . ']'] = ts('Duplicate Option label');
                $errors['option_label[' . $nextIndex . ']'] = ts('Duplicate Option label');
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
            $errors['option_label[' . $i . ']'] = ts('Option label cannot be empty');
            $_flagOption = 1;
          }
          else {
            $_emptyRow = 1;
          }
        }
        else {
          if (!strlen(trim($fields['option_value'][$i]))) {
            if (!$fields['option_value'][$i]) {
              $errors['option_value[' . $i . ']'] = ts('Option value cannot be empty');
              $_flagOption = 1;
            }
          }
        }

        if ($fields['option_value'][$i] && $dataType != 'String') {
          if ($dataType == 'Int') {
            if (!CRM_Utils_Rule::integer($fields['option_value'][$i])) {
              $_flagOption = 1;
              $errors['option_value[' . $i . ']'] = ts('Please enter a valid integer.');
            }
          }
          elseif ($dataType == 'Money') {
            if (!CRM_Utils_Rule::money($fields['option_value'][$i])) {
              $_flagOption = 1;
              $errors['option_value[' . $i . ']'] = ts('Please enter a valid money value.');
            }
          }
          else {
            if (!CRM_Utils_Rule::numeric($fields['option_value'][$i])) {
              $_flagOption = 1;
              $errors['option_value[' . $i . ']'] = ts('Please enter a valid number.');
            }
          }
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
    }
    elseif (in_array($htmlType, self::$htmlTypesWithOptions) &&
      !in_array($dataType, ['Boolean', 'Country', 'StateProvince', 'ContactReference', 'EntityReference'])
    ) {
      if (!$fields['option_group_id']) {
        $errors['option_group_id'] = ts('You must select a Multiple Choice Option set if you chose Reuse an existing set.');
      }
      else {
        $query = "
SELECT count(*)
FROM   civicrm_custom_field
WHERE  data_type != %1
AND    option_group_id = %2";
        $params = [
          1 => [$dataType, 'String'],
          2 => [$fields['option_group_id'], 'Integer'],
        ];
        $count = CRM_Core_DAO::singleValueQuery($query, $params);
        if ($count > 0) {
          $errors['option_group_id'] = ts('The data type of the multiple choice option set you\'ve selected does not match the data type assigned to this field.');
        }
      }
    }

    $assignError = new CRM_Core_Page();
    if ($_rowError) {
      $_showHide->addToTemplate();
      $assignError->assign('optionRowError', $_rowError);
    }
    else {
      if (isset($htmlType)) {
        switch ($htmlType) {
          case 'Radio':
          case 'CheckBox':
          case 'Select':
            $_fieldError = 1;
            $assignError->assign('fieldError', $_fieldError);
            break;

          default:
            $_fieldError = 0;
            $assignError->assign('fieldError', $_fieldError);
        }
      }

      for ($idx = 1; $idx <= self::NUM_OPTION; $idx++) {
        $showBlocks = 'optionField_' . $idx;
        if (!empty($fields['option_label'][$idx])) {
          $_showHide->addShow($showBlocks);
        }
        else {
          $_showHide->addHide($showBlocks);
        }
      }
      $_showHide->addToTemplate();
    }

    // we can not set require and view at the same time.
    if (!empty($fields['is_required']) && !empty($fields['is_view'])) {
      $errors['is_view'] = ts('Can not set this field Required and View Only at the same time.');
    }

    // If switching to a new option list, validate existing data
    if (empty($errors) && $self->_id && in_array($htmlType, self::$htmlTypesWithOptions) &&
      !in_array($dataType, ['Boolean', 'Country', 'StateProvince', 'ContactReference', 'EntityReference'])) {
      $oldHtmlType = $self->_values['html_type'];
      $oldOptionGroup = $self->_values['option_group_id'];
      if ($oldHtmlType === 'Text' || $oldOptionGroup != $fields['option_group_id'] || $fields['option_type'] == 1) {
        if ($fields['option_type'] == 2) {
          $optionQuery = "SELECT value FROM civicrm_option_value WHERE option_group_id = " . (int) $fields['option_group_id'];
        }
        else {
          $options = array_map(['CRM_Core_DAO', 'escapeString'], array_filter($fields['option_value'], 'strlen'));
          $optionQuery = '"' . implode('","', $options) . '"';
        }
        $table = CRM_Core_BAO_CustomGroup::getGroup(['id' => $self->_gid])['table_name'];
        $column = $self->_values['column_name'];
        $invalid = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `$table` WHERE `$column` NOT IN ($optionQuery)");
        if ($invalid) {
          $errors['html_type'] = ts('Cannot impose option list because there is existing data which does not match the options.');
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   *
   * @return void
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    self::clearEmptyOptions($params);

    // Automatically disable 'is_search_range' if the field does not support it
    if (in_array($params['data_type'], ['Int', 'Float', 'Money', 'Date'])) {
      if (in_array($params['html_type'], ['Radio', 'Select'])) {
        $params['is_search_range'] = 0;
      }
    }
    else {
      $params['is_search_range'] = 0;
    }

    $params['serialize'] = $this->determineSerializeType($params);

    $filter = 'null';
    if ($params['data_type'] == 'ContactReference' && !empty($params['filter_selected'])) {
      if ($params['filter_selected'] == 'Advance' && trim($params['filter'] ?? '')) {
        $filter = trim($params['filter']);
      }
      elseif ($params['filter_selected'] == 'Group' && !empty($params['group_id'])) {
        $filter = 'action=lookup&group=' . implode(',', $params['group_id']);
      }
    }
    if ($params['data_type'] !== 'EntityReference') {
      $params['filter'] = $filter;
    }

    // fix for CRM-316
    $oldWeight = NULL;
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $fieldValues = ['custom_group_id' => $this->_gid];
      if ($this->_id) {
        $oldWeight = $this->_values['weight'];
      }
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_CustomField', $oldWeight, $params['weight'], $fieldValues);
    }

    // The text_length attribute for Memo fields is in a different input as there
    // are different label, help text and default value than for other type fields
    if ($params['data_type'] == "Memo") {
      $params['text_length'] = $params['note_length'];
    }

    // need the FKEY - custom group id
    $params['custom_group_id'] = $this->_gid;

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }
    $customField = CRM_Core_BAO_CustomField::create($params);
    $this->_id = $customField->id;

    // reset the cache
    Civi::cache('fields')->flush();

    $msg = '<p>' . ts("Custom field '%1' has been saved.", [1 => $customField->label]) . '</p>';

    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      $msg .= '<p>' . ts("Ready to add another.") . '</p>';
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/field/add',
        'reset=1&gid=' . $this->_gid
      ));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/field',
        'reset=1&gid=' . $this->_gid
      ));
    }
    $session->setStatus($msg, ts('Saved'), 'success');

    // Add data when in ajax contect
    $this->ajaxResponse['customField'] = $customField->toArray();
  }

  /**
   * Removes value from fields with no label.
   *
   * This allows default values to be set in the form, but ignored in post-processing.
   *
   * @param array $fields
   */
  public static function clearEmptyOptions(&$fields) {
    foreach ($fields['option_label'] as $i => $label) {
      if (!strlen(trim($label))) {
        $fields['option_value'][$i] = '';
      }
    }
  }

  /**
   * Get number of existing records for this field that contain more than one serialized value.
   *
   * @return int
   * @throws CRM_Core_Exception
   */
  public function getMultiValueCount() {
    $table = CRM_Core_BAO_CustomGroup::getGroup(['id' => $this->_gid])['table_name'];
    $column = $this->_values['column_name'];
    $sp = CRM_Core_DAO::VALUE_SEPARATOR;
    $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` LIKE '{$sp}%{$sp}%{$sp}'";
    return (int) CRM_Core_DAO::singleValueQuery($sql);
  }

  /**
   * @return string
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * @return string
   */
  public function getDefaultEntity() {
    return 'CustomField';
  }

  /**
   * Determine the serialize type based on form values.
   * @param array $params The submitted form values.
   * @return int
   *   The serialize type - CRM_Core_DAO::SERIALIZE_XXX or 0
   */
  public function determineSerializeType($params) {
    if ($params['html_type'] === 'Select' || $params['html_type'] === 'Autocomplete-Select') {
      return !empty($params['serialize']) ? CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND : 0;
    }
    else {
      return $params['html_type'] == 'CheckBox' ? CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND : 0;
    }
  }

}
