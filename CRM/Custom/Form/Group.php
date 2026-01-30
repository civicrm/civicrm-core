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
 * form to process actions on the set aspect of Custom Data
 */
class CRM_Custom_Form_Group extends CRM_Admin_Form {

  /**
   * Have any custom data records been saved yet?
   * If not we can be more lenient about making changes.
   *
   * @var bool
   */
  protected $_isGroupEmpty = TRUE;

  /**
   * Use APIv4 to load values.
   * @var string
   */
  protected $retrieveMethod = 'api4';

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->preventAjaxSubmit();
    parent::preProcess();

    $this->setAction($this->_id ? CRM_Core_Action::UPDATE : CRM_Core_Action::ADD);

    if ($this->_id) {
      if ($this->_values['is_reserved']) {
        CRM_Core_Error::statusBounce("You cannot edit the settings of a reserved custom field-set.");
      }
      $this->_isGroupEmpty = CRM_Core_BAO_CustomGroup::isGroupEmpty($this->_id);
      $this->setTitle(ts('Edit %1', [1 => $this->_values['title']]));
    }
    // Used by I18n/Dialog
    $this->assign('gid', $this->_id);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param self $self
   *
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];

    //validate group title as well as name.
    $title = $fields['title'];
    $name = CRM_Utils_String::munge($title, '_', 64);
    $query = 'select count(*) from civicrm_custom_group where ( name like %1) and id != %2';
    $grpCnt = CRM_Core_DAO::singleValueQuery($query, [
      1 => [$name, 'String'],
      2 => [(int) $self->_id, 'Integer'],
    ]);
    if ($grpCnt) {
      $errors['title'] = ts('Custom group \'%1\' already exists in Database.', [1 => $title]);
    }

    if (empty($fields['is_multiple']) && $fields['style'] == 'Tab with table') {
      $errors['style'] = ts("Display Style 'Tab with table' is only supported for multiple-record custom field sets.");
    }

    //checks the given custom set doesnot start with digit
    $title = $fields['title'];
    if (!empty($title)) {
      // gives the ascii value
      $asciiValue = ord($title[0]);
      if ($asciiValue >= 48 && $asciiValue <= 57) {
        $errors['title'] = ts("Name cannot not start with a digit");
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   *
   * @return void
   * @see valid_date
   */
  public function addRules() {
    $this->addFormRule(['CRM_Custom_Form_Group', 'formRule'], $this);
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_CustomGroup');

    // This form is largely driven by a trio of related fields:
    //  1. `extends` - entity name e.g. Activity, Contact, (plus contact types pretending to be entities e.g. Individual, Organization)
    //  2. `extends_entity_column_id` - "category" of sub_type (usually null as most entities only have one category of sub_type)
    //  3. `extends_entity_column_value` - sub_type value(s) e.g. options from `activity_type_id`
    // Most entities have no options for field 2. For them, it will be hidden from the form, and
    // the pair of fields 1 & 3 will act like a normal chain-select, (value of 1 controls the options shown in 3).
    // For extra-complex entities like Participant, fields 1 + 2 will act like a compound key to
    // control the options in field 3.

    // Get options for the `extends` field.
    $extendsOptions = CRM_Core_BAO_CustomGroup::getCustomGroupExtendsOptions();
    // Sort by label
    $labels = array_column($extendsOptions, 'label');
    array_multisort($labels, SORT_NATURAL, $extendsOptions);

    // Get options for `extends_entity_column_id` (rarely used except for participants)
    // Format as an array keyed by entity to match with 'extends' values, e.g.
    // [
    //   'Participant' => [['id' => 'ParticipantRole', 'text' => 'Participants (Role)'], ...]],
    // ]
    $entityColumnIdOptions = [];
    foreach (CRM_Core_BAO_CustomGroup::getExtendsEntityColumnIdOptions() as $idOption) {
      $entityColumnIdOptions[$idOption['extends']][] = [
        'id' => $idOption['id'],
        'text' => $idOption['label'],
      ];
    }

    $extendsValue = $this->_values['extends'] ?? NULL;
    $initialEntityColumnIdOptions = $entityColumnIdOptions[$extendsValue] ?? [];

    $initialEntityColumnValueOptions = [];
    if ($extendsValue) {
      $initialEntityColumnValueOptions = civicrm_api4('CustomGroup', 'getFields', [
        'where' => [['name', '=', 'extends_entity_column_value']],
        'action' => 'create',
        'loadOptions' => ['id', 'label'],
        'values' => $this->_values,
      ], 0)['options'];
    }

    // Assign data for use by js chain-selects
    $this->assign('entityColumnIdOptions', $entityColumnIdOptions);
    // List of entities that allow `is_multiple`
    $this->assign('allowMultiple', array_column($extendsOptions, 'allow_is_multiple', 'id'));
    // Used by warnDataLoss
    $this->assign('defaultSubtypes', $this->_values['extends_entity_column_value'] ?? []);
    // Used to initially hide selects with no options
    $this->assign('emptyEntityColumnId', empty($initialEntityColumnIdOptions));
    $this->assign('emptyEntityColumnValue', empty($initialEntityColumnValueOptions));

    // Add form fields
    $this->add('text', 'title', ts('Set Name'), $attributes['title'], TRUE);

    $this->add('select2', 'extends', ts('Used For'), $extendsOptions, TRUE, ['placeholder' => ts('Select')]);

    $this->add('select2', 'extends_entity_column_id', ts('Type'), $initialEntityColumnIdOptions, FALSE, ['placeholder' => ts('Any')]);

    $this->add('select2', 'extends_entity_column_value', ts('Sub Type'), $initialEntityColumnValueOptions, FALSE, ['multiple' => TRUE, 'placeholder' => ts('Any')]);

    // help text
    $this->add('wysiwyg', 'help_pre', ts('Pre-form Help'), ['class' => 'collapsed']);
    $this->add('wysiwyg', 'help_post', ts('Post-form Help'), ['class' => 'collapsed']);

    // weight
    $this->add('number', 'weight', ts('Order'), $attributes['weight'], TRUE);
    $this->addRule('weight', ts('is a numeric field'), 'numeric');

    // display style
    $this->add('select', 'style', ts('Display Style'), CRM_Core_SelectValues::customGroupStyle());

    $this->add('text', 'icon', ts('Tab icon'), ['class' => 'crm-icon-picker', 'allowClear' => TRUE]);

    // is this set collapsed or expanded ?
    $this->addToggle('collapse_display', ts('Collapse on initial display'));

    // is this set collapsed or expanded ? in advanced search
    $this->addToggle('collapse_adv_display', ts('Collapse in Advanced Search'));

    // is this set active ?
    $this->addToggle('is_active', ts('Enabled'));

    //Is this set visible on public pages?
    $this->addToggle('is_public', ts('Public'));

    $this->addToggle('is_multiple', ts('Allow multiple records'),
      ['on' => ts('Multiple'), 'off' => ts('Single')]
    );

    $this->add('number', 'max_multiple', ts('Maximum number of multiple records'), ['class' => 'six', 'min' => 1, 'step' => 1]);
    $this->addRule('max_multiple', ts('is a numeric field'), 'numeric');

    // Once data exists, certain options cannot be changed
    if (!$this->_isGroupEmpty) {
      $this->getElement('extends')->freeze();
      $this->getElement('extends_entity_column_id')->freeze();
      $this->getElement('is_multiple')->setAttribute('disabled', 'disabled');
      // Don't allow max to be lowered if data already exists
      $this->getElement('max_multiple')->setAttribute('min', $this->_values['max_multiple'] ?? '0');
    }

    $buttons = [
      [
        'type' => 'next',
        'name' => ts('Save'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];
    if (!$this->_isGroupEmpty && !empty($this->_values['extends_entity_column_value'])) {
      $buttons[0]['class'] = 'crm-warnDataLoss';
    }
    $this->addButtons($buttons);
  }

  /**
   * Set default values for the form.
   * @return array
   */
  public function setDefaultValues(): array {
    $defaults = parent::setDefaultValues();
    if ($this->_action == CRM_Core_Action::ADD) {
      $defaults += [
        'weight' => CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_CustomGroup'),
        'is_active' => 1,
        'is_public' => 1,
        'collapse_adv_display' => 1,
        'style' => 'Inline',
      ];
    }
    return $defaults;
  }

  /**
   * @return void
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues('Group');
    if (!empty($params['extends_entity_column_value']) && is_string($params['extends_entity_column_value'])) {
      // Because select2
      $params['extends_entity_column_value'] = explode(',', $params['extends_entity_column_value']);
    }
    $params['overrideFKConstraint'] = 0;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
      if ($this->_values['extends'] != $params['extends']) {
        $params['overrideFKConstraint'] = 1;
      }

      if (!empty($this->_subtypes)) {
        $subtypesToBeRemoved = [];
        $subtypesToPreserve = $params['extends'][1];
        // Don't remove any value if group is extended to -any- subtype
        if (!empty($subtypesToPreserve[0])) {
          $subtypesToBeRemoved = array_diff($this->_subtypes, array_intersect($this->_subtypes, $subtypesToPreserve));
        }
        CRM_Contact_BAO_ContactType::deleteCustomRowsOfSubtype($this->_id, $subtypesToBeRemoved, $subtypesToPreserve);
      }
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      //new custom set , so lets set the created_id
      $session = CRM_Core_Session::singleton();
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }

    $result = civicrm_api3('CustomGroup', 'create', $params);
    $group = $result['values'][$result['id']];
    $this->_id = $result['id'];

    // reset the cache
    Civi::cache('fields')->flush();
    // reset ACL and system caches.
    Civi::rebuild(['system' => TRUE])->execute();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      CRM_Core_Session::setStatus(ts('Your custom field set \'%1 \' has been saved.', [1 => $group['title']]), ts('Saved'), 'success');
    }
    else {
      // Jump directly to adding a field if popups are disabled
      $action = CRM_Core_Resources::singleton()->ajaxPopupsEnabled ? '' : '/add';
      $url = CRM_Utils_System::url("civicrm/admin/custom/group/field$action", 'reset=1&new=1&gid=' . $group['id']);
      CRM_Core_Session::setStatus(ts("Your custom field set '%1' has been added. You can add custom fields now.",
        [1 => $group['title']]
      ), ts('Saved'), 'success');
      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
    }

    // prompt Drupal Views users to update $db_prefix in settings.php, if necessary
    global $db_prefix;
    $config = CRM_Core_Config::singleton();
    if (is_array($db_prefix) && $config->userSystem->viewsExists()) {
      // get table_name for each custom group
      $tables = [];
      $sql = "SELECT table_name FROM civicrm_custom_group WHERE is_active = 1";
      $result = CRM_Core_DAO::executeQuery($sql);
      while ($result->fetch()) {
        $tables[$result->table_name] = $result->table_name;
      }

      // find out which tables are missing from the $db_prefix array
      $missingTableNames = array_diff_key($tables, $db_prefix);

      if (!empty($missingTableNames)) {
        CRM_Core_Session::setStatus(ts("To ensure that all of your custom data groups are available to Views, you may need to add the following key(s) to the db_prefix array in your settings.php file: '%1'.",
          [1 => implode(', ', $missingTableNames)]
        ), ts('Note'), 'info');
      }
    }
  }

  public function getDefaultEntity(): string {
    return 'CustomGroup';
  }

  /**
   * Function that's only ever called by another deprecated function.
   *
   * @deprecated
   */
  public static function getRelationshipTypes() {
    // Note: We include inactive reltypes because we don't want to break custom-data
    // UI when a reltype is disabled.
    return CRM_Core_DAO::executeQuery('
      SELECT
        id,
        (CASE 1
           WHEN label_a_b is not null AND label_b_a is not null AND label_a_b != label_b_a
            THEN concat(label_a_b, \' / \', label_b_a)
           WHEN label_a_b is not null
            THEN label_a_b
           WHEN label_b_a is not null
            THEN label_b_a
           ELSE concat("RelType #", id)
        END) as label
      FROM civicrm_relationship_type
      '
    )->fetchMap('id', 'label');
  }

}
