<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Form to process actions on Price Sets.
 */
class CRM_Price_Form_Set extends CRM_Core_Form {

  use CRM_Core_Form_EntityFormTrait;

  /**
   * The set id saved to the session for an update.
   *
   * @var int
   */
  protected $_sid;

  /**
   * Get the entity id being edited.
   *
   * @return int|null
   */
  public function getEntityId() {
    return $this->_sid;
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'PriceSet';
  }

  /**
   * Fields for the entity to be assigned to the template.
   *
   * Fields may have keys
   *  - name (required to show in tpl from the array)
   *  - description (optional, will appear below the field)
   *  - not-auto-addable - this class will not attempt to add the field using addField.
   *    (this will be automatically set if the field does not have html in it's metadata
   *    or is not a core field on the form's entity).
   *  - help (option) add help to the field - e.g ['id' => 'id-source', 'file' => 'CRM/Contact/Form/Contact']]
   *  - template - use a field specific template to render this field
   * @var array
   */
  protected $entityFields = [];

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
    $this->entityFields = [
      'title' => [
        'required' => 'TRUE',
        'name' => 'title',
      ],
      'min_amount' => ['name' => 'min_amount'],
      'help_pre' => ['name' => 'help_pre'],
      'help_post' => ['name' => 'help_post'],
      'is_active' => ['name' => 'is_active'],
    ];
  }

  /**
   * Deletion message to be assigned to the form.
   *
   * @var string
   */
  protected $deleteMessage;

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {}

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    // current set id
    $this->_sid = $this->get('sid');

    // setting title for html page
    $title = ts('New Price Set');
    if ($this->getEntityId()) {
      $title = CRM_Price_BAO_PriceSet::getTitle($this->getEntityId());
    }
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $title = ts('Edit %1', array(1 => $title));
    }
    elseif ($this->_action & CRM_Core_Action::VIEW) {
      $title = ts('Preview %1', array(1 => $title));
    }
    CRM_Utils_System::setTitle($title);

    $url = CRM_Utils_System::url('civicrm/admin/price', 'reset=1');
    $breadCrumb = array(
      array(
        'title' => ts('Price Sets'),
        'url' => $url,
      ),
    );
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param array $options
   *   Additional user data.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $options) {
    $errors = array();
    $count = count(CRM_Utils_Array::value('extends', $fields));
    //price sets configured for membership
    if ($count && array_key_exists(CRM_Core_Component::getComponentID('CiviMember'), $fields['extends'])) {
      if ($count > 1) {
        $errors['extends'] = ts('If you plan on using this price set for membership signup and renewal, you can not also use it for Events or Contributions. However, a membership price set may include additional fields for non-membership options that require an additional fee (e.g. magazine subscription).');
      }
    }
    // Checks the given price set does not start with a digit
    if (strlen($fields['title']) && is_numeric($fields['title'][0])) {
      $errors['title'] = ts("Name cannot not start with a digit");
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->buildQuickEntityForm();
    $this->assign('sid', $this->getEntityId());

    $this->addRule('title', ts('Name already exists in Database.'),
      'objectExists', array('CRM_Price_DAO_PriceSet', $this->getEntityId(), 'title')
    );

    $priceSetUsedTables = $extends = array();
    if ($this->_action == CRM_Core_Action::UPDATE && $this->getEntityId()) {
      $priceSetUsedTables = CRM_Price_BAO_PriceSet::getUsedBy($this->getEntityId(), 'table');
    }

    $enabledComponents = CRM_Core_Component::getEnabledComponents();

    foreach ($enabledComponents as $name => $compObj) {
      switch ($name) {
        case 'CiviEvent':
          $option = $this->createElement('checkbox', $compObj->componentID, NULL, ts('Event'));
          if (!empty($priceSetUsedTables)) {
            foreach (array('civicrm_event', 'civicrm_participant') as $table) {
              if (in_array($table, $priceSetUsedTables)) {
                $option->freeze();
                break;
              }
            }
          }
          $extends[] = $option;
          break;

        case 'CiviContribute':
          $option = $this->createElement('checkbox', $compObj->componentID, NULL, ts('Contribution'));
          if (!empty($priceSetUsedTables)) {
            foreach (array('civicrm_contribution', 'civicrm_contribution_page') as $table) {
              if (in_array($table, $priceSetUsedTables)) {
                $option->freeze();
                break;
              }
            }
          }
          $extends[] = $option;
          break;

        case 'CiviMember':
          $option = $this->createElement('checkbox', $compObj->componentID, NULL, ts('Membership'));
          if (!empty($priceSetUsedTables)) {
            foreach (array('civicrm_membership', 'civicrm_contribution_page') as $table) {
              if (in_array($table, $priceSetUsedTables)) {
                $option->freeze();
                break;
              }
            }
          }
          $extends[] = $option;
          break;
      }
    }

    if (CRM_Utils_System::isNull($extends)) {
      $this->assign('extends', FALSE);
    }
    else {
      $this->assign('extends', TRUE);
    }

    $this->addGroup($extends, 'extends', ts('Used For'), '&nbsp;', TRUE);

    $this->addRule('extends', ts('%1 is a required field.', array(1 => ts('Used For'))), 'required');

    // financial type
    $financialType = CRM_Financial_BAO_FinancialType::getIncomeFinancialType();

    $this->add('select', 'financial_type_id',
      ts('Default Financial Type'),
      array('' => ts('- select -')) + $financialType, 'required'
    );

    $this->addFormRule(array('CRM_Price_Form_Set', 'formRule'));

    // views are implemented as frozen form
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode.
   *
   * The default values are retrieved from the database.
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = array('is_active' => TRUE);
    if ($this->getEntityId()) {
      $params = array('id' => $this->getEntityId());
      CRM_Price_BAO_PriceSet::retrieve($params, $defaults);
      $extends = explode(CRM_Core_DAO::VALUE_SEPARATOR, $defaults['extends']);
      unset($defaults['extends']);
      foreach ($extends as $compId) {
        $defaults['extends'][$compId] = 1;
      }
    }

    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues('Set');
    $nameLength = CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceSet', 'name');
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $params, FALSE);

    $compIds = array();
    $extends = CRM_Utils_Array::value('extends', $params);
    if (is_array($extends)) {
      foreach ($extends as $compId => $selected) {
        if ($selected) {
          $compIds[] = $compId;
        }
      }
    }
    $params['extends'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $compIds);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->getEntityId();
    }
    else {
      $params['name'] = CRM_Utils_String::titleToVar($params['title'],
        CRM_Utils_Array::value('maxlength', $nameLength));
    }

    $set = CRM_Price_BAO_PriceSet::create($params);
    if ($this->_action & CRM_Core_Action::UPDATE) {
      CRM_Core_Session::setStatus(ts('The Set \'%1\' has been saved.', array(1 => $set->title)), ts('Saved'), 'success');
    }
    else {
      // Jump directly to adding a field if popups are disabled
      $action = CRM_Core_Resources::singleton()->ajaxPopupsEnabled ? 'browse' : 'add';
      $url = CRM_Utils_System::url('civicrm/admin/price/field', array(
        'reset' => 1,
        'action' => $action,
        'sid' => $set->id,
        'new' => 1,
      ));
      CRM_Core_Session::setStatus(ts("Your Set '%1' has been added. You can add fields to this set now.",
        array(1 => $set->title)
      ), ts('Saved'), 'success');
      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
    }
  }

}
