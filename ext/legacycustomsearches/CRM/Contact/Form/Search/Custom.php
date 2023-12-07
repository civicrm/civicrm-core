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
class CRM_Contact_Form_Search_Custom extends CRM_Contact_Form_Search {

  /**
   * @var CRM_Contact_Form_Search_Custom_Base
   */
  protected $_customClass;

  public function preProcess() {
    // SearchFormName is deprecated & to be removed - the replacement is for the task to
    // call $this->form->getSearchFormValues()
    // A couple of extensions use it.
    $this->set('searchFormName', 'Custom');

    $this->set('context', 'custom');

    $csID = CRM_Utils_Request::retrieve('csid', 'Integer', $this);
    $ssID = CRM_Utils_Request::retrieve('ssID', 'Integer', $this);
    $gID = CRM_Utils_Request::retrieve('gid', 'Integer', $this);

    [$this->_customSearchID, $this->_customSearchClass, $formValues] = CRM_Contact_BAO_SearchCustom::details($csID, $ssID, $gID);

    if (!$this->_customSearchID) {
      CRM_Core_Error::statusbounce(ts('Could not get details for custom search.'));
    }

    // stash this as a hidden element so we can potentially go there if the session
    // is reset but this is available in the POST
    $this->addElement('hidden', 'csid', $csID);

    if (!empty($formValues)) {
      $this->_formValues = $formValues;
    }

    // set breadcrumb to return to Custom Search listings page
    $breadCrumb = [
      [
        'title' => ts('Custom Searches'),
        'url' => CRM_Utils_System::url('civicrm/contact/search/custom/list',
          'reset=1'
        ),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    // use the custom selector
    self::$_selectorName = 'CRM_Contact_Selector_Custom';

    $this->set('customSearchID', $this->_customSearchID);
    $this->set('customSearchClass', $this->_customSearchClass);

    parent::preProcess();

    // instantiate the new class
    $this->_customClass = new $this->_customSearchClass($this->_formValues);

    $this->addFormRule([$this->_customClass, 'formRule'], $this);

    // CRM-12747
    if (isset($this->_customClass->_permissionedComponent) &&
      !self::isPermissioned($this->_customClass->_permissionedComponent)
    ) {
      CRM_Utils_System::permissionDenied();
    }
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule([$this->_customClass, 'formRule']);
  }

  /**
   * Set the default values of various form elements.
   *
   * @return array
   *   reference to the array of default values
   */
  public function setDefaultValues() {
    if (method_exists($this->_customSearchClass, 'setDefaultValues')) {
      return $this->_customClass->setDefaultValues();
    }
    return $this->_formValues;
  }

  /**
   * Builds the list of tasks or actions that a searcher can perform on a result set.
   *
   * @return array
   */
  public function buildTaskList() {
    // call the parent method to populate $this->_taskList for the custom search
    // amtg = 'Add members to group'
    if ($this->_context !== 'amtg') {
      $taskParams['deletedContacts'] = FALSE;
      if ($this->_componentMode == CRM_Contact_BAO_Query::MODE_CONTACTS || $this->_componentMode == CRM_Contact_BAO_Query::MODE_CONTACTSRELATED) {
        $taskParams['deletedContacts'] = $this->_formValues['deleted_contacts'] ?? NULL;
      }
      $className = $this->_modeValue['taskClassName'];
      $taskParams['ssID'] = $this->_ssID ?? NULL;
      $this->_taskList += $className::permissionedTaskTitles(CRM_Core_Permission::getPermission(), $taskParams);
    }
    $reflectionClass = new ReflectionClass($this->_customClass);
    if ($reflectionClass->getMethod('buildTaskList')->class == get_class($this->_customClass)) {
      return $this->_customClass->buildTaskList($this);
    }
    return $this->_taskList;
  }

  public function buildQuickForm() {
    $this->_customClass->buildForm($this);

    parent::buildQuickForm();
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */

  /**
   * @return string
   */
  public function getTemplateFileName() {

    $ext = CRM_Extension_System::singleton()->getMapper();

    if ($ext->isExtensionClass(CRM_Utils_System::getClassName($this->_customClass))) {
      $fileName = $ext->getTemplatePath(CRM_Utils_System::getClassName($this->_customClass)) . '/' . $ext->getTemplateName(CRM_Utils_System::getClassName($this->_customClass));
    }
    else {
      $fileName = $this->_customClass->templateFile();
    }

    return $fileName ?: parent::getTemplateFileName();
  }

  public function postProcess() {
    $this->set('isAdvanced', '3');
    $this->set('isCustom', '1');

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);

      $this->_formValues['customSearchID'] = $this->_customSearchID;
      $this->_formValues['customSearchClass'] = $this->_customSearchClass;
    }

    //use the custom selector
    self::$_selectorName = 'CRM_Contact_Selector_Custom';

    parent::postProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Custom Search');
  }

  /**
   * @param $components
   *
   * @return bool
   */
  public function isPermissioned($components) {
    if (empty($components)) {
      return TRUE;
    }
    if (is_array($components)) {
      foreach ($components as $component) {
        if (!CRM_Core_Permission::access($component)) {
          return FALSE;
        }
      }
    }
    else {
      if (!CRM_Core_Permission::access($components)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
