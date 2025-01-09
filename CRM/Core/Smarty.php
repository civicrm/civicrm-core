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
 * Fix for bug CRM-392. Not sure if this is the best fix or it will impact
 * other similar PEAR packages. doubt it
 */

use Civi\Core\Event\SmartyErrorEvent;

/**
 *
 */
class CRM_Core_Smarty extends CRM_Core_SmartyCompatibility {
  const
    // use print.tpl and bypass the CMS. Civi prints a valid html file
    PRINT_PAGE = 1,
    // this and all the below bypasses the CMS html surrounding it and assumes we will embed this within other pages
    PRINT_SNIPPET = 2,
    // sends the generated html to the chosen pdf engine
    PRINT_PDF = 3,
    // this options also skips the enclosing form html and does not
    // generate any of the hidden fields, most notably qfKey
    // this is typically used in ajax scripts to embed form snippets based on user choices
    PRINT_NOFORM = 4,
    // this prints a complete form and also generates a qfKey, can we replace this with
    // snippet = 2?? Does the constant _NOFFORM do anything?
    PRINT_QFKEY = 5,
    // Note: added in v 4.3 with the value '6'
    // Value changed in 4.5 to 'json' for better readability
    // @see CRM_Core_Page_AJAX::returnJsonResponse
    PRINT_JSON = 'json';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * Backup frames.
   *
   * A list of variables ot save temporarily in format (string $name => mixed $value).
   *
   * @var array
   */
  private $backupFrames = [];

  /**
   * This is a sentinel-object that indicates an undefined value.
   *
   * It lacks any substantive content; but it has unique identity that cannot be mistaken for
   * organic values like `null`, `string`, `false`, or similar.
   *
   * @var object
   */
  private static $UNDEFINED_VALUE;

  /**
   * @throws \CRM_Core_Exception
   * @throws \SmartyException
   */
  private function initialize() {
    $config = CRM_Core_Config::singleton();

    if (isset($config->customTemplateDir) && $config->customTemplateDir) {
      $template_dir = array_merge([$config->customTemplateDir],
        $config->templateDir
      );
    }
    else {
      $template_dir = $config->templateDir;
    }
    $compile_dir = CRM_Utils_File::addTrailingSlash(CRM_Utils_File::addTrailingSlash($config->templateCompileDir) . $this->getLocale());

    if (!defined('SMARTY_DIR')) {
      // The absence of the global indicates Smarty5 - which is not a fan of bypassing the functions.
      // In theory this would work on all & it does run fun with Smarty2 but our tests
      // do something weird with loading Smarty so we have to head tests running Smarty2 off
      // at the pass.
      $this->setTemplateDir($template_dir);
      $this->setCompileDir($compile_dir);
    }
    else {
      $this->template_dir = $template_dir;
      $this->compile_dir = $compile_dir;
    }
    CRM_Utils_File::createDir($compile_dir);
    CRM_Utils_File::restrictAccess($compile_dir);

    // check and ensure it is writable
    // else we sometime suppress errors quietly and this results
    // in blank emails etc
    if (!is_writable($compile_dir)) {
      echo "CiviCRM does not have permission to write temp files in {$compile_dir}, Exiting";
      exit();
    }

    $this->use_sub_dirs = TRUE;

    $customPluginsDir = NULL;
    if (!empty($config->customPHPPathDir) || $config->customPHPPathDir === '0') {
      $customPluginsDir
        = $config->customPHPPathDir . DIRECTORY_SEPARATOR .
        'CRM' . DIRECTORY_SEPARATOR .
        'Core' . DIRECTORY_SEPARATOR .
        'Smarty' . DIRECTORY_SEPARATOR .
        'plugins' . DIRECTORY_SEPARATOR;
      if (!file_exists($customPluginsDir)) {
        $customPluginsDir = NULL;
      }
      if ($customPluginsDir) {
        $this->addPluginsDir($customPluginsDir);
      }
    }

    $pkgsDir = Civi::paths()->getVariable('civicrm.packages', 'path');
    // smarty3/4 have the define, fall back to smarty2. smarty5 deprecates plugins_dir - TBD.
    $smartyPluginsDir = defined('SMARTY_PLUGINS_DIR') ? SMARTY_PLUGINS_DIR : ($pkgsDir . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR . 'plugins');
    $corePluginsDir = __DIR__ . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
    $this->addPluginsDir($corePluginsDir);

    $this->compile_check = $this->isCheckSmartyIsCompiled();

    // add the session and the config here
    $session = CRM_Core_Session::singleton();

    $this->assign('config', $config);
    $this->assign('session', $session);
    $this->assign('debugging', [
      'smartyDebug' => CRM_Utils_Request::retrieveValue('smartyDebug', 'Integer'),
      'sessionReset' => CRM_Utils_Request::retrieveValue('sessionReset', 'Integer'),
      'sessionDebug' => CRM_Utils_Request::retrieveValue('sessionDebug', 'Integer'),
      'directoryCleanup' => CRM_Utils_Request::retrieveValue('directoryCleanup', 'Integer'),
      'cacheCleanup' => CRM_Utils_Request::retrieveValue('cacheCleanup', 'Integer'),
      'configReset' => CRM_Utils_Request::retrieveValue('configReset', 'Integer'),
    ]);
    $this->assign('snippet_type', CRM_Utils_Request::retrieveValue('snippet', 'String'));

    $tsLocale = CRM_Core_I18n::getLocale();
    $this->assign('tsLocale', $tsLocale);

    // CRM-7163 hack: we don’t display langSwitch on upgrades anyway
    if (!CRM_Core_Config::isUpgradeMode()) {
      $this->assign('langSwitch', CRM_Core_I18n::uiLanguages());
    }

    $this->loadFilter('pre', 'resetExtScope');
    $this->loadFilter('pre', 'htxtFilter');

    // Smarty5 can't use php functions unless they are registered.... Smarty4 gets noisy about it.
    $functionsForSmarty = [
      // In theory json_encode, count & implode no longer need to
      // be added as they are now more natively supported in smarty4, smarty5
      'json_encode',
      'count',
      'implode',
      // We use str_starts_with to check if a field is (e.g 'phone_' in profile presentation.
      'str_starts_with',
      // Trim is used on the extensions page.
      'trim',
      'mb_substr',
      'is_numeric',
      'array_key_exists',
      'strstr',
      'strpos',
    ];
    foreach ($functionsForSmarty as $function) {
      $this->registerPlugin('modifier', $function, $function);
    }

    $this->registerPlugin('modifier', 'call_user_func', [self::class, 'callUserFuncArray']);
    // This does not appear to be used & feels like the sort of approach that would be phased out.
    $this->assign('crmPermissions', new CRM_Core_Smarty_Permissions());

    if ($config->debug || str_contains(CIVICRM_UF_BASEURL, 'localhost') || CRM_Utils_Constant::value('CIVICRM_UF') === 'UnitTests') {
      $this->error_reporting = E_ALL;
    }
  }

  /**
   * Call a permitted function from the Smarty layer.
   *
   * In general calling functions from the Smarty layer is being made stricter in
   * Smarty - they need to be registered.
   *
   * We can't quite kill off call_user_func from the smarty layer yet but we
   * can deprecate using it to call anything other than the 3 known patterns.
   * In Smarty5 this will hard-fail, which is OK as Smarty5 is being phased in
   * and can err on the side of strictness, at least for now.
   *
   * @param callable $callable
   * @param mixed $args
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public static function callUserFuncArray(callable $callable, ...$args) {
    $permitted = [
      ['CRM_Campaign_BAO_Campaign', 'isComponentEnabled'],
      ['CRM_Case_BAO_Case', 'checkPermission'],
      ['CRM_Core_Permission', 'check'],
      ['CRM_Core_Permission', 'access'],
    ];
    if (!in_array($callable, $permitted)) {
      if (CRM_Core_Smarty::singleton()->getVersion() === 5) {
        throw new CRM_Core_Exception('unsupported function');
      }
      CRM_Core_Error::deprecatedWarning('unsupported function. call_user_func array is not generally supported in Smarty5 but we have transitional support for 2 functions that are in common use');
    }
    return call_user_func_array($callable, $args ?: []);
  }

  /**
   * Static instance provider.
   *
   * Method providing static instance of SmartTemplate, as
   * in Singleton pattern.
   *
   * @return \CRM_Core_Smarty
   */
  public static function &singleton() {
    if (static::$UNDEFINED_VALUE === NULL) {
      static::$UNDEFINED_VALUE = new stdClass();
    }
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Core_Smarty();
      self::$_singleton->initialize();

      self::registerStringResource();
    }
    return self::$_singleton;
  }

  /**
   * Handle smarty error in one off string.
   *
   * @param int $errorNumber
   * @param string $errorMessage
   *
   * @throws \CRM_Core_Exception
   */
  public function handleSmartyError(int $errorNumber, string $errorMessage): void {
    $event = new SmartyErrorEvent($errorNumber, $errorMessage);
    \Civi::dispatcher()->dispatch('civi.smarty.error', $event);
    restore_error_handler();
    throw new \CRM_Core_Exception('Message was not parsed due to invalid smarty syntax : ' . $errorMessage);
  }

  /**
   * Ensure these variables are set to make it easier to access them without e-notice.
   *
   * @param array $variables
   */
  public function ensureVariablesAreAssigned(array $variables): void {
    foreach ($variables as $variable) {
      if (!isset($this->getTemplateVars()[$variable])) {
        $this->assign($variable);
      }
    }
  }

  /**
   * @deprecated
   * Directly apply self::setRequiredTemplateTabKeys to the tabHeader
   * variable
   */
  public function addExpectedTabHeaderKeys(): void {
    $tabs = $this->getTemplateVars('tabHeader');
    $tabs = self::setRequiredTabTemplateKeys($tabs);
    $this->assign('tabHeader', $tabs);
  }

  /**
   * Ensure an array of tabs has the required keys to be passed
   * to our Smarty tabs templates (TabHeader.tpl or Summary.tpl)
   */
  public static function setRequiredTabTemplateKeys(array $tabs): array {
    $defaults = [
      'class' => '',
      'extra' => '',
      'icon' => NULL,
      'count' => NULL,
      'hideCount' => FALSE,
      'template' => NULL,
      'active' => TRUE,
      'valid' => TRUE,
      // Afform tabs set the afform module and directive - NULL for non-afform tabs
      'module' => NULL,
      'directive' => NULL,
    ];

    foreach ($tabs as $i => $tab) {
      if (empty($tab['url'])) {
        $tab['url'] = $tab['link'] ?? '';
      }
      $tabs[$i] = array_merge($defaults, (array) $tab);
    }
    return $tabs;
  }

  /**
   * Fetch a template (while using certain variables)
   *
   * @param string $resource_name
   * @param array $vars
   *   (string $name => mixed $value) variables to export to Smarty.
   * @throws Exception
   * @return bool|mixed|string
   */
  public function fetchWith($resource_name, $vars) {
    $this->pushScope($vars);
    try {
      $result = $this->fetch($resource_name);
    }
    catch (Exception $e) {
      // simulate try { ... } finally { ... }
      $this->popScope();
      throw $e;
    }
    $this->popScope();
    return $result;
  }

  /**
   * @param string $name
   * @param $value
   */
  public function appendValue($name, $value) {
    $currentValue = $this->getTemplateVars($name);
    if (!$currentValue) {
      $this->assign($name, $value);
    }
    else {
      if (strpos($currentValue, $value) === FALSE) {
        $this->assign($name, $currentValue . $value);
      }
    }
  }

  /**
   * Clear template variables, except session or config.
   *
   * Also the debugging variable because during test runs initialize() is only
   * called once at the start but the var gets indirectly accessed by a couple
   * tests that test forms.
   *
   * @return void
   */
  public function clearTemplateVars(): void {
    foreach (array_keys($this->getTemplateVars()) as $key) {
      if ($key === 'config' || $key === 'session' || $key === 'debugging') {
        continue;
      }
      $this->clearAssign($key);
    }
  }

  public static function registerStringResource() {
    if (method_exists('Smarty', 'register_resource')) {
      require_once 'CRM/Core/Smarty/resources/String.php';
      civicrm_smarty_register_string_resource();
    }
  }

  /**
   * Temporarily assign a list of variables.
   *
   * ```
   * $smarty->pushScope(array(
   *   'first_name' => 'Alice',
   *   'last_name' => 'roberts',
   * ));
   * $html = $smarty->fetch('view-contact.tpl');
   * $smarty->popScope();
   * ```
   *
   * @param array $vars
   *   (string $name => mixed $value).
   * @return CRM_Core_Smarty
   * @see popScope
   */
  public function pushScope($vars) {
    $oldVars = $this->getTemplateVars();
    $backupFrame = [];
    foreach ($vars as $key => $value) {
      $backupFrame[$key] = array_key_exists($key, $oldVars) ? $oldVars[$key] : static::$UNDEFINED_VALUE;
    }
    $this->backupFrames[] = $backupFrame;

    $this->assignAll($vars);

    return $this;
  }

  /**
   * Remove any values that were previously pushed.
   *
   * @return CRM_Core_Smarty
   * @see pushScope
   */
  public function popScope() {
    $this->assignAll(array_pop($this->backupFrames));
    return $this;
  }

  /**
   * @param array $vars
   *   (string $name => mixed $value).
   * @return CRM_Core_Smarty
   */
  public function assignAll($vars) {
    foreach ($vars as $key => $value) {
      if ($value !== static::$UNDEFINED_VALUE) {
        $this->assign($key, $value);
      }
      else {
        $this->clearAssign($key);
      }
    }
    return $this;
  }

  /**
   * Get the locale for translation.
   *
   * @return string
   */
  private function getLocale() {
    $tsLocale = CRM_Core_I18n::getLocale();
    if (!empty($tsLocale)) {
      return $tsLocale;
    }

    $config = CRM_Core_Config::singleton();
    if (!empty($config->lcMessages)) {
      return $config->lcMessages;
    }

    return 'en_US';
  }

  /**
   * Get the compile_check value.
   *
   * @return bool
   */
  private function isCheckSmartyIsCompiled() {
    // check for define in civicrm.settings.php as FALSE, otherwise returns TRUE
    return CRM_Utils_Constant::value('CIVICRM_TEMPLATE_COMPILE_CHECK', TRUE);
  }

  /**
   * Smarty escape modifier plugin.
   *
   * This replaces the core smarty modifier and basically does a lot of
   * early-returning before calling the core function.
   *
   * It early returns on patterns that are common 'no-escape' patterns
   * in CiviCRM - this list can be honed over time.
   *
   * It also logs anything that is actually escaped. Since this only kicks
   * in when CIVICRM_SMARTY_DEFAULT_ESCAPE is defined it is ok to be aggressive
   * about logging as we mostly care about developers using it at this stage.
   *
   * Note we don't actually use 'htmlall' anywhere in our tpl layer yet so
   * anything coming in with this be happening because of the default modifier.
   *
   * Also note the right way to opt a field OUT of escaping is
   * ``{$fieldName nofilter}``
   * This should be used for fields with known html AND for fields where
   * we are doing empty or isset checks - as otherwise the value is passed for
   * escaping first so you still get an enotice for 'empty' or a fatal for 'isset'
   *
   * Type:     modifier<br>
   * Name:     escape<br>
   * Purpose:  Escape the string according to escapement type
   *
   * @link http://smarty.php.net/manual/en/language.modifier.escape.php
   *          escape (Smarty online manual)
   * @author   Monte Ohrt <monte at ohrt dot com>
   *
   * @param string $string
   * @param string $esc_type
   * @param string $char_set
   *
   * @return string
   */
  public static function escape($string, $esc_type = 'html', $char_set = 'UTF-8') {
    // CiviCRM variables are often arrays - just handle them.
    // The early return on booleans & numbers is mostly to prevent them being
    // logged as 'changed' when they are cast to a string.
    if (!is_scalar($string) || empty($string) || is_bool($string) || is_numeric($string) || $esc_type === 'none') {
      return $string;
    }
    if ($esc_type === 'htmlall') {
      // 'htmlall' is the nothing-specified default.
      // Don't escape things we think quickform added.
      if (strpos($string, '<input') === 0
        || strpos($string, '<select') === 0
        // Not handling as yet but these ones really should get some love.
        || strpos($string, '<label') === 0
        || strpos($string, '<button') === 0
        || strpos($string, '<span class="crm-frozen-field">') === 0
        || strpos($string, '<textarea') === 0

        // The ones below this point are hopefully here short term.
        || strpos($string, '<a') === 0
        // Message templates screen
        || strpos($string, '<span><a href') === 0
        // Not sure how big a pattern this is - used in Pledge view tab
        // not sure if it needs escaping
        || strpos($string, ' action="/civicrm/') === 0
        // eg. Tag edit page, civicrm/admin/financial/financialType/accounts?action=add&reset=1&aid=1
        || strpos($string, ' action="" method="post"') === 0
        // This seems to be urls...
        || strpos($string, '/civicrm/') === 0
        // Validation error message - eg. <span class="crm-error">Tournament Fees is a required field.</span>
        || strpos($string, '
    <span class="crm-error">') === 0
        // e.g from participant tab class="action-item" href=/civicrm/contact/view/participant?reset=1&amp;action=add&amp;cid=142&amp;context=participant
        || strpos($string, 'class="action-item" href=/civicrm/"') === 0
      ) {
        // Do not escape the above common patterns.
        return $string;
      }
    }

    $string = mb_convert_encoding($string, 'UTF-8', $char_set);
    $value = htmlentities($string, ENT_QUOTES, 'UTF-8');
    if ($value !== $string) {
      Civi::log('smarty')->debug('smarty escaping original {original}, escaped {escaped} type {type} charset {charset}', [
        'original' => $string,
        'escaped' => $value,
        'type' => $esc_type,
        'charset' => $char_set,
      ]);
    }
    return $value;
  }

  public function getVersion (): int {
    return static::findVersion();
  }

  public static function findVersion(): int {
    static $version;
    if ($version === NULL) {
      if (class_exists('Smarty\Smarty')) {
        $version = 5;
      }
      else {
        $class = new ReflectionClass('Smarty');
        $path = $class->getFileName();
        if (str_contains($path, 'smarty3')) {
          $version = 3;
        }
        elseif (str_contains($path, 'smarty4')) {
          $version = 4;
        }
        else {
          $version = 2;
        }
      }
    }
    return $version;

  }

}
