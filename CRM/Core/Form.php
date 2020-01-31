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
 * This is our base form. It is part of the Form/Controller/StateMachine
 * trifecta. Each form is associated with a specific state in the state
 * machine. Each form can also operate in various modes
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * The default values for the form.
   *
   * @var array
   */
  public $_defaults = [];

  /**
   * (QUASI-PROTECTED) The options passed into this form
   *
   * This field should marked `protected` and is not generally
   * intended for external callers, but some edge-cases do use it.
   *
   * @var mixed
   */
  public $_options = NULL;

  /**
   * (QUASI-PROTECTED) The mode of operation for this form
   *
   * This field should marked `protected` and is not generally
   * intended for external callers, but some edge-cases do use it.
   *
   * @var int
   */
  public $_action;

  /**
   * Available payment processors.
   *
   * As part of trying to consolidate various payment pages we store processors here & have functions
   * at this level to manage them.
   *
   * @var array
   *   An array of payment processor details with objects loaded in the 'object' field.
   */
  protected $_paymentProcessors;

  /**
   * Available payment processors (IDS).
   *
   * As part of trying to consolidate various payment pages we store processors here & have functions
   * at this level to manage them. An alternative would be to have a separate Form that is inherited
   * by all forms that allow payment processing.
   *
   * @var array
   *   An array of the IDS available on this form.
   */
  public $_paymentProcessorIDs;

  /**
   * Default or selected processor id.
   *
   * As part of trying to consolidate various payment pages we store processors here & have functions
   * at this level to manage them. An alternative would be to have a separate Form that is inherited
   * by all forms that allow payment processing.
   *
   * @var int
   */
  protected $_paymentProcessorID;

  /**
   * Is pay later enabled for the form.
   *
   * As part of trying to consolidate various payment pages we store processors here & have functions
   * at this level to manage them. An alternative would be to have a separate Form that is inherited
   * by all forms that allow payment processing.
   *
   * @var int
   */
  protected $_is_pay_later_enabled;

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
  protected $_dateFields = [];

  /**
   * Cache the smarty template for efficiency reasons
   *
   * @var CRM_Core_Smarty
   */
  static protected $_template;

  /**
   *  Indicate if this form should warn users of unsaved changes
   * @var bool
   */
  protected $unsavedChangesWarn;

  /**
   * What to return to the client if in ajax mode (snippet=json)
   *
   * @var array
   */
  public $ajaxResponse = [];

  /**
   * Url path used to reach this page
   *
   * @var array
   */
  public $urlPath = [];

  /**
   * Context of the form being loaded.
   *
   * 'event' or null
   *
   * @var string
   */
  protected $context;

  /**
   * @var bool
   */
  public $submitOnce = FALSE;

  /**
   * @return string
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Set context variable.
   */
  public function setContext() {
    $this->context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
  }

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
   * @var array
   * @internal to keep track of chain-select fields
   */
  private $_chainSelectFields = [];

  /**
   * Extra input types we support via the "add" method
   * @var array
   */
  public static $html5Types = [
    'number',
    'url',
    'email',
    'color',
  ];

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

    parent::__construct($this->_name, $method);

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
    $this->setTranslatedFields();
  }

  /**
   * Set translated fields.
   *
   * This function is called from the class constructor, allowing us to set
   * fields on the class that can't be set as properties due to need for
   * translation or other non-input specific handling.
   */
  protected function setTranslatedFields() {}

  /**
   * Add one or more css classes to the form.
   *
   * @param string $className
   */
  public function addClass($className) {
    $classes = $this->getAttribute('class');
    $this->setAttribute('class', ($classes ? "$classes " : '') . $className);
  }

  /**
   * Register all the standard rules that most forms potentially use.
   */
  public function registerRules() {
    static $rules = [
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
      'settingPath',
      'autocomplete',
      'validContact',
    ];

    foreach ($rules as $rule) {
      $this->registerRule($rule, 'callback', $rule, 'CRM_Utils_Rule');
    }
  }

  /**
   * Simple easy to use wrapper around addElement.
   *
   * Deal with simple validation rules.
   *
   * @param string $type
   * @param string $name
   * @param string $label
   * @param string|array $attributes (options for select elements)
   * @param bool $required
   * @param array $extra
   *   (attributes for select elements).
   *   For datepicker elements this is consistent with the data
   *   from CRM_Utils_Date::getDatePickerExtra
   *
   * @return HTML_QuickForm_Element
   *   Could be an error object
   */
  public function &add(
    $type, $name, $label = '',
    $attributes = '', $required = FALSE, $extra = NULL
  ) {
    // Fudge some extra types that quickform doesn't support
    $inputType = $type;
    if ($type == 'wysiwyg' || in_array($type, self::$html5Types)) {
      $attributes = ($attributes ? $attributes : []) + ['class' => ''];
      $attributes['class'] = ltrim($attributes['class'] . " crm-form-$type");
      if ($type == 'wysiwyg' && isset($attributes['preset'])) {
        $attributes['data-preset'] = $attributes['preset'];
        unset($attributes['preset']);
      }
      $type = $type == 'wysiwyg' ? 'textarea' : 'text';
    }
    // Like select but accepts rich array data (with nesting, colors, icons, etc) as option list.
    if ($inputType == 'select2') {
      $type = 'text';
      $options = $attributes;
      $attributes = ($extra ? $extra : []) + ['class' => ''];
      $attributes['class'] = ltrim($attributes['class'] . " crm-select2 crm-form-select2");
      $attributes['data-select-params'] = json_encode(['data' => $options, 'multiple' => !empty($attributes['multiple'])]);
      unset($attributes['multiple']);
      $extra = NULL;
    }
    // @see http://wiki.civicrm.org/confluence/display/CRMDOC/crmDatepicker
    if ($type == 'datepicker') {
      $attributes = ($attributes ? $attributes : []);
      $attributes['data-crm-datepicker'] = json_encode((array) $extra);
      if (!empty($attributes['aria-label']) || $label) {
        $attributes['aria-label'] = CRM_Utils_Array::value('aria-label', $attributes, $label);
      }
      $type = "text";
    }
    if ($type == 'select' && is_array($extra)) {
      // Normalize this property
      if (!empty($extra['multiple'])) {
        $extra['multiple'] = 'multiple';
      }
      else {
        unset($extra['multiple']);
      }
      unset($extra['size'], $extra['maxlength']);
      // Add placeholder option for select
      if (isset($extra['placeholder'])) {
        if ($extra['placeholder'] === TRUE) {
          $extra['placeholder'] = $required ? ts('- select -') : ts('- none -');
        }
        if (($extra['placeholder'] || $extra['placeholder'] === '') && empty($extra['multiple']) && is_array($attributes) && !isset($attributes[''])) {
          $attributes = ['' => $extra['placeholder']] + $attributes;
        }
      }
    }
    $element = $this->addElement($type, $name, $label, $attributes, $extra);
    if (HTML_QuickForm::isError($element)) {
      CRM_Core_Error::fatal(HTML_QuickForm::errorMessage($element));
    }

    if ($inputType == 'color') {
      $this->addRule($name, ts('%1 must contain a color value e.g. #ffffff.', [1 => $label]), 'regex', '/#[0-9a-fA-F]{6}/');
    }

    if ($required) {
      if ($type == 'file') {
        $error = $this->addRule($name, ts('%1 is a required field.', [1 => $label]), 'uploadedfile');
      }
      else {
        $error = $this->addRule($name, ts('%1 is a required field.', [1 => $label]), 'required');
      }
      if (HTML_QuickForm::isError($error)) {
        CRM_Core_Error::fatal(HTML_QuickForm::errorMessage($element));
      }
    }

    // Add context for the editing of option groups
    if (isset($extra['option_context'])) {
      $context = json_encode($extra['option_context']);
      $element->setAttribute('data-option-edit-context', $context);
    }

    return $element;
  }

  /**
   * Preprocess form.
   *
   * This is called before buildForm. Any pre-processing that
   * needs to be done for buildForm should be done here.
   *
   * This is a virtual function and should be redefined if needed.
   */
  public function preProcess() {
  }

  /**
   * Called after the form is validated.
   *
   * Any processing of form state etc should be done in this function.
   * Typically all processing associated with a form should be done
   * here and relevant state should be stored in the session
   *
   * This is a virtual function and should be redefined if needed
   */
  public function postProcess() {
  }

  /**
   * Main process wrapper.
   *
   * Implemented so that we can call all the hook functions.
   *
   * @param bool $allowAjax
   *   FIXME: This feels kind of hackish, ideally we would take the json-related code from this function.
   *                          and bury it deeper down in the controller
   */
  public function mainProcess($allowAjax = TRUE) {
    $this->postProcess();
    $this->postProcessHook();

    // Respond with JSON if in AJAX context (also support legacy value '6')
    if ($allowAjax && !empty($_REQUEST['snippet']) && in_array($_REQUEST['snippet'], [
      CRM_Core_Smarty::PRINT_JSON,
      6,
    ])) {
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
   *
   * However in a few cases, the form exits or redirects early in which
   * case it needs to call this function so other modules can do the needful
   * Calling this function directly should be avoided if possible. In general a
   * better way is to do setUserContext so the framework does the redirect
   */
  public function postProcessHook() {
    CRM_Utils_Hook::postProcess(get_class($this), $this);
  }

  /**
   * This virtual function is used to build the form.
   *
   * It replaces the buildForm associated with QuickForm_Page. This allows us to put
   * preProcess in front of the actual form building routine
   */
  public function buildQuickForm() {
  }

  /**
   * This virtual function is used to set the default values of various form elements.
   *
   * @return array|NULL
   *   reference to the array of default values
   */
  public function setDefaultValues() {
    return NULL;
  }

  /**
   * This is a virtual function that adds group and global rules to the form.
   *
   * Keeping it distinct from the form to keep code small
   * and localized in the form building code
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

    $hookErrors = [];

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
   * Core function that builds the form.
   *
   * We redefine this function here and expect all CRM forms to build their form in the function
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
    // also call the hook function so any modules can set their own custom defaults
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

    if ($this->submitOnce) {
      $this->setAttribute('data-submit-once', 'true');
    }
  }

  /**
   * Add default Next / Back buttons.
   *
   * @param array $params
   *   Array of associative arrays in the order in which the buttons should be
   *   displayed. The associate array has 3 fields: 'type', 'name' and 'isDefault'
   *   The base form class will define a bunch of static arrays for commonly used
   *   formats.
   */
  public function addButtons($params) {
    $prevnext = $spacing = [];
    foreach ($params as $button) {
      if (!empty($button['submitOnce'])) {
        $this->submitOnce = TRUE;
      }

      $attrs = ['class' => 'crm-form-submit'] + (array) CRM_Utils_Array::value('js', $button);

      if (!empty($button['class'])) {
        $attrs['class'] .= ' ' . $button['class'];
      }

      if (!empty($button['isDefault'])) {
        $attrs['class'] .= ' default';
      }

      if (in_array($button['type'], ['upload', 'next', 'submit', 'done', 'process', 'refresh'])) {
        $attrs['class'] .= ' validate';
        $defaultIcon = 'fa-check';
      }
      else {
        $attrs['class'] .= ' cancel';
        $defaultIcon = $button['type'] == 'back' ? 'fa-chevron-left' : 'fa-times';
      }

      if ($button['type'] === 'reset') {
        $prevnext[] = $this->createElement($button['type'], 'reset', $button['name'], $attrs);
      }
      else {
        if (!empty($button['subName'])) {
          if ($button['subName'] == 'new') {
            $defaultIcon = 'fa-plus-circle';
          }
          if ($button['subName'] == 'done') {
            $defaultIcon = 'fa-check-circle';
          }
          if ($button['subName'] == 'next') {
            $defaultIcon = 'fa-chevron-right';
          }
        }

        if (in_array($button['type'], ['next', 'upload', 'done']) && $button['name'] === ts('Save')) {
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
        $this->updateAttributes(['enctype' => 'multipart/form-data']);
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
   * Getter function for title.
   *
   * Should be over-ridden by derived class.
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
   */
  public function setTitle($title) {
    $this->_title = $title;
    CRM_Utils_System::setTitle($title);
  }

  /**
   * Assign billing type id to bltID.
   *
   * @throws CRM_Core_Exception
   */
  public function assignBillingType() {
    $this->_bltID = CRM_Core_BAO_LocationType::getBilling();
    $this->set('bltID', $this->_bltID);
    $this->assign('bltID', $this->_bltID);
  }

  /**
   * @return int
   */
  public function getPaymentProcessorID() {
    return $this->_paymentProcessorID;
  }

  /**
   * This if a front end form function for setting the payment processor.
   *
   * It would be good to sync it with the back-end function on abstractEditPayment & use one everywhere.
   *
   * @param bool $isPayLaterEnabled
   *
   * @throws \CRM_Core_Exception
   */
  protected function assignPaymentProcessor($isPayLaterEnabled) {
    $this->_paymentProcessors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(
      [ucfirst($this->_mode) . 'Mode'],
      $this->_paymentProcessorIDs
    );
    if ($isPayLaterEnabled) {
      $this->_paymentProcessors[0] = CRM_Financial_BAO_PaymentProcessor::getPayment(0);
    }

    if (!empty($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $paymentProcessorID => $paymentProcessorDetail) {
        if (empty($this->_paymentProcessor) && $paymentProcessorDetail['is_default'] == 1 || (count($this->_paymentProcessors) == 1)
        ) {
          $this->_paymentProcessor = $paymentProcessorDetail;
          $this->assign('paymentProcessor', $this->_paymentProcessor);
          // Setting this is a bit of a legacy overhang.
          $this->_paymentObject = $paymentProcessorDetail['object'];
        }
      }
      // It's not clear why we set this on the form.
      $this->set('paymentProcessors', $this->_paymentProcessors);
    }
    else {
      throw new CRM_Core_Exception(ts('A payment processor configured for this page might be disabled (contact the site administrator for assistance).'));
    }

  }

  /**
   * Format the fields in $this->_params for the payment processor.
   *
   * In order to pass fields to the payment processor in a consistent way we add some renamed
   * parameters.
   *
   * @param array $fields
   *
   * @return array
   */
  protected function formatParamsForPaymentProcessor($fields) {
    $this->_params = $this->prepareParamsForPaymentProcessor($this->_params);
    $fields = array_merge($fields, ['first_name' => 1, 'middle_name' => 1, 'last_name' => 1]);
    return $fields;
  }

  /**
   * Format the fields in $params for the payment processor.
   *
   * In order to pass fields to the payment processor in a consistent way we add some renamed
   * parameters.
   *
   * @param array $params Payment processor params
   *
   * @return array $params
   */
  protected function prepareParamsForPaymentProcessor($params) {
    // also add location name to the array
    $params["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $params) . ' ' . CRM_Utils_Array::value('billing_middle_name', $params) . ' ' . CRM_Utils_Array::value('billing_last_name', $params);
    $params["address_name-{$this->_bltID}"] = trim($params["address_name-{$this->_bltID}"]);
    // Add additional parameters that the payment processors are used to receiving.
    if (!empty($params["billing_state_province_id-{$this->_bltID}"])) {
      $params['state_province'] = $params["state_province-{$this->_bltID}"] = $params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($params["billing_state_province_id-{$this->_bltID}"]);
    }
    if (!empty($params["billing_country_id-{$this->_bltID}"])) {
      $params['country'] = $params["country-{$this->_bltID}"] = $params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($params["billing_country_id-{$this->_bltID}"]);
    }

    list($hasAddressField, $addressParams) = CRM_Contribute_BAO_Contribution::getPaymentProcessorReadyAddressParams($params, $this->_bltID);
    if ($hasAddressField) {
      $params = array_merge($params, $addressParams);
    }

    // How does this relate to similar code in CRM_Contact_BAO_Contact::addBillingNameFieldsIfOtherwiseNotSet()?
    $nameFields = ['first_name', 'middle_name', 'last_name'];
    foreach ($nameFields as $name) {
      if (array_key_exists("billing_$name", $params)) {
        $params[$name] = $params["billing_{$name}"];
        $params['preserveDBName'] = TRUE;
      }
    }

    // For legacy reasons we set these creditcard expiry fields if present
    if (isset($params['credit_card_exp_date'])) {
      $params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($params);
      $params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($params);
    }

    // Assign IP address parameter
    $params['ip_address'] = CRM_Utils_System::ipAddress();

    return $params;
  }

  /**
   * Handle Payment Processor switching for contribution and event registration forms.
   *
   * This function is shared between contribution & event forms & this is their common class.
   *
   * However, this should be seen as an in-progress refactor, the end goal being to also align the
   * backoffice forms that action payments.
   *
   * This function overlaps assignPaymentProcessor, in a bad way.
   */
  protected function preProcessPaymentOptions() {
    $this->_paymentProcessorID = NULL;
    if ($this->_paymentProcessors) {
      if (!empty($this->_submitValues)) {
        $this->_paymentProcessorID = CRM_Utils_Array::value('payment_processor_id', $this->_submitValues);
        $this->_paymentProcessor = CRM_Utils_Array::value($this->_paymentProcessorID, $this->_paymentProcessors);
        $this->set('type', $this->_paymentProcessorID);
        $this->set('mode', $this->_mode);
        $this->set('paymentProcessor', $this->_paymentProcessor);
      }
      // Set default payment processor
      else {
        foreach ($this->_paymentProcessors as $values) {
          if (!empty($values['is_default']) || count($this->_paymentProcessors) == 1) {
            $this->_paymentProcessorID = $values['id'];
            break;
          }
        }
      }
      if ($this->_paymentProcessorID
        || (isset($this->_submitValues['payment_processor_id']) && $this->_submitValues['payment_processor_id'] == 0)
      ) {
        CRM_Core_Payment_ProcessorForm::preProcess($this);
      }
      else {
        $this->_paymentProcessor = [];
      }
    }

    // We save the fact that the profile 'billing' is required on the payment form.
    // Currently pay-later is the only 'processor' that takes notice of this - but ideally
    // 1) it would be possible to select the minimum_billing_profile_id for the contribution form
    // 2) that profile_id would be set on the payment processor
    // 3) the payment processor would return a billing form that combines these user-configured
    // minimums with the payment processor minimums. This would lead to fields like 'postal_code'
    // only being on the form if either the admin has configured it as wanted or the processor
    // requires it.
    $this->assign('billing_profile_id', (CRM_Utils_Array::value('is_billing_required', $this->_values) ? 'billing' : ''));
  }

  /**
   * Handle pre approval for processors.
   *
   * This fits with the flow where a pre-approval is done and then confirmed in the next stage when confirm is hit.
   *
   * This function is shared between contribution & event forms & this is their common class.
   *
   * However, this should be seen as an in-progress refactor, the end goal being to also align the
   * backoffice forms that action payments.
   *
   * @param array $params
   */
  protected function handlePreApproval(&$params) {
    try {
      $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
      $params['component'] = 'contribute';
      $result = $payment->doPreApproval($params);
      if (empty($result)) {
        // This could happen, for example, when paypal looks at the button value & decides it is not paypal express.
        return;
      }
    }
    catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
      CRM_Core_Error::statusBounce(ts('Payment approval failed with message :') . $e->getMessage(), $payment->getCancelUrl($params['qfKey'], CRM_Utils_Array::value('participant_id', $params)));
    }

    $this->set('pre_approval_parameters', $result['pre_approval_parameters']);
    if (!empty($result['redirect_url'])) {
      CRM_Utils_System::redirect($result['redirect_url']);
    }
  }

  /**
   * Setter function for options.
   *
   * @param mixed $options
   */
  public function setOptions($options) {
    $this->_options = $options;
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
   * Getter function for renderer.
   *
   * If renderer is not set create one and initialize it.
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
        [
          '_' => DIRECTORY_SEPARATOR,
          '\\' => DIRECTORY_SEPARATOR,
        ]
      ) . '.tpl';
    }
    return $tplname;
  }

  /**
   * A wrapper for getTemplateFileName.
   *
   * This includes calling the hook to prevent us from having to copy & paste the logic of calling the hook.
   */
  public function getHookedTemplateFileName() {
    $pageTemplateFile = $this->getTemplateFileName();
    CRM_Utils_Hook::alterTemplateFile(get_class($this), $this, 'page', $pageTemplateFile);
    return $pageTemplateFile;
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl.
   *
   * i.e. we do not override.
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
   *   Value of variable.
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
  public function &addRadio($name, $title, $values, $attributes = [], $separator = NULL, $required = FALSE) {
    $options = [];
    $attributes = $attributes ? $attributes : [];
    $allowClear = !empty($attributes['allowClear']);
    unset($attributes['allowClear']);
    $attributes['id_suffix'] = $name;
    foreach ($values as $key => $var) {
      $options[] = $this->createElement('radio', NULL, NULL, $var, $key, $attributes);
    }
    $group = $this->addGroup($options, $name, $title, $separator);

    $optionEditKey = 'data-option-edit-path';
    if (!empty($attributes[$optionEditKey])) {
      $group->setAttribute($optionEditKey, $attributes[$optionEditKey]);
    }

    if ($required) {
      $this->addRule($name, ts('%1 is a required field.', [1 => $title]), 'required');
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
  public function addYesNo($id, $title, $allowClear = FALSE, $required = NULL, $attributes = []) {
    $attributes += ['id_suffix' => $id];
    $choice = [];
    $choice[] = $this->createElement('radio', NULL, '11', ts('Yes'), '1', $attributes);
    $choice[] = $this->createElement('radio', NULL, '11', ts('No'), '0', $attributes);

    $group = $this->addGroup($choice, $id, $title);
    if ($allowClear) {
      $group->setAttribute('allowClear', TRUE);
    }
    if ($required) {
      $this->addRule($id, ts('%1 is a required field.', [1 => $title]), 'required');
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
    $options = [];

    if ($javascriptMethod) {
      foreach ($values as $key => $var) {
        if (!$flipValues) {
          $options[] = $this->createElement('checkbox', $var, NULL, $key, $javascriptMethod, $attributes);
        }
        else {
          $options[] = $this->createElement('checkbox', $key, NULL, $var, $javascriptMethod, $attributes);
        }
      }
    }
    else {
      foreach ($values as $key => $var) {
        if (!$flipValues) {
          $options[] = $this->createElement('checkbox', $var, NULL, $key, $attributes);
        }
        else {
          $options[] = $this->createElement('checkbox', $key, NULL, $var, $attributes);
        }
      }
    }

    $group = $this->addGroup($options, $id, $title, $separator);
    $optionEditKey = 'data-option-edit-path';
    if (!empty($attributes[$optionEditKey])) {
      $group->setAttribute($optionEditKey, $attributes[$optionEditKey]);
    }

    if ($other) {
      $this->addElement('text', $id . '_other', ts('Other'), $attributes[$id . '_other']);
    }

    if ($required) {
      $this->addRule($id,
        ts('%1 is a required field.', [1 => $title]),
        'required'
      );
    }
  }

  public function resetValues() {
    $data = $this->controller->container();
    $data['values'][$this->_name] = [];
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
   * @param bool|string $submitOnce
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $buttons = [];
    if ($backType != NULL) {
      $buttons[] = [
        'type' => $backType,
        'name' => ts('Previous'),
      ];
    }
    if ($nextType != NULL) {
      $nextButton = [
        'type' => $nextType,
        'name' => $title,
        'isDefault' => TRUE,
      ];
      if ($submitOnce) {
        $this->submitOnce = TRUE;
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
      $this->addDateTime($name . $from, $label, $required, ['formatType' => $dateFormat]);
      $this->addDateTime($name . $to, ts('To:'), $required, ['formatType' => $dateFormat]);
    }
    else {
      $this->addDate($name . $from, $label, $required, ['formatType' => $dateFormat]);
      $this->addDate($name . $to, ts('To:'), $required, ['formatType' => $dateFormat]);
    }
  }

  /**
   * Add a search for a range using date picker fields.
   *
   * @param string $fieldName
   * @param string $label
   * @param bool $isDateTime
   *   Is this a date-time field (not just date).
   * @param bool $required
   * @param string $fromLabel
   * @param string $toLabel
   */
  public function addDatePickerRange($fieldName, $label, $isDateTime = FALSE, $required = FALSE, $fromLabel = 'From', $toLabel = 'To') {

    $options = [
      '' => ts('- any -'),
      0 => ts('Choose Date Range'),
    ] + CRM_Core_OptionGroup::values('relative_date_filters');

    $this->add('select',
      "{$fieldName}_relative",
      $label,
      $options,
      $required,
      ['class' => 'crm-select2']
    );
    $attributes = ['format' => 'searchDate'];
    $extra = ['time' => $isDateTime];
    $this->add('datepicker', $fieldName . '_low', ts($fromLabel), $attributes, $required, $extra);
    $this->add('datepicker', $fieldName . '_high', ts($toLabel), $attributes, $required, $extra);
  }

  /**
   * Based on form action, return a string representing the api action.
   * Used by addField method.
   *
   * Return string
   */
  protected function getApiAction() {
    $action = $this->getAction();
    if ($action & (CRM_Core_Action::UPDATE + CRM_Core_Action::ADD)) {
      return 'create';
    }
    if ($action & (CRM_Core_Action::VIEW + CRM_Core_Action::BROWSE + CRM_Core_Action::BASIC + CRM_Core_Action::ADVANCED + CRM_Core_Action::PREVIEW)) {
      return 'get';
    }
    if ($action & (CRM_Core_Action::DELETE)) {
      return 'delete';
    }
    // If you get this exception try adding more cases above.
    throw new Exception("Cannot determine api action for " . get_class($this) . '.' . 'CRM_Core_Action "' . CRM_Core_Action::description($action) . '" not recognized.');
  }

  /**
   * Classes extending CRM_Core_Form should implement this method.
   * @throws Exception
   */
  public function getDefaultEntity() {
    throw new Exception("Cannot determine default entity. " . get_class($this) . " should implement getDefaultEntity().");
  }

  /**
   * Classes extending CRM_Core_Form should implement this method.
   *
   * TODO: Merge with CRM_Core_DAO::buildOptionsContext($context) and add validation.
   * @throws Exception
   */
  public function getDefaultContext() {
    throw new Exception("Cannot determine default context. " . get_class($this) . " should implement getDefaultContext().");
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
  public function addSelect($name, $props = [], $required = FALSE) {
    if (!isset($props['entity'])) {
      $props['entity'] = $this->getDefaultEntity();
    }
    if (!isset($props['field'])) {
      $props['field'] = strrpos($name, '[') ? rtrim(substr($name, 1 + strrpos($name, '[')), ']') : $name;
    }
    if (!isset($props['context'])) {
      try {
        $props['context'] = $this->getDefaultContext();
      }
      // This is not a required param, so we'll ignore if this doesn't exist.
      catch (Exception $e) {
      }
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
          in_array($props['field'], CRM_Utils_Array::value('api.aliases', $fieldSpec, []))
        ) {
          break;
        }
      }
      $label = isset($props['label']) ? $props['label'] : $fieldSpec['title'];
      if (CRM_Utils_Array::value('context', $props) != 'search') {
        $props['data-option-edit-path'] = array_key_exists('option_url', $props) ? $props['option_url'] : CRM_Core_PseudoConstant::getOptionEditUrl($fieldSpec);
      }
    }
    $props['class'] = (isset($props['class']) ? $props['class'] . ' ' : '') . "crm-select2";
    $props['data-api-entity'] = $props['entity'];
    $props['data-api-field'] = $props['field'];
    CRM_Utils_Array::remove($props, 'label', 'entity', 'field', 'option_url', 'options', 'context');
    return $this->add('select', $name, $label, $options, $required, $props);
  }

  /**
   * Adds a field based on metadata.
   *
   * @param $name
   *   Field name to go on the form.
   * @param array $props
   *   Mix of html attributes and special properties, namely.
   *   - entity (api entity name, can usually be inferred automatically from the form class)
   *   - name (field name - only needed if different from name used on the form)
   *   - option_url - path to edit this option list - usually retrieved automatically - set to NULL to disable link
   *   - placeholder - set to NULL to disable
   *   - multiple - bool
   *   - context - @see CRM_Core_DAO::buildOptionsContext
   * @param bool $required
   * @param bool $legacyDate
   *   Temporary param to facilitate the conversion of fields to use the datepicker in
   *   a controlled way. To convert the field the jcalendar code needs to be removed from the
   *   tpl as well. That file is intended to be EOL.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   * @return mixed
   *   HTML_QuickForm_Element
   *   void
   */
  public function addField($name, $props = [], $required = FALSE, $legacyDate = TRUE) {
    // Resolve context.
    if (empty($props['context'])) {
      $props['context'] = $this->getDefaultContext();
    }
    $context = $props['context'];
    // Resolve entity.
    if (empty($props['entity'])) {
      $props['entity'] = $this->getDefaultEntity();
    }
    // Resolve field.
    if (empty($props['name'])) {
      $props['name'] = strrpos($name, '[') ? rtrim(substr($name, 1 + strrpos($name, '[')), ']') : $name;
    }
    // Resolve action.
    if (empty($props['action'])) {
      $props['action'] = $this->getApiAction();
    }

    // Handle custom fields
    if (strpos($name, 'custom_') === 0 && is_numeric($name[7])) {
      $fieldId = (int) substr($name, 7);
      return CRM_Core_BAO_CustomField::addQuickFormElement($this, $name, $fieldId, $required, $context == 'search', CRM_Utils_Array::value('label', $props));
    }

    // Core field - get metadata.
    $fieldSpec = civicrm_api3($props['entity'], 'getfield', $props);
    $fieldSpec = $fieldSpec['values'];
    $fieldSpecLabel = isset($fieldSpec['html']['label']) ? $fieldSpec['html']['label'] : CRM_Utils_Array::value('title', $fieldSpec);
    $label = CRM_Utils_Array::value('label', $props, $fieldSpecLabel);

    $widget = isset($props['type']) ? $props['type'] : $fieldSpec['html']['type'];
    if ($widget == 'TextArea' && $context == 'search') {
      $widget = 'Text';
    }

    $isSelect = (in_array($widget, [
      'Select',
      'Select2',
      'CheckBoxGroup',
      'RadioGroup',
      'Radio',
    ]));

    if ($isSelect) {
      // Fetch options from the api unless passed explicitly.
      if (isset($props['options'])) {
        $options = $props['options'];
      }
      else {
        $options = isset($fieldSpec['options']) ? $fieldSpec['options'] : NULL;
      }
      if ($context == 'search') {
        $widget = $widget == 'Select2' ? $widget : 'Select';
        $props['multiple'] = CRM_Utils_Array::value('multiple', $props, TRUE);
      }

      // Add data for popup link.
      $canEditOptions = CRM_Core_Permission::check('administer CiviCRM');
      $hasOptionUrl = !empty($props['option_url']);
      $optionUrlKeyIsSet = array_key_exists('option_url', $props);
      $shouldAdd = $context !== 'search' && $isSelect && $canEditOptions;

      // Only add if key is not set, or if non-empty option url is provided
      if (($hasOptionUrl || !$optionUrlKeyIsSet) && $shouldAdd) {
        $optionUrl = $hasOptionUrl ? $props['option_url'] :
          CRM_Core_PseudoConstant::getOptionEditUrl($fieldSpec);
        $props['data-option-edit-path'] = $optionUrl;
        $props['data-api-entity'] = $props['entity'];
        $props['data-api-field'] = $props['name'];
      }
    }
    $props += CRM_Utils_Array::value('html', $fieldSpec, []);
    CRM_Utils_Array::remove($props, 'entity', 'name', 'context', 'label', 'action', 'type', 'option_url', 'options');

    // TODO: refactor switch statement, to separate methods.
    switch ($widget) {
      case 'Text':
      case 'Url':
      case 'Number':
      case 'Email':
        //TODO: Autodetect ranges
        $props['size'] = isset($props['size']) ? $props['size'] : 60;
        return $this->add(strtolower($widget), $name, $label, $props, $required);

      case 'hidden':
        return $this->add('hidden', $name, NULL, $props, $required);

      case 'TextArea':
        //Set default columns and rows for textarea.
        $props['rows'] = isset($props['rows']) ? $props['rows'] : 4;
        $props['cols'] = isset($props['cols']) ? $props['cols'] : 60;
        if (empty($props['maxlength']) && isset($fieldSpec['length'])) {
          $props['maxlength'] = $fieldSpec['length'];
        }
        return $this->add('textarea', $name, $label, $props, $required);

      case 'Select Date':
        // This is a white list for fields that have been tested with
        // date picker. We should be able to remove the other
        if ($legacyDate) {
          //TODO: add range support
          //TODO: Add date formats
          //TODO: Add javascript template for dates.
          return $this->addDate($name, $label, $required, $props);
        }
        else {
          $fieldSpec = CRM_Utils_Date::addDateMetadataToField($fieldSpec, $fieldSpec);
          $attributes = ['format' => $fieldSpec['date_format']];
          return $this->add('datepicker', $name, $label, $attributes, $required, $fieldSpec['datepicker']['extra']);
        }

      case 'Radio':
        $separator = isset($props['separator']) ? $props['separator'] : NULL;
        unset($props['separator']);
        if (!isset($props['allowClear'])) {
          $props['allowClear'] = !$required;
        }
        return $this->addRadio($name, $label, $options, $props, $separator, $required);

      case 'ChainSelect':
        $props += [
          'required' => $required,
          'label' => $label,
          'multiple' => $context == 'search',
        ];
        return $this->addChainSelect($name, $props);

      case 'Select':
      case 'Select2':
        $props['class'] = CRM_Utils_Array::value('class', $props, 'big') . ' crm-select2';
        if (!array_key_exists('placeholder', $props)) {
          $props['placeholder'] = $required ? ts('- select -') : ($context == 'search' ? ts('- any -') : ts('- none -'));
        }
        // TODO: Add and/or option for fields that store multiple values
        return $this->add(strtolower($widget), $name, $label, $options, $required, $props);

      case 'CheckBoxGroup':
        return $this->addCheckBox($name, $label, array_flip($options), $required, $props);

      case 'RadioGroup':
        return $this->addRadio($name, $label, $options, $props, NULL, $required);

      case 'CheckBox':
        if ($context === 'search') {
          $this->addYesNo($name, $label, TRUE, FALSE, $props);
          return;
        }
        $text = isset($props['text']) ? $props['text'] : NULL;
        unset($props['text']);
        return $this->addElement('checkbox', $name, $label, $text, $props);

      //add support for 'Advcheckbox' field
      case 'advcheckbox':
        $text = isset($props['text']) ? $props['text'] : NULL;
        unset($props['text']);
        return $this->addElement('advcheckbox', $name, $label, $text, $props);

      case 'File':
        // We should not build upload file in search mode.
        if ($context == 'search') {
          return;
        }
        $file = $this->add('file', $name, $label, $props, $required);
        $this->addUploadElement($name);
        return $file;

      case 'RichTextEditor':
        return $this->add('wysiwyg', $name, $label, $props, $required);

      case 'EntityRef':
        return $this->addEntityRef($name, $label, $props, $required);

      case 'Password':
        $props['size'] = isset($props['size']) ? $props['size'] : 60;
        return $this->add('password', $name, $label, $props, $required);

      // Check datatypes of fields
      // case 'Int':
      //case 'Float':
      //case 'Money':
      //case read only fields
      default:
        throw new Exception("Unsupported html-element " . $widget);
    }
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
   * @param string $usedFor
   */
  public function addProfileSelector($name, $label, $allowCoreTypes, $allowSubTypes, $entities, $default = FALSE, $usedFor = NULL) {
    // Output widget
    // FIXME: Instead of adhoc serialization, use a single json_encode()
    CRM_UF_Page_ProfileEditor::registerProfileScripts();
    CRM_UF_Page_ProfileEditor::registerSchemas(CRM_Utils_Array::collect('entity_type', $entities));
    $this->add('text', $name, $label, [
      'class' => 'crm-profile-selector',
      // Note: client treats ';;' as equivalent to \0, and ';;' works better in HTML
      'data-group-type' => CRM_Core_BAO_UFGroup::encodeGroupType($allowCoreTypes, $allowSubTypes, ';;'),
      'data-entities' => json_encode($entities),
      //CRM-15427
      'data-default' => $default,
      'data-usedfor' => json_encode($usedFor),
    ]);
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
      $uploadNames = [];
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
   * @deprecated
   * Use $this->add('datepicker', ...) instead.
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
      $params = ['name' => $attributes['formatType']];
      $values = [];

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

        $this->add('text', $elementName, ts('Time'), ['timeFormat' => $show24Hours]);
      }
    }

    if ($required) {
      $this->addRule($name, ts('Please select %1', [1 => $label]), 'required');
      if (!empty($attributes['addTime']) && !empty($attributes['addTimeRequired'])) {
        $this->addRule($elementName, ts('Please enter a time.'), 'required');
      }
    }
  }

  /**
   * Function that will add date and time.
   *
   * @deprecated
   * Use $this->add('datepicker', ...) instead.
   *
   * @param string $name
   * @param string $label
   * @param bool $required
   * @param null $attributes
   */
  public function addDateTime($name, $label, $required = FALSE, $attributes = NULL) {
    $addTime = ['addTime' => TRUE];
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
   *
   * @param string $name
   * @param string $label
   * @param bool $required
   * @param null $attributes
   * @param bool $addCurrency
   * @param string $currencyName
   * @param null $defaultCurrency
   * @param bool $freezeCurrency
   *
   * @return \HTML_QuickForm_Element
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
   *
   * @param string $name
   * @param null $label
   * @param bool $required
   * @param string $defaultCurrency
   * @param bool $freezeCurrency
   * @param bool $setDefaultCurrency
   */
  public function addCurrency(
    $name = 'currency',
    $label = NULL,
    $required = TRUE,
    $defaultCurrency = NULL,
    $freezeCurrency = FALSE,
    $setDefaultCurrency = TRUE
  ) {
    $currencies = CRM_Core_OptionGroup::values('currencies_enabled');
    if (!empty($defaultCurrency) && !array_key_exists($defaultCurrency, $currencies)) {
      Civi::log()->warning('addCurrency: Currency ' . $defaultCurrency . ' is disabled but still in use!');
      $currencies[$defaultCurrency] = $defaultCurrency;
    }
    $options = ['class' => 'crm-select2 eight'];
    if (!$required) {
      $currencies = ['' => ''] + $currencies;
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
    // In some case, setting currency field by default might override the default value
    //  as encountered in CRM-20527 for batch data entry
    if ($setDefaultCurrency) {
      $this->setDefaults([$name => $defaultCurrency]);
    }
  }

  /**
   * Create a single or multiple entity ref field.
   * @param string $name
   * @param string $label
   * @param array $props
   *   Mix of html and widget properties, including:.
   *   - select - params to give to select2 widget
   *   - entity - defaults to Contact
   *   - create - can the user create a new entity on-the-fly?
   *             Set to TRUE if entity is contact and you want the default profiles,
   *             or pass in your own set of links. @see CRM_Campaign_BAO_Campaign::getEntityRefCreateLinks for format
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
  public function addEntityRef($name, $label = '', $props = [], $required = FALSE) {
    // Default properties
    $props['api'] = CRM_Utils_Array::value('api', $props, []);
    $props['entity'] = CRM_Utils_String::convertStringToCamel(CRM_Utils_Array::value('entity', $props, 'Contact'));
    $props['class'] = ltrim(CRM_Utils_Array::value('class', $props, '') . ' crm-form-entityref');

    if (array_key_exists('create', $props) && empty($props['create'])) {
      unset($props['create']);
    }

    $props['placeholder'] = CRM_Utils_Array::value('placeholder', $props, $required ? ts('- select %1 -', [1 => ts(str_replace('_', ' ', $props['entity']))]) : ts('- none -'));

    $defaults = [];
    if (!empty($props['multiple'])) {
      $defaults['multiple'] = TRUE;
    }
    $props['select'] = CRM_Utils_Array::value('select', $props, []) + $defaults;

    $this->formatReferenceFieldAttributes($props, get_class($this));
    return $this->add('text', $name, $label, $props, $required);
  }

  /**
   * @param array $props
   * @param string $formName
   */
  private function formatReferenceFieldAttributes(&$props, $formName) {
    CRM_Utils_Hook::alterEntityRefParams($props, $formName);
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
    $this->_required = array_diff($this->_required, [$elementName]);
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
   *
   * Typically called within the scope of a FormRule function
   *
   * @param array $fields
   * @param array $values
   * @param array $errors
   */
  public static function validateMandatoryFields($fields, $values, &$errors) {
    foreach ($fields as $name => $fld) {
      if (!empty($fld['is_required']) && CRM_Utils_System::isNull(CRM_Utils_Array::value($name, $values))) {
        $errors[$name] = ts('%1 is a required field.', [1 => $fld['title']]);
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
  protected function setContactID() {
    $tempID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if (isset($this->_params) && !empty($this->_params['select_contact_id'])) {
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
      CRM_Core_Resources::singleton()->addVars('coreForm', ['contact_id' => (int) $tempID]);
      return (int) $tempID;
    }

    $userID = $this->getLoggedInUserContactID();

    if (!is_null($tempID) && $tempID === $userID) {
      CRM_Core_Resources::singleton()->addVars('coreForm', ['contact_id' => (int) $tempID]);
      return (int) $userID;
    }

    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if ($userChecksum) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        CRM_Core_Resources::singleton()->addVars('coreForm', ['contact_id' => (int) $tempID]);
        CRM_Core_Resources::singleton()->addVars('coreForm', ['checksum' => $userChecksum]);
        return $tempID;
      }
    }
    // check if user has permission, CRM-12062
    elseif ($tempID && CRM_Contact_BAO_Contact_Permission::allow($tempID)) {
      CRM_Core_Resources::singleton()->addVars('coreForm', ['contact_id' => (int) $tempID]);
      return $tempID;
    }
    if (is_numeric($userID)) {
      CRM_Core_Resources::singleton()->addVars('coreForm', ['contact_id' => (int) $userID]);
    }
    return is_numeric($userID) ? $userID : NULL;
  }

  /**
   * Get the contact id that the form is being submitted for.
   *
   * @return int|null
   */
  public function getContactID() {
    return $this->setContactID();
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
  public function addAutoSelector($profiles = [], $autoCompleteField = []) {
    $autoCompleteField = array_merge([
      'id_field' => 'select_contact_id',
      'placeholder' => ts('Select someone else ...'),
      'show_hide' => TRUE,
      'api' => ['params' => ['contact_type' => 'Individual']],
    ], $autoCompleteField);

    if ($this->canUseAjaxContactLookups()) {
      $this->assign('selectable', $autoCompleteField['id_field']);
      $this->addEntityRef($autoCompleteField['id_field'], NULL, [
        'placeholder' => $autoCompleteField['placeholder'],
        'api' => $autoCompleteField['api'],
      ]);

      CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'js/AlternateContactSelector.js', 1, 'html-header')
        ->addSetting([
          'form' => ['autocompletes' => $autoCompleteField],
          'ids' => ['profile' => $profiles],
        ]);
    }
  }

  /**
   */
  public function canUseAjaxContactLookups() {
    if (0 < (civicrm_api3('contact', 'getcount', ['check_permissions' => 1])) &&
      CRM_Core_Permission::check([['access AJAX API', 'access CiviCRM']])
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
   */
  public function addCIDZeroOptions() {
    $this->assign('nocid', TRUE);
    $profiles = [];
    if ($this->_values['custom_pre_id']) {
      $profiles[] = $this->_values['custom_pre_id'];
    }
    if ($this->_values['custom_post_id']) {
      $profiles = array_merge($profiles, (array) $this->_values['custom_post_id']);
    }
    $profiles[] = 'billing';
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
      $defaults = civicrm_api3('profile', 'getsingle', [
        'profile_id' => (array) $profile_id,
        'contact_id' => $contactID,
      ]);
      return $defaults;
    }
    catch (Exception $e) {
      // the try catch block gives us silent failure -not 100% sure this is a good idea
      // as silent failures are often worse than noisy ones
      return [];
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
        CRM_Utils_System::setTitle(ts('New %1', [1 => $entityLabel]));
        break;

      case CRM_Core_Action::UPDATE:
        CRM_Utils_System::setTitle(ts('Edit %1', [1 => $entityLabel]));
        break;

      case CRM_Core_Action::VIEW:
      case CRM_Core_Action::PREVIEW:
        CRM_Utils_System::setTitle(ts('View %1', [1 => $entityLabel]));
        break;

      case CRM_Core_Action::DELETE:
        CRM_Utils_System::setTitle(ts('Delete %1', [1 => $entityLabel]));
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
  public function addChainSelect($elementName, $settings = []) {
    $props = $settings += [
      'control_field' => str_replace(['state_province', 'StateProvince', 'county', 'County'], [
        'country',
        'Country',
        'state_province',
        'StateProvince',
      ], $elementName),
      'data-callback' => strpos($elementName, 'rovince') ? 'civicrm/ajax/jqState' : 'civicrm/ajax/jqCounty',
      'label' => strpos($elementName, 'rovince') ? ts('State/Province') : ts('County'),
      'data-empty-prompt' => strpos($elementName, 'rovince') ? ts('Choose country first') : ts('Choose state first'),
      'data-none-prompt' => ts('- N/A -'),
      'multiple' => FALSE,
      'required' => FALSE,
      'placeholder' => empty($settings['required']) ? ts('- none -') : ts('- select -'),
    ];
    CRM_Utils_Array::remove($props, 'label', 'required', 'control_field', 'context');
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
   * Add actions menu to results form.
   *
   * @param array $tasks
   */
  public function addTaskMenu($tasks) {
    if (is_array($tasks) && !empty($tasks)) {
      // Set constants means this will always load with an empty value, not reloading any submitted value.
      // This is appropriate as it is a pseudofield.
      $this->setConstants(['task' => '']);
      $this->assign('taskMetaData', $tasks);
      $select = $this->add('select', 'task', NULL, ['' => ts('Actions')], FALSE, [
        'class' => 'crm-select2 crm-action-menu fa-check-circle-o huge crm-search-result-actions',
      ]
      );
      foreach ($tasks as $key => $task) {
        $attributes = [];
        if (isset($task['data'])) {
          foreach ($task['data'] as $dataKey => $dataValue) {
            $attributes['data-' . $dataKey] = $dataValue;
          }
        }
        $select->addOption($task['title'], $key, $attributes);
      }
      if (empty($this->_actionButtonName)) {
        $this->_actionButtonName = $this->getButtonName('next', 'action');
      }
      $this->assign('actionButtonName', $this->_actionButtonName);
      $this->add('submit', $this->_actionButtonName, ts('Go'), ['class' => 'hiddenElement crm-search-go-button']);

      // Radio to choose "All items" or "Selected items only"
      $selectedRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel', ['checked' => 'checked']);
      $allRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all');
      $this->assign('ts_sel_id', $selectedRowsRadio->_attributes['id']);
      $this->assign('ts_all_id', $allRowsRadio->_attributes['id']);

      CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'js/crm.searchForm.js', 1, 'html-header');
    }
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
        $options = [];
        // If the control field is on the form, setup chain-select and dynamically populate options
        if ($this->elementExists($control)) {
          $controlField = $this->getElement($control);
          $controlType = $targetType == 'county' ? 'stateProvince' : 'country';

          $targetField->setAttribute('class', $targetField->getAttribute('class') . ' crm-chain-select-target');

          $css = (string) $controlField->getAttribute('class');
          $controlField->updateAttributes([
            'class' => ($css ? "$css " : 'crm-select2 ') . 'crm-chain-select-control',
            'data-target' => $target,
          ]);
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
          $options = ['' => $targetField->getAttribute('placeholder')] + $options;
          $targetField->removeAttribute('placeholder');
        }
        $targetField->_options = [];
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
          }
          // Suppress "required" error for field if it has no options
          elseif (!$options) {
            $this->setElementError($target, NULL);
          }
        }
      }
    }
  }

  /**
   * Assign billing name to the template.
   *
   * @param array $params
   *   Form input params, default to $this->_params.
   *
   * @return string
   */
  public function assignBillingName($params = []) {
    $name = '';
    if (empty($params)) {
      $params = $this->_params;
    }
    if (!empty($params['billing_first_name'])) {
      $name = $params['billing_first_name'];
    }

    if (!empty($params['billing_middle_name'])) {
      $name .= " {$params['billing_middle_name']}";
    }

    if (!empty($params['billing_last_name'])) {
      $name .= " {$params['billing_last_name']}";
    }
    $name = trim($name);
    $this->assign('billingName', $name);
    return $name;
  }

  /**
   * Get the currency for the form.
   *
   * @todo this should be overriden on the forms rather than having this
   * historic, possible handling in here. As we clean that up we should
   * add deprecation notices into here.
   *
   * @param array $submittedValues
   *   Array allowed so forms inheriting this class do not break.
   *   Ideally we would make a clear standard around how submitted values
   *   are stored (is $this->_values consistently doing that?).
   *
   * @return string
   */
  public function getCurrency($submittedValues = []) {
    $currency = CRM_Utils_Array::value('currency', $this->_values);
    // For event forms, currency is in a different spot
    if (empty($currency)) {
      $currency = CRM_Utils_Array::value('currency', CRM_Utils_Array::value('event', $this->_values));
    }
    if (empty($currency)) {
      $currency = CRM_Utils_Request::retrieveValue('currency', 'String');
    }
    // @todo If empty there is a problem - we should probably put in a deprecation notice
    // to warn if that seems to be happening.
    return $currency;
  }

  /**
   * Is the form in view or edit mode.
   *
   * The 'addField' function relies on the form action being one of a set list
   * of actions. Checking for these allows for an early return.
   *
   * @return bool
   */
  protected function isFormInViewOrEditMode() {
    return in_array($this->_action, [
      CRM_Core_Action::UPDATE,
      CRM_Core_Action::ADD,
      CRM_Core_Action::VIEW,
      CRM_Core_Action::BROWSE,
      CRM_Core_Action::BASIC,
      CRM_Core_Action::ADVANCED,
      CRM_Core_Action::PREVIEW,
    ]);
  }

}
