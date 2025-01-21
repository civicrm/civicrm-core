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
 * This class acts as our base controller class and adds additional
 * functionality and smarts to the base QFC. Specifically we create
 * our own action classes and handle the transitions ourselves by
 * simulating a state machine. We also create direct jump links to any
 * page that can be used universally.
 *
 * This concept has been discussed on the PEAR list and the QFC FAQ
 * goes into a few details. Please check
 * http://pear.php.net/manual/en/package.html.html-quickform-controller.faq.php
 * for other useful tips and suggestions
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

require_once 'HTML/QuickForm/Controller.php';
require_once 'HTML/QuickForm/Action/Direct.php';

/**
 * Class CRM_Core_Controller
 */
class CRM_Core_Controller extends HTML_QuickForm_Controller {

  /**
   * The title associated with this controller.
   *
   * @var string
   */
  protected $_title;

  /**
   * The key associated with this controller.
   *
   * @var string
   */
  public $_key;

  /**
   * The name of the session scope where values are stored.
   *
   * @var object
   */
  protected $_scope;

  /**
   * The state machine associated with this controller.
   *
   * @var object
   */
  protected $_stateMachine;

  /**
   * Is this object being embedded in another object. If
   * so the display routine needs to not do any work. (The
   * parent object takes care of the display)
   *
   * @var bool
   */
  protected $_embedded = FALSE;

  /**
   * After entire form execution complete,
   * do we want to skip control redirection.
   * Default - It get redirect to user context.
   *
   * Useful when we run form in non civicrm context
   * and we need to transfer control back.(eg. drupal)
   *
   * @var bool
   */
  protected $_skipRedirection = FALSE;

  /**
   * Are we in print mode? if so we need to modify the display
   * functionality to do a minimal display :)
   *
   * @var int|string
   *   Should match a CRM_Core_Smarty::PRINT_* constant,
   *   or equal 0 if not in print mode
   */
  public $_print = 0;

  /**
   * Should we generate a qfKey, true by default
   *
   * @var bool
   */
  public $_generateQFKey = TRUE;

  /**
   * QF response type.
   *
   * @var string
   */
  public $_QFResponseType = 'html';

  /**
   * Cache the smarty template for efficiency reasons.
   *
   * @var CRM_Core_Smarty
   */
  static protected $_template;

  /**
   * Cache the session for efficiency reasons.
   *
   * @var CRM_Core_Session
   */
  static protected $_session;

  /**
   * The parent of this form if embedded.
   *
   * @var object
   */
  protected $_parent = NULL;

  /**
   * The destination if set will override the destination the code wants to send it to.
   *
   * @var string
   */
  public $_destination = NULL;

  /**
   * The entry url for a top level form or wizard. Typically the URL with a reset=1
   * used to redirect back to when we land into some session wierdness
   *
   * @var string
   */
  public $_entryURL = NULL;

  /**
   * All CRM single or multi page pages should inherit from this class.
   *
   * @param string $title
   *   Descriptive title of the controller.
   * @param bool $modal
   *   Whether controller is modal.
   * @param mixed $mode
   * @param string $scope
   *   Name of session if we want unique scope, used only by Controller_Simple.
   * @param bool $addSequence
   *   Should we add a unique sequence number to the end of the key.
   * @param bool $ignoreKey
   *   Should we not set a qfKey for this controller (for standalone forms).
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct(
    $title = NULL,
    $modal = TRUE,
    $mode = NULL,
    $scope = NULL,
    $addSequence = FALSE,
    $ignoreKey = FALSE
  ) {
    // this has to true for multiple tab session fix
    $addSequence = TRUE;

    // let the constructor initialize this, should happen only once
    if (!isset(self::$_template)) {
      self::$_template = CRM_Core_Smarty::singleton();
      self::$_session = CRM_Core_Session::singleton();
      self::$_template->ensureVariablesAreAssigned(['formTpl']);
    }

    // lets try to get it from the session and/or the request vars
    // we do this early on in case there is a fatal error in retrieving the
    // key and/or session
    $this->_entryURL
      = CRM_Utils_Request::retrieve('entryURL', 'String', $this);

    // add a unique validable key to the name
    $name = CRM_Utils_System::getClassName($this);
    if ($name == 'CRM_Core_Controller_Simple' && !empty($scope)) {
      // use form name if we have, since its a lot better and
      // definitely different for different forms
      $name = $scope;
    }
    $name = $name . '_' . $this->key($name, $addSequence, $ignoreKey);
    $this->_title = $title;
    if ($scope) {
      $this->_scope = $scope;
    }
    else {
      $this->_scope = CRM_Utils_System::getClassName($this);
    }
    $this->_scope = $this->_scope . '_' . $this->_key;

    // only use the civicrm cache if we have a valid key
    // else we clash with other users CRM-7059
    if (!empty($this->_key)) {
      CRM_Core_Session::registerAndRetrieveSessionObjects([
        "_{$name}_container",
        ['CiviCRM', $this->_scope],
      ]);
    }

    parent::__construct($name, $modal);

    $snippet = $_REQUEST['snippet'] ?? NULL;
    if ($snippet) {
      if ($snippet == 3) {
        $this->_print = CRM_Core_Smarty::PRINT_PDF;
      }
      elseif ($snippet == 4) {
        // this is used to embed fragments of a form
        $this->_print = CRM_Core_Smarty::PRINT_NOFORM;
        self::$_template->assign('suppressForm', TRUE);
        $this->_generateQFKey = FALSE;
      }
      elseif ($snippet == 5) {
        // mode deprecated in favor of json
        // still used by dashlets, probably nothing else
        $this->_print = CRM_Core_Smarty::PRINT_NOFORM;
      }
      // Respond with JSON if in AJAX context (also support legacy value '6')
      elseif (in_array($snippet, [CRM_Core_Smarty::PRINT_JSON, 6])) {
        CRM_Core_Page_AJAX::validateAjaxRequestMethod();
        $this->_print = CRM_Core_Smarty::PRINT_JSON;
        $this->_QFResponseType = 'json';
      }
      else {
        $this->_print = CRM_Core_Smarty::PRINT_SNIPPET;
      }
    }

    // if the request has a reset value, initialize the controller session
    if (!empty($_GET['reset'])) {
      $this->reset();

      // in this case we'll also cache the url as a hidden form variable, this allows us to
      // redirect in case the session has disappeared on us
      $this->_entryURL = CRM_Utils_System::makeURL(NULL, TRUE, FALSE, NULL, TRUE);
      // In WordPress Shortcodes the standard entryURL generated via makeURL doesn't generally have id=x&reset=1 included so we add them here
      // This prevents infinite loops caused when the session has timed out.
      if (stripos($this->_entryURL, 'id') === FALSE && (stripos($this->_entryURL, 'transact') !== FALSE || stripos($this->_entryURL, 'register') !== FALSE)) {
        $this->_entryURL .= '&id=' . CRM_Utils_Request::retrieveValue('id', 'Positive') . '&reset=1';
      }
      $this->set('entryURL', $this->_entryURL);
    }

    // set the key in the session
    // do this at the end so we have initialized the object
    // and created the scope etc
    $this->set('qfKey', $this->_key);

    // also retrieve and store destination in session
    $this->_destination = CRM_Utils_Request::retrieve(
      'civicrmDestination',
      'String',
      $this,
      FALSE,
      NULL,
      $_REQUEST
    );
  }

  public function fini() {
    CRM_Core_BAO_Cache::storeSessionToCache([
      "_{$this->_name}_container",
      ['CiviCRM', $this->_scope],
    ], TRUE);
  }

  /**
   * @param string $name
   * @param bool $addSequence
   * @param bool $ignoreKey
   *
   * @return mixed|string
   */
  public function key($name, $addSequence = FALSE, $ignoreKey = FALSE) {
    $config = CRM_Core_Config::singleton();

    if (
      $ignoreKey ||
      (isset($config->keyDisable) && $config->keyDisable)
    ) {
      return NULL;
    }

    // We need a form key. Check _POST first, then _GET.
    // @todo Note: we currently have to check $_REQUEST, too, since that
    // is currently overwritten by civicrm_api3_contribution_page_validate.
    // It's bad form to use $_REQUEST because it's ambiguous; and it's bad form
    // to change superglobals anyway. If PR
    // https://github.com/civicrm/civicrm-core/pull/17324
    // and/or related get merged, then we should remove the REQUEST reference here.
    $key = $_POST['qfKey'] ?? $_GET['qfKey'] ?? $_REQUEST['qfKey'] ?? NULL;
    if (!$key && in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'])) {
      // Generate a key if this is an initial request without one.
      // We allow HEAD here because it is used by bots to validate URLs, so if
      // we issue a 500 server error to them they may think the site is broken.
      $key = CRM_Core_Key::get($name, $addSequence);
    }
    else {
      // Other requests that usually change data (POST, but feasibly DELETE,
      // PUT, PATCH...) always require a valid key.
      $key = CRM_Core_Key::validate($key, $name, $addSequence);
    }

    if (!$key) {
      $this->invalidKey();
    }

    $this->_key = $key;

    return $key;
  }

  /**
   * Process the request, overrides the default QFC run method
   * This routine actually checks if the QFC is modal and if it
   * is the first invalid page, if so it call the requested action
   * if not, it calls the display action on the first invalid page
   * avoids the issue of users hitting the back button and getting
   * a broken page
   *
   * This run is basically a composition of the original run and the
   * jump action
   *
   * @return mixed
   */
  public function run() {
    // the names of the action and page should be saved
    // note that this is split into two, because some versions of
    // php 5.x core dump on the triple assignment :)
    $this->_actionName = $this->getActionName();
    [$pageName, $action] = $this->_actionName;

    if ($this->isModal()) {
      if (!$this->isValid($pageName)) {
        $pageName = $this->findInvalid();
        $action = 'display';
      }
    }

    // note that based on action, control might not come back!!
    // e.g. if action is a valid JUMP, u basically do a redirect
    // to the appropriate place
    $this->wizardHeader($pageName);
    return $this->_pages[$pageName]->handle($action);
  }

  /**
   * @return bool
   */
  public function validate() {
    $this->_actionName = $this->getActionName();
    [$pageName, $action] = $this->_actionName;

    $page = &$this->_pages[$pageName];

    $data = &$this->container();
    $this->applyDefaults($pageName);
    $page->isFormBuilt() or $page->buildForm();
    // We use defaults and constants as if they were submitted
    $data['values'][$pageName] = $page->exportValues();
    $page->loadValues($data['values'][$pageName]);
    // Is the page now valid?
    if (TRUE === ($data['valid'][$pageName] = $page->validate())) {
      return TRUE;
    }
    return $page->_errors;
  }

  /**
   * Helper function to add all the needed default actions.
   *
   * Note that the framework redefines all of the default QFC actions.
   *
   * @param string $uploadDirectory to store all the uploaded files
   * @param array $uploadNames for the various upload buttons (note u can have more than 1 upload)
   */
  public function addActions($uploadDirectory = NULL, $uploadNames = NULL) {
    $names = [
      'display' => 'CRM_Core_QuickForm_Action_Display',
      'next' => 'CRM_Core_QuickForm_Action_Next',
      'back' => 'CRM_Core_QuickForm_Action_Back',
      'process' => 'CRM_Core_QuickForm_Action_Process',
      'cancel' => 'CRM_Core_QuickForm_Action_Cancel',
      'refresh' => 'CRM_Core_QuickForm_Action_Refresh',
      'reload' => 'CRM_Core_QuickForm_Action_Reload',
      'done' => 'CRM_Core_QuickForm_Action_Done',
      'jump' => 'CRM_Core_QuickForm_Action_Jump',
      'submit' => 'CRM_Core_QuickForm_Action_Submit',
    ];

    foreach ($names as $name => $classPath) {
      $action = new $classPath($this->_stateMachine);
      $this->addAction($name, $action);
    }

    $this->addUploadAction($uploadDirectory, $uploadNames);
  }

  /**
   * Getter method for stateMachine.
   *
   * @return CRM_Core_StateMachine
   */
  public function getStateMachine() {
    return $this->_stateMachine;
  }

  /**
   * Setter method for stateMachine.
   *
   * @param CRM_Core_StateMachine $stateMachine
   */
  public function setStateMachine($stateMachine) {
    $this->_stateMachine = $stateMachine;
  }

  /**
   * Add pages to the controller. Note that the controller does not really care
   * the order in which the pages are added
   *
   * @param CRM_Core_StateMachine $stateMachine
   * @param \const|int $action the mode in which the state machine is operating
   *                              typically this will be add/view/edit
   */
  public function addPages(&$stateMachine, $action = CRM_Core_Action::NONE) {
    $pages = $stateMachine->getPages();
    foreach ($pages as $name => $value) {
      $className = $value['className'] ?? $name;
      $title = $value['title'] ?? NULL;
      $options = $value['options'] ?? NULL;
      $stateName = CRM_Utils_String::getClassName($className);
      if (!empty($value['className'])) {
        $formName = $name;
      }
      else {
        $formName = CRM_Utils_String::getClassName($name);
      }

      $ext = CRM_Extension_System::singleton()->getMapper();
      if ($ext->isExtensionClass($className)) {
        require_once $ext->classToPath($className);
      }
      else {
        require_once str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
      }
      $$stateName = new $className($stateMachine->find($className), $action, 'post', $formName);
      if ($title) {
        $$stateName->setTitle($title);
      }
      if ($options) {
        $$stateName->setOptions($options);
      }
      if (property_exists($$stateName, 'urlPath')) {
        $$stateName->urlPath = explode('/', (string) CRM_Utils_System::currentPath());
      }
      $this->addPage($$stateName);
      $this->addAction($stateName, new HTML_QuickForm_Action_Direct());

      //CRM-6342 -we need kill the reference here,
      //as we have deprecated reference object creation.
      unset($$stateName);
    }
  }

  /**
   * QFC does not provide native support to have different 'submit' buttons.
   * We introduce this notion to QFC by using button specific data. Thus if
   * we have two submit buttons, we could have one displayed as a button and
   * the other as an image, both are of type 'submit'.
   *
   * @return string
   *   the name of the button that has been pressed by the user
   */
  public function getButtonName() {
    $data = &$this->container();
    return $data['_qf_button_name'] ?? '';
  }

  /**
   * Destroy all the session state of the controller.
   */
  public function reset() {
    $this->container(TRUE);
    self::$_session->resetScope($this->_scope);
  }

  /**
   * Virtual function to do any processing of data.
   *
   * Sometimes it is useful for the controller to actually process data.
   * This is typically used when we need the controller to figure out
   * what pages are potentially involved in this wizard. (this is dynamic
   * and can change based on the arguments
   */
  public function process() {
  }

  /**
   * Store the variable with the value in the form scope.
   *
   * @param string|array $name name of the variable or an assoc array of name/value pairs
   * @param mixed $value
   *   Value of the variable if string.
   */
  public function set($name, $value = NULL) {
    self::$_session->set($name, $value, $this->_scope);
  }

  /**
   * Get the variable from the form scope.
   *
   * @param string $name
   *   name of the variable.
   *
   * @return mixed
   */
  public function get($name) {
    return self::$_session->get($name, $this->_scope);
  }

  /**
   * Create the header for the wizard from the list of pages.
   * Store the created header in smarty
   *
   * @param string $currentPageName
   *   Name of the page being displayed.
   *
   * @return array
   */
  public function wizardHeader($currentPageName) {
    $wizard = [];
    $wizard['steps'] = [];
    $count = 0;
    foreach ($this->_pages as $name => $page) {
      $count++;
      $wizard['steps'][] = [
        'name' => $name,
        'title' => $page->getTitle(),
        //'link'      => $page->getLink ( ),
        'link' => NULL,
        'step' => TRUE,
        'valid' => TRUE,
        'stepNumber' => $count,
        'collapsed' => FALSE,
      ];

      if ($name == $currentPageName) {
        $wizard['currentStepNumber'] = $count;
        $wizard['currentStepName'] = $name;
        $wizard['currentStepTitle'] = $page->getTitle();
      }
    }

    $wizard['stepCount'] = $count;

    $this->addWizardStyle($wizard);

    $this->assign('wizard', $wizard);
    return $wizard;
  }

  /**
   * @param array $wizard
   */
  public function addWizardStyle(&$wizard) {
    $wizard['style'] = [
      'barClass' => '',
      'stepPrefixCurrent' => '<i class="crm-i fa-chevron-right" aria-hidden="true"></i> ',
      'stepPrefixPast' => '<i class="crm-i fa-check" aria-hidden="true"></i> ',
      'stepPrefixFuture' => ' ',
      'subStepPrefixCurrent' => '&nbsp;&nbsp;',
      'subStepPrefixPast' => '&nbsp;&nbsp;',
      'subStepPrefixFuture' => '&nbsp;&nbsp;',
      'showTitle' => 1,
    ];
  }

  /**
   * Assign value to name in template.
   *
   * @param string $var
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
   * @param mixed $value
   *   (reference) value of variable.
   *
   * @deprecated since 5.72 will be removed around 5.84
   */
  public function assign_by_ref($var, &$value) {
    CRM_Core_Error::deprecatedFunctionWarning('assign');
    self::$_template->assign($var, $value);
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
   * @deprecated since 5.69 will be removed around 5.93. use getTemplateVars.
   *
   * @param string $name
   *
   * @return array
   */
  public function get_template_vars($name = NULL) {
    return $this->getTemplateVars($name);
  }

  /**
   * Get the value/s assigned to the Template Engine (Smarty).
   *
   * @param string|null $name
   */
  public function getTemplateVars($name = NULL) {
    return self::$_template->getTemplateVars($name);
  }

  /**
   * Setter for embedded.
   *
   * @param bool $embedded
   */
  public function setEmbedded($embedded) {
    $this->_embedded = $embedded;
  }

  /**
   * Getter for embedded.
   *
   * @return bool
   *   return the embedded value
   */
  public function getEmbedded() {
    return $this->_embedded;
  }

  /**
   * Setter for skipRedirection.
   *
   * @param bool $skipRedirection
   */
  public function setSkipRedirection($skipRedirection) {
    $this->_skipRedirection = $skipRedirection;
  }

  /**
   * Getter for skipRedirection.
   *
   * @return bool
   *   return the skipRedirection value
   */
  public function getSkipRedirection() {
    return $this->_skipRedirection;
  }

  /**
   * Output HTTP headers for Word document
   * (note .doc, not the newer .docx format)
   *
   * @deprecated
   *
   * @param string|null $fileName
   * @return void
   */
  public function setWord($fileName = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');

    //Mark as a CSV file.
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/vnd.ms-word');

    //Force a download and name the file using the current timestamp.
    if (!$fileName) {
      $fileName = 'Contacts_' . $_SERVER['REQUEST_TIME'] . '.doc';
    }
    CRM_Utils_System::setHttpHeader("Content-Disposition", "attachment; filename=Contacts_$fileName");
  }

  /**
   * Output HTTP headers for Excel document
   * (note .xls, not the newer .xlsx format)
   *
   * @deprecated
   *
   * @param string|null $fileName
   * @return void
   */
  public function setExcel($fileName = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');

    //Mark as an excel file.
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/vnd.ms-excel');

    //Force a download and name the file using the current timestamp.
    if (!$fileName) {
      $fileName = 'Contacts_' . $_SERVER['REQUEST_TIME'] . '.xls';
    }

    CRM_Utils_System::setHttpHeader("Content-Disposition", "attachment; filename=Contacts_$fileName");
  }

  /**
   * Setter for print.
   *
   * Historically the $print argument has also accepted a string (xls or doc),
   * but this usage is now deprecated.
   *
   * @param int|string $print
   *   Should match a CRM_Core_Smarty::PRINT_* constant,
   *   or equal 0 if not in print mode
   *
   * @return void
   */
  public function setPrint($print) {
    if ($print === "xls") {
      $this->setExcel();
    }
    elseif ($print === "doc") {
      $this->setWord();
    }
    $this->_print = $print;
  }

  /**
   * Getter for print.
   *
   * @return int|string
   *   Value matching a CRM_Core_Smarty::PRINT_* constant,
   *   or 0 if not in print mode
   */
  public function getPrint() {
    return $this->_print;
  }

  /**
   * @return string
   */
  public function getTemplateFile() {
    return CRM_Utils_System::getContentTemplate($this->_print);
  }

  /**
   * @param $uploadDir
   * @param $uploadNames
   */
  public function addUploadAction($uploadDir, $uploadNames) {
    if (empty($uploadDir)) {
      $config = CRM_Core_Config::singleton();
      $uploadDir = $config->uploadDir;
    }

    if (empty($uploadNames)) {
      $uploadNames = $this->get('uploadNames');
      if (!empty($uploadNames)) {
        $uploadNames = array_merge($uploadNames,
          CRM_Core_BAO_File::uploadNames()
        );
      }
      else {
        $uploadNames = CRM_Core_BAO_File::uploadNames();
      }
    }

    $action = new CRM_Core_QuickForm_Action_Upload($this->_stateMachine,
      $uploadDir,
      $uploadNames
    );
    $this->addAction('upload', $action);
  }

  /**
   * @param $parent
   */
  public function setParent($parent) {
    $this->_parent = $parent;
  }

  /**
   * @return object
   */
  public function getParent() {
    return $this->_parent;
  }

  /**
   * @return string
   */
  public function getDestination() {
    return $this->_destination;
  }

  /**
   * @param string|null $url
   * @param bool $setToReferer
   */
  public function setDestination($url = NULL, $setToReferer = FALSE) {
    if (empty($url)) {
      if ($setToReferer) {
        $url = $_SERVER['HTTP_REFERER'];
      }
      else {
        $config = CRM_Core_Config::singleton();
        $url = $config->userFrameworkBaseURL;
      }
    }

    $this->_destination = $url;
    $this->set('civicrmDestination', $this->_destination);
  }

  /**
   * @return mixed
   */
  public function cancelAction() {
    $actionName = $this->getActionName();
    [$pageName, $action] = $actionName;
    return $this->_pages[$pageName]->cancelAction();
  }

  /**
   * Write a simple fatal error message.
   *
   * Other controllers can decide to do something else and present the user a better message
   * and/or redirect to the same page with a reset url
   */
  public function invalidKey() {
    self::invalidKeyCommon();
  }

  public function invalidKeyCommon() {
    throw new CRM_Core_Exception(
      ts("Sorry, your session has expired. Please reload the page or go back and try again."), 419, ts("Could not find a valid session key."));
  }

  /**
   * Instead of outputting a fatal error message, we'll just redirect
   * to the entryURL if present
   */
  public function invalidKeyRedirect() {
    if ($this->_entryURL && $url_parts = parse_url($this->_entryURL)) {
      // CRM-16832: Ensure local redirects only.
      if (!empty($url_parts['path'])) {
        // Prepend a slash, but don't duplicate it.
        $redirect_url = '/' . ltrim($url_parts['path'], '/');
        if (!empty($url_parts['query'])) {
          $redirect_url .= '?' . $url_parts['query'];
        }
        CRM_Core_Session::setStatus(ts('Sorry, your session has expired. Please try again.'));
        return CRM_Utils_System::redirect($redirect_url);
      }
    }
    self::invalidKeyCommon();
  }

}
