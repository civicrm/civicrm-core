<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * This is our base form. It is part of the Form/Controller/StateMachine
 * trifecta. Each form is associated with a specific state in the state
 * machine. Each form can also operate in various modes
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

require_once 'HTML/QuickForm/Page.php';

/**
 * Class CRM_Core_Form
 */
class CRM_Core_Form extends HTML_QuickForm_Page {

  /**
   * The state object that this form belongs to
   * @var object
   */
  protected $_state;

  /**
   * The name of this form
   * @var string
   */
  protected $_name;

  /**
   * The title of this form
   * @var string
   */
  protected $_title = NULL;

  /**
   * The options passed into this form
   * @var mixed
   */
  protected $_options = NULL;

  /**
   * The mode of operation for this form
   * @var int
   */
  protected $_action;

  /**
   * The renderer used for this form
   *
   * @var object
   */
  protected $_renderer;

  /**
   * An array to hold a list of datefields on the form
   * so that they can be converted to ISO in a consistent manner
   *
   * @var array
   *
   * e.g on a form declare $_dateFields = array(
   *  'receive_date' => array('default' => 'now'),
   *  );
   *  then in postProcess call $this->convertDateFieldsToMySQL($formValues)
   *  to have the time field re-incorporated into the field & 'now' set if
   *  no value has been passed in
   */
  protected $_dateFields = array();

  /**
   * Cache the smarty template for efficiency reasons
   *
   * @var CRM_Core_Smarty
   */
  static protected $_template;

  /**
   *  Indicate if this form should warn users of unsaved changes
   */
  protected $unsavedChangesWarn;

  /**
   * What to return to the client if in ajax mode (snippet=json)
   *
   * @var array
   */
  public $ajaxResponse = array();

  /**
   * Url path used to reach this page
   *
   * @var array
   */
  public $urlPath = array();

  /**
   * @var CRM_Core_Controller
   */
  public $controller;

  /**
   * Constants for attributes for various form elements
   * attempt to standardize on the number of variations that we
   * use of the below form elements
   *
   * @var const string
   */
  const ATTR_SPACING = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

  /**
   * All checkboxes are defined with a common prefix. This allows us to
   * have the same javascript to check / clear all the checkboxes etc
   * If u have multiple groups of checkboxes, you will need to give them different
   * ids to avoid potential name collision
   *
   * @var string|int
   */
  const CB_PREFIX = 'mark_x_', CB_PREFIY = 'mark_y_', CB_PREFIZ = 'mark_z_', CB_PREFIX_LEN = 7;

  /**
   * @internal to keep track of chain-select fields
   * @var array
   */
  private $_chainSelectFields = array();

  /**
   * Constructor for the basic form page.
   *
   * We should not use QuickForm directly. This class provides a lot
   * of default convenient functions, rules and buttons
   *
   * @param object $state
   *   State associated with this form.
   * @param \const|\enum|int $action The mode the form is operating in (None/Create/View/Update/Delete)
   * @param string $method
   *   The type of http method used (GET/POST).
   * @param string $name
   *   The name of the form if different from class name.
   *
   * @return \CRM_Core_Form
   */
  public function __construct(
    $state = NULL,
    $action = CRM_Core_Action::NONE,
    $method = 'post',
    $name = NULL
  ) {

    if ($name) {
      $this->_name = $name;
    }
    else {
      // CRM-15153 - FIXME this name translates to a DOM id and is not always unique!
      $this->_name = CRM_Utils_String::getClassName(CRM_Utils_System::getClassName($this));
    }

    $this->HTML_QuickForm_Page($this->_name, $method);

    $this->_state =& $state;
    if ($this->_state) {
      $this->_state->setName($this->_name);
    }
    $this->_action = (int) $action;

    $this->registerRules();

    // let the constructor initialize this, should happen only once
    if (!isset(self::$_template)) {
      self::$_template = CRM_Core_Smarty::singleton();
    }
    // Workaround for CRM-15153 - give each form a reasonably unique css class
    $this->addClass(CRM_Utils_System::getClassName($this));

    $this->assign('snippet', CRM_Utils_Array::value('snippet', $_GET));
  }

  public static function generateID() {
  }

  /**
   * Add one or more css classes to the form
   * @param string $className
   */
  public function addClass($className) {
    $classes = $this->getAttribute('class');
    $this->setAttribute('class', ($classes ? "$classes " : '') . $className);
  }

  /**
   * Register all the standard rules that most forms potentially use.
   *
   * @return void
   */
  public function registerRules() {
    static $rules = array(
      'title',
      'longTitle',
      'variable',
      'qfVariable',
      'phone',
      'integer',
      'query',
      'url',
      'wikiURL',
      'domain',
      'numberOfDigit',
      'date',
      'currentDate',
      'asciiFile',
      'htmlFile',
      'utf8File',
      'objectExists',
      'optionExists',
      'postalCode',
      'money',
      'positiveInteger',
      'xssString',
      'fileExists',
      'autocomplete',
      'validContact',
    );

    foreach ($rules as $rule) {
      $this->registerRule($rule, 'callback', $rule, 'CRM_Utils_Rule');
    }
  }

  /**
   * Simple easy to use wrapper around addElement. Deal with
   * simple validation rules
   *
   * @param string $type
   * @param string $name
   * @param string $label
   * @param string|array $attributes (options for select elements)
   * @param bool $required
   * @param array $extra
   *   (attributes for select elements).
   *
   * @return HTML_QuickForm_Element could be an error object
   */
  public function &add(
    $type, $name, $label = '',
    $attributes = '', $required = FALSE, $extra = NULL
  ) {
    if ($type == 'select' && is_array($extra)) {
      // Normalize this property
      if (!empty($extra['multiple'])) {
        $extra['multiple'] = 'multiple';
      }
      else {
        unset($extra['multiple']);
      }
      // Add placeholder option for select
      if (isset($extra['placeholder'])) {
        if ($extra['placeholder'] === TRUE) {
          $extra['placeholder'] = $required ? ts('- select -') : ts('- none -');
        }
        if (($extra['placeholder'] || $extra['placeholder'] === '') && empty($extra['multiple']) && is_array($attributes) && !isset($attributes[''])) {
          $attributes = array('' => $extra['placeholder']) + $attributes;
        }
      }
    }
    $element = $this->addElement($type, $name, $label, $attributes, $extra);
    if (HTML_QuickForm::isError($element)) {
      CRM_Core_Error::fatal(HTML_QuickForm::errorMessage($element));
    }

    if ($required) {
      if ($type == 'file') {
        $error = $this->addRule($name, ts('%1 is a required field.', array(1 => $label)), 'uploadedfile');
      }
      else {
        $error = $this->addRule($name, ts('%1 is a required field.', array(1 => $label)), 'required');
      }
      if (HTML_QuickForm::isError($error)) {
        CRM_Core_Error::fatal(HTML_QuickForm::errorMessage($element));
      }
    }

    return $element;
  }

  /**
   * called before buildForm. Any pre-processing that
   * needs to be done for buildForm should be done here
   *
   * This is a virtual function and should be redefined if needed
   *
   *
   * @return void
   */
  public function preProcess() {
  }

  /**
   * called after the form is validated. Any
   * processing of form state etc should be done in this function.
   * Typically all processing associated with a form should be done
   * here and relevant state should be stored in the session
   *
   * This is a virtual function and should be redefined if needed
   *
   *
   * @return void
   */
  public function postProcess() {
  }

  /**
   * just a wrapper, so that we can call all the hook functions
   * @param bool $allowAjax
   *   FIXME: This feels kind of hackish, ideally we would take the json-related code from this function.
   *                          and bury it deeper down in the controller
   */
  public function mainProcess($allowAjax = TRUE) {
    $this->postProcess();
    $this->postProcessHook();

    // Respond with JSON if in AJAX context (also support legacy value '6')
    if ($allowAjax && !empty($_REQUEST['snippet']) && in_array($_REQUEST['snippet'], array(
          CRM_Core_Smarty::PRINT_JSON,
          6,
        ))
    ) {
      $this->ajaxResponse['buttonName'] = str_replace('_qf_' . $this->getAttribute('id') . '_', '', $this->controller->getButtonName());
      $this->ajaxResponse['action'] = $this->_action;
      if (isset($this->_id) || isset($this->id)) {
        $this->ajaxResponse['id'] = isset($this->id) ? $this->id : $this->_id;
      }
      CRM_Core_Page_AJAX::returnJsonResponse($this->ajaxResponse);
    }
  }

  /**
   * The postProcess hook is typically called by the framework.
   * However in a few cases, the form exits or redirects early in which
   * case it needs to call this function so other modules can do the needful
   * Calling this function directly should be avoided if possible. In general a
   * better way is to do setUserContext so the framework does the redirect
   */
  public function postProcessHook() {
    CRM_Utils_Hook::postProcess(get_class($this), $this);
  }

  /**
   * This virtual function is used to build the form. It replaces the
   * buildForm associated with QuickForm_Page. This allows us to put
   * preProcess in front of the actual form building routine
   *
   *
   * @return void
   */
  public function buildQuickForm() {
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array|NULL
   *   reference to the array of default values
   */
  public function setDefaultValues() {
    return NULL;
  }

  /**
   * This is a virtual function that adds group and global rules to
   * the form. Keeping it distinct from the form to keep code small
   * and localized in the form building code
   *
   *
   * @return void
   */
  public function addRules() {
  }

  /**
   * Performs the server side validation.
   * @since     1.0
   * @return bool
   *   true if no error found
   * @throws    HTML_QuickForm_Error
   */
  public function validate() {
    $error = parent::validate();

    $this->validateChainSelectFields();

    $hookErrors = CRM_Utils_Hook::validate(
      get_class($this),
      $this->_submitValues,
      $this->_submitFiles,
      $this
    );

    if (!is_array($hookErrors)) {
      $hookErrors = array();
    }

    CRM_Utils_Hook::validateForm(
      get_class($this),
      $this->_submitValues,
      $this->_submitFiles,
      $this,
      $hookErrors
    );

    if (!empty($hookErrors)) {
      $this->_errors += $hookErrors;
    }

    return (0 == count($this->_errors));
  }

  /**
   * Core function that builds the form. We redefine this function
   * here and expect all CRM forms to build their form in the function
   * buildQuickForm.
   */
  public function buildForm() {
    $this->_formBuilt = TRUE;

    $this->preProcess();

    CRM_Utils_Hook::preProcess(get_class($this), $this);

    $this->assign('translatePermission', CRM_Core_Permission::check('translate CiviCRM'));

    if (
      $this->controller->_key &&
      $this->controller->_generateQFKey
    ) {
      $this->addElement('hidden', 'qfKey', $this->controller->_key);
      $this->assign('qfKey', $this->controller->_key);

    }

    // _generateQFKey suppresses the qfKey generation on form snippets that
    // are part of other forms, hence we use that to avoid adding entryURL
    if ($this->controller->_generateQFKey && $this->controller->_entryURL) {
      $this->addElement('hidden', 'entryURL', $this->controller->_entryURL);
    }

    $this->buildQuickForm();

    $defaults = $this->setDefaultValues();
    unset($defaults['qfKey']);

    if (!empty($defaults)) {
      $this->setDefaults($defaults);
    }

    // call the form hook
    // also call the hook function so any modules can set thier own custom defaults
    // the user can do both the form and set default values with this hook
    CRM_Utils_Hook::buildForm(get_class($this), $this);

    $this->addRules();

    //Set html data-attribute to enable warning user of unsaved changes
    if ($this->unsavedChangesWarn === TRUE
      || (!isset($this->unsavedChangesWarn)
        && ($this->_action & CRM_Core_Action::ADD || $this->_action & CRM_Core_Action::UPDATE)
      )
    ) {
      $this->setAttribute('data-warn-changes', 'true');
    }
  }

  /**
   * Add default Next / Back buttons
   *
   * @param array $params
   *   Array of associative arrays in the order in which the buttons should be
   *   displayed. The associate array has 3 fields: 'type', 'name' and 'isDefault'
   *   The base form class will define a bunch of static arrays for commonly used
   *   formats.
   *
   * @return void
   */
  public function addButtons($params) {
    $prevnext = $spacing = array();
    foreach ($params as $button) {
      $attrs = array('class' => 'crm-form-submit') + (array) CRM_Utils_Array::value('js', $button);

      if (!empty($button['class'])) {
        $attrs['class'] .= ' ' . $button['class'];
      }

      if (!empty($button['isDefault'])) {
        $attrs['class'] .= ' default';
      }

      if (in_array($button['type'], array('upload', 'next', 'submit', 'done', 'process', 'refresh'))) {
        $attrs['class'] .= ' validate';
        $defaultIcon = 'check';
      }
      else {
        $attrs['class'] .= ' cancel';
        $defaultIcon = $button['type'] == 'back' ? 'triangle-1-w' : 'close';
      }

      if ($button['type'] === 'reset') {
        $prevnext[] = $this->createElement($button['type'], 'reset', $button['name'], $attrs);
      }
      else {
        if (!empty($button['subName'])) {
          if ($button['subName'] == 'new') {
            $defaultIcon = 'plus';
          }
          if ($button['subName'] == 'done') {
            $defaultIcon = 'circle-check';
          }
          if ($button['subName'] == 'next') {
            $defaultIcon = 'circle-triangle-e';
          }
        }

        if (in_array($button['type'], array('next', 'upload', 'done')) && $button['name'] === ts('Save')) {
          $attrs['accesskey'] = 'S';
        }
        $icon = CRM_Utils_Array::value('icon', $button, $defaultIcon);
        if ($icon) {
          $attrs['crm-icon'] = $icon;
        }
        $buttonName = $this->getButtonName($button['type'], CRM_Utils_Array::value('subName', $button));
        $prevnext[] = $this->createElement('submit', $buttonName, $button['name'], $attrs);
      }
      if (!empty($button['isDefault'])) {
        $this->setDefaultAction($button['type']);
      }

      // if button type is upload, set the enctype
      if ($button['type'] == 'upload') {
        $this->updateAttributes(array('enctype' => 'multipart/form-data'));
        $this->setMaxFileSize();
      }

      // hack - addGroup uses an array to express variable spacing, read from the last element
      $spacing[] = CRM_Utils_Array::value('spacing', $button, self::ATTR_SPACING);
    }
    $this->addGroup($prevnext, 'buttons', '', $spacing, FALSE);
  }

  /**
   * Getter function for Name.
   *
   * @return string
   */
  public function getName() {
    return $this->_name;
  }

  /**
   * Getter function for State.
   *
   * @return object
   */
  public function &getState() {
    return $this->_state;
  }

  /**
   * Getter function for StateType.
   *
   * @return int
   */
  public function getStateType() {
    return $this->_state->getType();
  }

  /**
   * Getter function for title. Should be over-ridden by derived class
   *
   * @return string
   */
  public function getTitle() {
    return $this->_title ? $this->_title : ts('ERROR: Title is not Set');
  }

  /**
   * Setter function for title.
   *
   * @param string $title
   *   The title of the form.
   *
   * @return void
   */
  public function setTitle($title) {
    $this->_title = $title;
  }

  /**
   * Setter function for options.
   *
   * @param mixed $options
   *
   * @return void
   */
  public function setOptions($options) {
    $this->_options = $options;
  }

  /**
   * Getter function for link.
   *
   * @return string
   */
  public function getLink() {
    $config = CRM_Core_Config::singleton();
    return CRM_Utils_System::url($_GET[$config->userFrameworkURLVar],
      '_qf_' . $this->_name . '_display=true'
    );
  }

  /**
   * Boolean function to determine if this is a one form page.
   *
   * @return bool
   */
  public function isSimpleForm() {
    return $this->_state->getType() & (CRM_Core_State::START | CRM_Core_State::FINISH);
  }

  /**
   * Getter function for Form Action.
   *
   * @return string
   */
  public function getFormAction() {
    return $this->_attributes['action'];
  }

  /**
   * Setter function for Form Action.
   *
   * @param string $action
   *
   * @return void
   */
  public function setFormAction($action) {
    $this->_attributes['action'] = $action;
  }

  /**
   * Render form and return contents.
   *
   * @return string
   */
  public function toSmarty() {
    $this->preProcessChainSelectFields();
    $renderer = $this->getRenderer();
    $this->accept($renderer);
    $content = $renderer->toArray();
    $content['formName'] = $this->getName();
    // CRM-15153
    $content['formClass'] = CRM_Utils_System::getClassName($this);
    return $content;
  }

  /**
   * Getter function for renderer. If renderer is not set
   * create one and initialize it
   *
   * @return object
   */
  public function &getRenderer() {
    if (!isset($this->_renderer)) {
      $this->_renderer = CRM_Core_Form_Renderer::singleton();
    }
    return $this->_renderer;
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    $ext = CRM_Extension_System::singleton()->getMapper();
    if ($ext->isExtensionClass(CRM_Utils_System::getClassName($this))) {
      $filename = $ext->getTemplateName(CRM_Utils_System::getClassName($this));
      $tplname = $ext->getTemplatePath(CRM_Utils_System::getClassName($this)) . DIRECTORY_SEPARATOR . $filename;
    }
    else {
      $tplname = strtr(
        CRM_Utils_System::getClassName($this),
        array(
          '_' => DIRECTORY_SEPARATOR,
          '\\' => DIRECTORY_SEPARATOR,
        )
      ) . '.tpl';
    }
    return $tplname;
  }

  /**
   * A wrapper for getTemplateFileName that includes calling the hook to
   * prevent us from having to copy & paste the logic of calling the hook
   */
  public function getHookedTemplateFileName() {
    $pageTemplateFile = $this->getTemplateFileName();
    CRM_Utils_Hook::alterTemplateFile(get_class($this), $this, 'page', $pageTemplateFile);
    return $pageTemplateFile;
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we dont override
   *
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    return NULL;
  }

  /**
   * Error reporting mechanism.
   *
   * @param string $message
   *   Error Message.
   * @param int $code
   *   Error Code.
   * @param CRM_Core_DAO $dao
   *   A data access object on which we perform a rollback if non - empty.
   *
   * @return void
   */
  public function error($message, $code = NULL, $dao = NULL) {
    if ($dao) {
      $dao->query('ROLLBACK');
    }

    $error = CRM_Core_Error::singleton();

    $error->push($code, $message);
  }

  /**
   * Store the variable with the value in the form scope.
   *
   * @param string $name
   *   Name of the variable.
   * @param mixed $value
   *   Value of the variable.
   *
   * @return void
   */
  public function set($name, $value) {
    $this->controller->set($name, $value);
  }

  /**
   * Get the variable from the form scope.
   *
   * @param string $name
   *   Name of the variable
   *
   * @return mixed
   */
  public function get($name) {
    return $this->controller->get($name);
  }

  /**
   * Getter for action.
   *
   * @return int
   */
  public function getAction() {
    return $this->_action;
  }

  /**
   * Setter for action.
   *
   * @param int $action
   *   The mode we want to set the form.
   *
   * @return void
   */
  public function setAction($action) {
    $this->_action = $action;
  }

  /**
   * Assign value to name in template.
   *
   * @param string $var
   *   Name of variable.
   * @param mixed $value
   *   Value of variable.
   *
   * @return void
   */
  public function assign($var, $value = NULL) {
    self::$_template->assign($var, $value);
  }

  /**
   * Assign value to name in template by reference.
   *
   * @param string $var
   *   Name of variable.
   * @param mixed $value
   *   Value of varaible.
   *
   * @return void
   */
  public function assign_by_ref($var, &$value) {
    self::$_template->assign_by_ref($var, $value);
  }

  /**
   * Appends values to template variables.
   *
   * @param array|string $tpl_var the template variable name(s)
   * @param mixed $value
   *   The value to append.
   * @param bool $merge
   */
  public function append($tpl_var, $value = NULL, $merge = FALSE) {
    self::$_template->append($tpl_var, $value, $merge);
  }

  /**
   * Returns an array containing template variables.
   *
   * @param string $name
   *
   * @return array
   */
  public function get_template_vars($name = NULL) {
    return self::$_template->get_template_vars($name);
  }

  /**
   * @param string $name
   * @param $title
   * @param $values
   * @param array $attributes
   * @param null $separator
   * @param bool $required
   *
   * @return HTML_QuickForm_group
   */
  public function &addRadio($name, $title, $values, $attributes = array(), $separator = NULL, $required = FALSE) {
    $options = array();
    $attributes = $attributes ? $attributes : array();
    $allowClear = !empty($attributes['allowClear']);
    unset($attributes['allowClear']);
    $attributes['id_suffix'] = $name;
    foreach ($values as $key => $var) {
      $options[] = $this->createElement('radio', NULL, NULL, $var, $key, $attributes);
    }
    $group = $this->addGroup($options, $name, $title, $separator);
    if ($required) {
      $this->addRule($name, ts('%1 is a required field.', array(1 => $title)), 'required');
    }
    if ($allowClear) {
      $group->setAttribute('allowClear', TRUE);
    }
    return $group;
  }

  /**
   * @param int $id
   * @param $title
   * @param bool $allowClear
   * @param null $required
   * @param array $attributes
   */
  public function addYesNo($id, $title, $allowClear = FALSE, $required = NULL, $attributes = array()) {
    $attributes += array('id_suffix' => $id);
    $choice = array();
    $choice[] = $this->createElement('radio', NULL, '11', ts('Yes'), '1', $attributes);
    $choice[] = $this->createElement('radio', NULL, '11', ts('No'), '0', $attributes);

    $group = $this->addGroup($choice, $id, $title);
    if ($allowClear) {
      $group->setAttribute('allowClear', TRUE);
    }
    if ($required) {
      $this->addRule($id, ts('%1 is a required field.', array(1 => $title)), 'required');
    }
  }

  /**
   * @param int $id
   * @param $title
   * @param $values
   * @param null $other
   * @param null $attributes
   * @param null $required
   * @param null $javascriptMethod
   * @param string $separator
   * @param bool $flipValues
   */
  public function addCheckBox(
    $id, $title, $values, $other = NULL,
    $attributes = NULL, $required = NULL,
    $javascriptMethod = NULL,
    $separator = '<br />', $flipValues = FALSE
  ) {
    $options = array();

    if ($javascriptMethod) {
      foreach ($values as $key => $var) {
        if (!$flipValues) {
          $options[] = $this->createElement('checkbox', $var, NULL, $key, $javascriptMethod);
        }
        else {
          $options[] = $this->createElement('checkbox', $key, NULL, $var, $javascriptMethod);
        }
      }
    }
    else {
      foreach ($values as $key => $var) {
        if (!$flipValues) {
          $options[] = $this->createElement('checkbox', $var, NULL, $key);
        }
        else {
          $options[] = $this->createElement('checkbox', $key, NULL, $var);
        }
      }
    }

    $this->addGroup($options, $id, $title, $separator);

    if ($other) {
      $this->addElement('text', $id . '_other', ts('Other'), $attributes[$id . '_other']);
    }

    if ($required) {
      $this->addRule($id,
        ts('%1 is a required field.', array(1 => $title)),
        'required'
      );
    }
  }

  public function resetValues() {
    $data = $this->controller->container();
    $data['values'][$this->_name] = array();
  }

  /**
   * Simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   *   Button type for the form after processing.
   * @param string $backType
   * @param bool|string $submitOnce If true, add javascript to next button submit which prevents it from being clicked more than once
   *
   * @return void
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $buttons = array();
    if ($backType != NULL) {
      $buttons[] = array(
        'type' => $backType,
        'name' => ts('Previous'),
      );
    }
    if ($nextType != NULL) {
      $nextButton = array(
        'type' => $nextType,
        'name' => $title,
        'isDefault' => TRUE,
      );
      if ($submitOnce) {
        $nextButton['js'] = array('onclick' => "return submitOnce(this,'{$this->_name}','" . ts('Processing') . "');");
      }
      $buttons[] = $nextButton;
    }
    $this->addButtons($buttons);
  }

  /**
   * @param string $name
   * @param string $from
   * @param string $to
   * @param string $label
   * @param string $dateFormat
   * @param bool $required
   * @param bool $displayTime
   */
  public function addDateRange($name, $from = '_from', $to = '_to', $label = 'From:', $dateFormat = 'searchDate', $required = FALSE, $displayTime = FALSE) {
    if ($displayTime) {
      $this->addDateTime($name . $from, $label, $required, array('formatType' => $dateFormat));
      $this->addDateTime($name . $to, ts('To:'), $required, array('formatType' => $dateFormat));
    }
    else {
      $this->addDate($name . $from, $label, $required, array('formatType' => $dateFormat));
      $this->addDate($name . $to, ts('To:'), $required, array('formatType' => $dateFormat));
    }
  }

  /**
   * Adds a select based on field metadata.
   * TODO: This could be even more generic and widget type (select in this case) could also be read from metadata
   * Perhaps a method like $form->bind($name) which would look up all metadata for named field
   * @param $name
   *   Field name to go on the form.
   * @param array $props
   *   Mix of html attributes and special properties, namely.
   *   - entity (api entity name, can usually be inferred automatically from the form class)
   *   - field (field name - only needed if different from name used on the form)
   *   - option_url - path to edit this option list - usually retrieved automatically - set to NULL to disable link
   *   - placeholder - set to NULL to disable
   *   - multiple - bool
   *   - context - @see CRM_Core_DAO::buildOptionsContext
   * @param bool $required
   * @throws CRM_Core_Exception
   * @return HTML_QuickForm_Element
   */
  public function addSelect($name, $props = array(), $required = FALSE) {
    if (!isset($props['entity'])) {
      $props['entity'] = CRM_Utils_Api::getEntityName($this);
    }
    if (!isset($props['field'])) {
      $props['field'] = strrpos($name, '[') ? rtrim(substr($name, 1 + strrpos($name, '[')), ']') : $name;
    }
    // Fetch options from the api unless passed explicitly
    if (isset($props['options'])) {
      $options = $props['options'];
    }
    else {
      $info = civicrm_api3($props['entity'], 'getoptions', $props);
      $options = $info['values'];
    }
    if (!array_key_exists('placeholder', $props)) {
      $props['placeholder'] = $required ? ts('- select -') : CRM_Utils_Array::value('context', $props) == 'search' ? ts('- any -') : ts('- none -');
    }
    // Handle custom field
    if (strpos($name, 'custom_') === 0 && is_numeric($name[7])) {
      list(, $id) = explode('_', $name);
      $label = isset($props['label']) ? $props['label'] : CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', 'label', $id);
      $gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', 'option_group_id', $id);
      if (CRM_Utils_Array::value('context', $props) != 'search') {
        $props['data-option-edit-path'] = array_key_exists('option_url', $props) ? $props['option_url'] : 'civicrm/admin/options/' . CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $gid);
      }
    }
    // Core field
    else {
      $info = civicrm_api3($props['entity'], 'getfields');
      foreach ($info['values'] as $uniqueName => $fieldSpec) {
        if (
          $uniqueName === $props['field'] ||
          CRM_Utils_Array::value('name', $fieldSpec) === $props['field'] ||
          in_array($props['field'], CRM_Utils_Array::value('api.aliases', $fieldSpec, array()))
        ) {
          break;
        }
      }
      $label = isset($props['label']) ? $props['label'] : $fieldSpec['title'];
      if (CRM_Utils_Array::value('context', $props) != 'search') {
        $props['data-option-edit-path'] = array_key_exists('option_url', $props) ? $props['option_url'] : $props['data-option-edit-path'] = CRM_Core_PseudoConstant::getOptionEditUrl($fieldSpec);
      }
    }
    $props['class'] = (isset($props['class']) ? $props['class'] . ' ' : '') . "crm-select2";
    $props['data-api-entity'] = $props['entity'];
    $props['data-api-field'] = $props['field'];
    CRM_Utils_Array::remove($props, 'label', 'entity', 'field', 'option_url', 'options', 'context');
    return $this->add('select', $name, $label, $options, $required, $props);
  }

  /**
   * Add a widget for selecting/editing/creating/copying a profile form
   *
   * @param string $name
   *   HTML form-element name.
   * @param string $label
   *   Printable label.
   * @param string $allowCoreTypes
   *   Only present a UFGroup if its group_type includes a subset of $allowCoreTypes; e.g. 'Individual', 'Activity'.
   * @param string $allowSubTypes
   *   Only present a UFGroup if its group_type is compatible with $allowSubypes.
   * @param array $entities
   * @param bool $default
   *   //CRM-15427.
   */
  public function addProfileSelector($name, $label, $allowCoreTypes, $allowSubTypes, $entities, $default = FALSE) {
    // Output widget
    // FIXME: Instead of adhoc serialization, use a single json_encode()
    CRM_UF_Page_ProfileEditor::registerProfileScripts();
    CRM_UF_Page_ProfileEditor::registerSchemas(CRM_Utils_Array::collect('entity_type', $entities));
    $this->add('text', $name, $label, array(
      'class' => 'crm-profile-selector',
      // Note: client treats ';;' as equivalent to \0, and ';;' works better in HTML
      'data-group-type' => CRM_Core_BAO_UFGroup::encodeGroupType($allowCoreTypes, $allowSubTypes, ';;'),
      'data-entities' => json_encode($entities),
      //CRM-15427
      'data-default' => $default,
    ));
  }

  /**
   * @param string $name
   * @param $label
   * @param $attributes
   * @param bool $forceTextarea
   */
  public function addWysiwyg($name, $label, $attributes, $forceTextarea = FALSE) {
    // 1. Get configuration option for editor (tinymce, ckeditor, pure textarea)
    // 2. Based on the option, initialise proper editor
    $editorID = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'editor_id'
    );
    $editor = strtolower(CRM_Utils_Array::value($editorID,
      CRM_Core_OptionGroup::values('wysiwyg_editor')
    ));
    if (!$editor || $forceTextarea) {
      $editor = 'textarea';
    }
    if ($editor == 'joomla default editor') {
      $editor = 'joomlaeditor';
    }

    if ($editor == 'drupal default editor') {
      $editor = 'drupalwysiwyg';
    }

    //lets add the editor as a attribute
    $attributes['editor'] = $editor;

    $this->addElement($editor, $name, $label, $attributes);
    $this->assign('editor', $editor);

    // include wysiwyg editor js files
    // FIXME: This code does not make any sense
    $includeWysiwygEditor = FALSE;
    $includeWysiwygEditor = $this->get('includeWysiwygEditor');
    if (!$includeWysiwygEditor) {
      $includeWysiwygEditor = TRUE;
      $this->set('includeWysiwygEditor', $includeWysiwygEditor);
    }

    $this->assign('includeWysiwygEditor', $includeWysiwygEditor);
  }

  /**
   * @param int $id
   * @param $title
   * @param null $required
   * @param null $extra
   */
  public function addCountry($id, $title, $required = NULL, $extra = NULL) {
    $this->addElement('select', $id, $title,
      array(
        '' => ts('- select -'),
      ) + CRM_Core_PseudoConstant::country(), $extra
    );
    if ($required) {
      $this->addRule($id, ts('Please select %1', array(1 => $title)), 'required');
    }
  }

  /**
   * @param string $name
   * @param $label
   * @param $options
   * @param $attributes
   * @param null $required
   * @param null $javascriptMethod
   */
  public function addSelectOther($name, $label, $options, $attributes, $required = NULL, $javascriptMethod = NULL) {

    $this->addElement('select', $name . '_id', $label, $options, $javascriptMethod);

    if ($required) {
      $this->addRule($name . '_id', ts('Please select %1', array(1 => $label)), 'required');
    }
  }

  /**
   * @return null
   */
  public function getRootTitle() {
    return NULL;
  }

  /**
   * @return string
   */
  public function getCompleteTitle() {
    return $this->getRootTitle() . $this->getTitle();
  }

  /**
   * @return CRM_Core_Smarty
   */
  public static function &getTemplate() {
    return self::$_template;
  }

  /**
   * @param $elementName
   */
  public function addUploadElement($elementName) {
    $uploadNames = $this->get('uploadNames');
    if (!$uploadNames) {
      $uploadNames = array();
    }
    if (is_array($elementName)) {
      foreach ($elementName as $name) {
        if (!in_array($name, $uploadNames)) {
          $uploadNames[] = $name;
        }
      }
    }
    else {
      if (!in_array($elementName, $uploadNames)) {
        $uploadNames[] = $elementName;
      }
    }
    $this->set('uploadNames', $uploadNames);

    $config = CRM_Core_Config::singleton();
    if (!empty($uploadNames)) {
      $this->controller->addUploadAction($config->customFileUploadDir, $uploadNames);
    }
  }

  /**
   * @return string
   */
  public function buttonType() {
    $uploadNames = $this->get('uploadNames');
    $buttonType = (is_array($uploadNames) && !empty($uploadNames)) ? 'upload' : 'next';
    $this->assign('buttonType', $buttonType);
    return $buttonType;
  }

  /**
   * @param $name
   *
   * @return null
   */
  public function getVar($name) {
    return isset($this->$name) ? $this->$name : NULL;
  }

  /**
   * @param $name
   * @param $value
   */
  public function setVar($name, $value) {
    $this->$name = $value;
  }

  /**
   * Add date.
   *
   * @code
   * // if you need time
   * $attributes = array(
   *   'addTime' => true,
   *   'formatType' => 'relative' or 'birth' etc check advanced date settings
   * );
   * @endcode
   *
   * @param string $name
   *   Name of the element.
   * @param string $label
   *   Label of the element.
   * @param bool $required
   *   True if required.
   * @param array $attributes
   *   Key / value pair.
   */
  public function addDate($name, $label, $required = FALSE, $attributes = NULL) {
    if (!empty($attributes['formatType'])) {
      // get actual format
      $params = array('name' => $attributes['formatType']);
      $values = array();

      // cache date information
      static $dateFormat;
      $key = "dateFormat_" . str_replace(' ', '_', $attributes['formatType']);
      if (empty($dateFormat[$key])) {
        CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, $values);
        $dateFormat[$key] = $values;
      }
      else {
        $values = $dateFormat[$key];
      }

      if ($values['date_format']) {
        $attributes['format'] = $values['date_format'];
      }

      if (!empty($values['time_format'])) {
        $attributes['timeFormat'] = $values['time_format'];
      }
      $attributes['startOffset'] = $values['start'];
      $attributes['endOffset'] = $values['end'];
    }

    $config = CRM_Core_Config::singleton();
    if (empty($attributes['format'])) {
      $attributes['format'] = $config->dateInputFormat;
    }

    if (!isset($attributes['startOffset'])) {
      $attributes['startOffset'] = 10;
    }

    if (!isset($attributes['endOffset'])) {
      $attributes['endOffset'] = 10;
    }

    $this->add('text', $name, $label, $attributes);

    if (!empty($attributes['addTime']) || !empty($attributes['timeFormat'])) {

      if (!isset($attributes['timeFormat'])) {
        $timeFormat = $config->timeInputFormat;
      }
      else {
        $timeFormat = $attributes['timeFormat'];
      }

      // 1 - 12 hours and 2 - 24 hours, but for jquery widget it is 0 and 1 respectively
      if ($timeFormat) {
        $show24Hours = TRUE;
        if ($timeFormat == 1) {
          $show24Hours = FALSE;
        }

        //CRM-6664 -we are having time element name
        //in either flat string or an array format.
        $elementName = $name . '_time';
        if (substr($name, -1) == ']') {
          $elementName = substr($name, 0, strlen($name) - 1) . '_time]';
        }

        $this->add('text', $elementName, ts('Time'), array('timeFormat' => $show24Hours));
      }
    }

    if ($required) {
      $this->addRule($name, ts('Please select %1', array(1 => $label)), 'required');
      if (!empty($attributes['addTime']) && !empty($attributes['addTimeRequired'])) {
        $this->addRule($elementName, ts('Please enter a time.'), 'required');
      }
    }
  }

  /**
   *  Function that will add date and time.
   */
  public function addDateTime($name, $label, $required = FALSE, $attributes = NULL) {
    $addTime = array('addTime' => TRUE);
    if (is_array($attributes)) {
      $attributes = array_merge($attributes, $addTime);
    }
    else {
      $attributes = $addTime;
    }

    $this->addDate($name, $label, $required, $attributes);
  }

  /**
   * Add a currency and money element to the form.
   */
  public function addMoney(
    $name,
    $label,
    $required = FALSE,
    $attributes = NULL,
    $addCurrency = TRUE,
    $currencyName = 'currency',
    $defaultCurrency = NULL,
    $freezeCurrency = FALSE
  ) {
    $element = $this->add('text', $name, $label, $attributes, $required);
    $this->addRule($name, ts('Please enter a valid amount.'), 'money');

    if ($addCurrency) {
      $ele = $this->addCurrency($currencyName, NULL, TRUE, $defaultCurrency, $freezeCurrency);
    }

    return $element;
  }

  /**
   * Add currency element to the form.
   */
  public function addCurrency(
    $name = 'currency',
    $label = NULL,
    $required = TRUE,
    $defaultCurrency = NULL,
    $freezeCurrency = FALSE
  ) {
    $currencies = CRM_Core_OptionGroup::values('currencies_enabled');
    $options = array('class' => 'crm-select2 eight');
    if (!$required) {
      $currencies = array('' => '') + $currencies;
      $options['placeholder'] = ts('- none -');
    }
    $ele = $this->add('select', $name, $label, $currencies, $required, $options);
    if ($freezeCurrency) {
      $ele->freeze();
    }
    if (!$defaultCurrency) {
      $config = CRM_Core_Config::singleton();
      $defaultCurrency = $config->defaultCurrency;
    }
    $this->setDefaults(array($name => $defaultCurrency));
  }

  /**
   * Create a single or multiple entity ref field.
   * @param string $name
   * @param string $label
   * @param array $props
   *   Mix of html and widget properties, including:.
   *   - select - params to give to select2 widget
   *   - entity - defaults to contact
   *   - create - can the user create a new entity on-the-fly?
   *             Set to TRUE if entity is contact and you want the default profiles,
   *             or pass in your own set of links. @see CRM_Core_BAO_UFGroup::getCreateLinks for format
   *             note that permissions are checked automatically
   *   - api - array of settings for the getlist api wrapper
   *          note that it accepts a 'params' setting which will be passed to the underlying api
   *   - placeholder - string
   *   - multiple - bool
   *   - class, etc. - other html properties
   * @param bool $required
   *
   * @return HTML_QuickForm_Element
   */
  public function addEntityRef($name, $label = '', $props = array(), $required = FALSE) {
    require_once "api/api.php";
    $config = CRM_Core_Config::singleton();
    // Default properties
    $props['api'] = CRM_Utils_Array::value('api', $props, array());
    $props['entity'] = _civicrm_api_get_entity_name_from_camel(CRM_Utils_Array::value('entity', $props, 'contact'));
    $props['class'] = ltrim(CRM_Utils_Array::value('class', $props, '') . ' crm-form-entityref');

    if ($props['entity'] == 'contact' && isset($props['create']) && !(CRM_Core_Permission::check('edit all contacts') || CRM_Core_Permission::check('add contacts'))) {
      unset($props['create']);
    }

    $props['placeholder'] = CRM_Utils_Array::value('placeholder', $props, $required ? ts('- select %1 -', array(1 => ts(str_replace('_', ' ', $props['entity'])))) : ts('- none -'));

    $defaults = array();
    if (!empty($props['multiple'])) {
      $defaults['multiple'] = TRUE;
    }
    $props['select'] = CRM_Utils_Array::value('select', $props, array()) + $defaults;

    $this->formatReferenceFieldAttributes($props);
    return $this->add('text', $name, $label, $props, $required);
  }

  /**
   * @param $props
   */
  private function formatReferenceFieldAttributes(&$props) {
    $props['data-select-params'] = json_encode($props['select']);
    $props['data-api-params'] = $props['api'] ? json_encode($props['api']) : NULL;
    $props['data-api-entity'] = $props['entity'];
    if (!empty($props['create'])) {
      $props['data-create-links'] = json_encode($props['create']);
    }
    CRM_Utils_Array::remove($props, 'multiple', 'select', 'api', 'entity', 'create');
  }

  /**
   * Convert all date fields within the params to mysql date ready for the
   * BAO layer. In this case fields are checked against the $_datefields defined for the form
   * and if time is defined it is incorporated
   *
   * @param array $params
   *   Input params from the form.
   *
   * @todo it would probably be better to work on $this->_params than a passed array
   * @todo standardise the format which dates are passed to the BAO layer in & remove date
   * handling from BAO
   */
  public function convertDateFieldsToMySQL(&$params) {
    foreach ($this->_dateFields as $fieldName => $specs) {
      if (!empty($params[$fieldName])) {
        $params[$fieldName] = CRM_Utils_Date::isoToMysql(
          CRM_Utils_Date::processDate(
            $params[$fieldName],
            CRM_Utils_Array::value("{$fieldName}_time", $params), TRUE)
        );
      }
      else {
        if (isset($specs['default'])) {
          $params[$fieldName] = date('YmdHis', strtotime($specs['default']));
        }
      }
    }
  }

  /**
   * @param $elementName
   */
  public function removeFileRequiredRules($elementName) {
    $this->_required = array_diff($this->_required, array($elementName));
    if (isset($this->_rules[$elementName])) {
      foreach ($this->_rules[$elementName] as $index => $ruleInfo) {
        if ($ruleInfo['type'] == 'uploadedfile') {
          unset($this->_rules[$elementName][$index]);
        }
      }
      if (empty($this->_rules[$elementName])) {
        unset($this->_rules[$elementName]);
      }
    }
  }

  /**
   * Function that can be defined in Form to override or.
   * perform specific action on cancel action
   */
  public function cancelAction() {
  }

  /**
   * Helper function to verify that required fields have been filled.
   * Typically called within the scope of a FormRule function
   */
  public static function validateMandatoryFields($fields, $values, &$errors) {
    foreach ($fields as $name => $fld) {
      if (!empty($fld['is_required']) && CRM_Utils_System::isNull(CRM_Utils_Array::value($name, $values))) {
        $errors[$name] = ts('%1 is a required field.', array(1 => $fld['title']));
      }
    }
  }

  /**
   * Get contact if for a form object. Prioritise
   *   - cid in URL if 0 (on behalf on someoneelse)
   *      (@todo consider setting a variable if onbehalf for clarity of downstream 'if's
   *   - logged in user id if it matches the one in the cid in the URL
   *   - contact id validated from a checksum from a checksum
   *   - cid from the url if the caller has ACL permission to view
   *   - fallback is logged in user (or ? NULL if no logged in user) (@todo wouldn't 0 be more intuitive?)
   *
   * @return NULL|int
   */
  public function getContactID() {
    $tempID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if (isset($this->_params) && isset($this->_params['select_contact_id'])) {
      $tempID = $this->_params['select_contact_id'];
    }
    if (isset($this->_params, $this->_params[0]) && !empty($this->_params[0]['select_contact_id'])) {
      // event form stores as an indexed array, contribution form not so much...
      $tempID = $this->_params[0]['select_contact_id'];
    }

    // force to ignore the authenticated user
    if ($tempID === '0' || $tempID === 0) {
      // we set the cid on the form so that this will be retained for the Confirm page
      // in the multi-page form & prevent us returning the $userID when this is called
      // from that page
      // we don't really need to set it when $tempID is set because the params have that stored
      $this->set('cid', 0);
      return $tempID;
    }

    $userID = $this->getLoggedInUserContactID();

    if ($tempID == $userID) {
      return $userID;
    }

    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if ($userChecksum) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        return $tempID;
      }
    }
    // check if user has permission, CRM-12062
    elseif ($tempID && CRM_Contact_BAO_Contact_Permission::allow($tempID)) {
      return $tempID;
    }

    return $userID;
  }

  /**
   * Get the contact id of the logged in user.
   */
  public function getLoggedInUserContactID() {
    // check if the user is logged in and has a contact ID
    $session = CRM_Core_Session::singleton();
    return $session->get('userID');
  }

  /**
   * Add autoselector field -if user has permission to view contacts
   * If adding this to a form you also need to add to the tpl e.g
   *
   * {if !empty($selectable)}
   * <div class="crm-summary-row">
   *   <div class="crm-label">{$form.select_contact.label}</div>
   *   <div class="crm-content">
   *     {$form.select_contact.html}
   *   </div>
   * </div>
   * {/if}
   *
   * @param array $profiles
   *   Ids of profiles that are on the form (to be autofilled).
   * @param array $autoCompleteField
   *
   *   - name_field
   *   - id_field
   *   - url (for ajax lookup)
   *
   * @todo add data attributes so we can deal with multiple instances on a form
   */
  public function addAutoSelector($profiles = array(), $autoCompleteField = array()) {
    $autoCompleteField = array_merge(array(
      'id_field' => 'select_contact_id',
      'placeholder' => ts('Select someone else ...'),
      'show_hide' => TRUE,
      'api' => array('params' => array('contact_type' => 'Individual')),
    ), $autoCompleteField);

    if ($this->canUseAjaxContactLookups()) {
      $this->assign('selectable', $autoCompleteField['id_field']);
      $this->addEntityRef($autoCompleteField['id_field'], NULL, array(
          'placeholder' => $autoCompleteField['placeholder'],
          'api' => $autoCompleteField['api'],
        ));

      CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'js/AlternateContactSelector.js', 1, 'html-header')
        ->addSetting(array(
          'form' => array('autocompletes' => $autoCompleteField),
          'ids' => array('profile' => $profiles),
        ));
    }
  }

  /**
   */
  public function canUseAjaxContactLookups() {
    if (0 < (civicrm_api3('contact', 'getcount', array('check_permissions' => 1))) &&
      CRM_Core_Permission::check(array(array('access AJAX API', 'access CiviCRM')))
    ) {
      return TRUE;
    }
  }

  /**
   * Add the options appropriate to cid = zero - ie. autocomplete
   *
   * @todo there is considerable code duplication between the contribution forms & event forms. It is apparent
   * that small pieces of duplication are not being refactored into separate functions because their only shared parent
   * is this form. Inserting a class FrontEndForm.php between the contribution & event & this class would allow functions like this
   * and a dozen other small ones to be refactored into a shared parent with the reduction of much code duplication
   *
   * @param $onlinePaymentProcessorEnabled
   */
  public function addCIDZeroOptions($onlinePaymentProcessorEnabled) {
    $this->assign('nocid', TRUE);
    $profiles = array();
    if ($this->_values['custom_pre_id']) {
      $profiles[] = $this->_values['custom_pre_id'];
    }
    if ($this->_values['custom_post_id']) {
      $profiles = array_merge($profiles, (array) $this->_values['custom_post_id']);
    }
    if ($onlinePaymentProcessorEnabled) {
      $profiles[] = 'billing';
    }
    if (!empty($this->_values)) {
      $this->addAutoSelector($profiles);
    }
  }

  /**
   * Set default values on form for given contact (or no contact defaults)
   *
   * @param mixed $profile_id
   *   (can be id, or profile name).
   * @param int $contactID
   *
   * @return array
   */
  public function getProfileDefaults($profile_id = 'Billing', $contactID = NULL) {
    try {
      $defaults = civicrm_api3('profile', 'getsingle', array(
        'profile_id' => (array) $profile_id,
        'contact_id' => $contactID,
      ));
      return $defaults;
    }
    catch (Exception $e) {
      // the try catch block gives us silent failure -not 100% sure this is a good idea
      // as silent failures are often worse than noisy ones
      return array();
    }
  }

  /**
   * Sets form attribute.
   * @see CRM.loadForm
   */
  public function preventAjaxSubmit() {
    $this->setAttribute('data-no-ajax-submit', 'true');
  }

  /**
   * Sets form attribute.
   * @see CRM.loadForm
   */
  public function allowAjaxSubmit() {
    $this->removeAttribute('data-no-ajax-submit');
  }

  /**
   * Sets page title based on entity and action.
   * @param string $entityLabel
   */
  public function setPageTitle($entityLabel) {
    switch ($this->_action) {
      case CRM_Core_Action::ADD:
        CRM_Utils_System::setTitle(ts('New %1', array(1 => $entityLabel)));
        break;

      case CRM_Core_Action::UPDATE:
        CRM_Utils_System::setTitle(ts('Edit %1', array(1 => $entityLabel)));
        break;

      case CRM_Core_Action::VIEW:
      case CRM_Core_Action::PREVIEW:
        CRM_Utils_System::setTitle(ts('View %1', array(1 => $entityLabel)));
        break;

      case CRM_Core_Action::DELETE:
        CRM_Utils_System::setTitle(ts('Delete %1', array(1 => $entityLabel)));
        break;
    }
  }

  /**
   * Create a chain-select target field. All settings are optional; the defaults usually work.
   *
   * @param string $elementName
   * @param array $settings
   *
   * @return HTML_QuickForm_Element
   */
  public function addChainSelect($elementName, $settings = array()) {
    $props = $settings += array(
      'control_field' => str_replace(array('state_province', 'StateProvince', 'county', 'County'), array(
          'country',
          'Country',
          'state_province',
          'StateProvince',
        ), $elementName),
      'data-callback' => strpos($elementName, 'rovince') ? 'civicrm/ajax/jqState' : 'civicrm/ajax/jqCounty',
      'label' => strpos($elementName, 'rovince') ? ts('State/Province') : ts('County'),
      'data-empty-prompt' => strpos($elementName, 'rovince') ? ts('Choose country first') : ts('Choose state first'),
      'data-none-prompt' => ts('- N/A -'),
      'multiple' => FALSE,
      'required' => FALSE,
      'placeholder' => empty($settings['required']) ? ts('- none -') : ts('- select -'),
    );
    CRM_Utils_Array::remove($props, 'label', 'required', 'control_field');
    $props['class'] = (empty($props['class']) ? '' : "{$props['class']} ") . 'crm-select2';
    $props['data-select-prompt'] = $props['placeholder'];
    $props['data-name'] = $elementName;

    $this->_chainSelectFields[$settings['control_field']] = $elementName;

    // Passing NULL instead of an array of options
    // CRM-15225 - normally QF will reject any selected values that are not part of the field's options, but due to a
    // quirk in our patched version of HTML_QuickForm_select, this doesn't happen if the options are NULL
    // which seems a bit dirty but it allows our dynamically-popuplated select element to function as expected.
    return $this->add('select', $elementName, $settings['label'], NULL, $settings['required'], $props);
  }

  /**
   * Set options and attributes for chain select fields based on the controlling field's value
   */
  private function preProcessChainSelectFields() {
    foreach ($this->_chainSelectFields as $control => $target) {
      // The 'target' might get missing if extensions do removeElement() in a form hook.
      if ($this->elementExists($target)) {
        $targetField = $this->getElement($target);
        $targetType = $targetField->getAttribute('data-callback') == 'civicrm/ajax/jqCounty' ? 'county' : 'stateProvince';
        $options = array();
        // If the control field is on the form, setup chain-select and dynamically populate options
        if ($this->elementExists($control)) {
          $controlField = $this->getElement($control);
          $controlType = $targetType == 'county' ? 'stateProvince' : 'country';

          $targetField->setAttribute('class', $targetField->getAttribute('class') . ' crm-chain-select-target');

          $css = (string) $controlField->getAttribute('class');
          $controlField->updateAttributes(array(
            'class' => ($css ? "$css " : 'crm-select2 ') . 'crm-chain-select-control',
            'data-target' => $target,
          ));
          $controlValue = $controlField->getValue();
          if ($controlValue) {
            $options = CRM_Core_BAO_Location::getChainSelectValues($controlValue, $controlType, TRUE);
            if (!$options) {
              $targetField->setAttribute('placeholder', $targetField->getAttribute('data-none-prompt'));
            }
          } 
          else {
            $targetField->setAttribute('placeholder', $targetField->getAttribute('data-empty-prompt'));
            $targetField->setAttribute('disabled', 'disabled');
          }
        }
        // Control field not present - fall back to loading default options
        else {
          $options = CRM_Core_PseudoConstant::$targetType();
        }
        if (!$targetField->getAttribute('multiple')) {
          $options = array('' => $targetField->getAttribute('placeholder')) + $options;
          $targetField->removeAttribute('placeholder');
        }
        $targetField->_options = array();
        $targetField->loadArray($options);
      }
    }
  }

  /**
   * Validate country / state / county match and suppress unwanted "required" errors
   */
  private function validateChainSelectFields() {
    foreach ($this->_chainSelectFields as $control => $target) {
      if ($this->elementExists($control) && $this->elementExists($target)) {
        $controlValue = (array) $this->getElementValue($control);
        $targetField = $this->getElement($target);
        $controlType = $targetField->getAttribute('data-callback') == 'civicrm/ajax/jqCounty' ? 'stateProvince' : 'country';
        $targetValue = array_filter((array) $targetField->getValue());
        if ($targetValue || $this->getElementError($target)) {
          $options = CRM_Core_BAO_Location::getChainSelectValues($controlValue, $controlType, TRUE);
          if ($targetValue) {
            if (!array_intersect($targetValue, array_keys($options))) {
              $this->setElementError($target, $controlType == 'country' ? ts('State/Province does not match the selected Country') : ts('County does not match the selected State/Province'));
            }
          } // Suppress "required" error for field if it has no options
          elseif (!$options) {
            $this->setElementError($target, NULL);
          }
        }
      }
    }
  }

}
