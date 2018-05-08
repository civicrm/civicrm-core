<?php
namespace Civi\Setup\UI;

use Civi\Setup\UI\Event\UIBootEvent;

class SetupController implements SetupControllerInterface {

  const PREFIX = 'civisetup';

  /**
   * @var \Civi\Setup
   */
  protected $setup;

  /**
   * @var array
   *   Some mix of the following:
   *     - res: The base URL for loading resource files (images/javascripts) for this
   *       project. Includes trailing slash.
   *     - ctrl: The URL of this setup controller. May be used for POST-backs.
   */
  protected $urls;

  /**
   * @var array
   *   A list of blocks to display on the setup page. Each item has:
   *    - file: string, relative path
   *    - class: string, a space-delimited list of CSS classes
   *    - is_active: bool
   *
   * Note: When rendering a block, content of the block's definition
   * will be available as `$_tpl_block`. For example, `$_tpl_block['is_active']`
   * would be the same boolean.
   */
  public $blocks;

  /**
   * SetupController constructor.
   * @param \Civi\Setup $setup
   */
  public function __construct(\Civi\Setup $setup) {
    $this->setup = $setup;
    $this->blocks = array();
  }

  /**
   * @param string $method
   *   Ex: 'GET' or 'POST'.
   * @param array $fields
   *   List of any HTTP GET/POST fields.
   * @return array
   *   The HTTP headers and response text.
   *   [0 => array $headers, 1 => string $body].
   */
  public function run($method, $fields = array()) {
    $this->setup->getDispatcher()->dispatch('civi.setupui.run', new UIBootEvent($this, $method, $fields));
    if (!$this->setup->checkAuthorized()->isAuthorized()) {
      return $this->createError("Not authorized to perform installation");
    }

    $this->boot($method, $fields);
    $action = $this->parseAction($fields, 'Start');
    $func = 'run' . $action;
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $action) || !is_callable([$this, $func])) {
      return $this->createError("Invalid action");
    }
    return call_user_func([$this, $func], $method, $fields);
  }

  /**
   * Run the main installer page.
   *
   * @param string $method
   *   Ex: 'GET' or 'POST'.
   * @param array $fields
   *   List of any HTTP GET/POST fields.
   * @return array
   *   The HTTP headers and response text.
   *   [0 => array $headers, 1 => string $body].
   */
  public function runStart($method, $fields) {
    $checkInstalled = $this->setup->checkInstalled();
    if ($checkInstalled->isDatabaseInstalled() || $checkInstalled->isSettingInstalled()) {
      return $this->createError("CiviCRM is already installed");
    }

    /**
     * @var \Civi\Setup\Model $model
     */
    $model = $this->setup->getModel();

    $tplFile = $this->getResourcePath('template.php');
    $tplVars = [
      'ctrl' => $this,
      'civicrm_version' => \CRM_Utils_System::version(),
      'installURLPath' => $this->urls['res'],
      'short_lang_code' => \CRM_Core_I18n_PseudoConstant::shortForLong($GLOBALS['tsLocale']),
      'text_direction' => (\CRM_Core_I18n::isLanguageRTL($GLOBALS['tsLocale']) ? 'rtl' : 'ltr'),
      'model' => $model,
      'reqs' => $this->setup->checkRequirements(),
    ];

    // $body = "<pre>" . htmlentities(print_r(['method' => $method, 'urls' => $this->urls, 'data' => $fields], 1)) . "</pre>";
    $body = $this->render($tplFile, $tplVars);

    return array(array(), $body);
  }

  /**
   * Perform the installation action.
   *
   * @param string $method
   *   Ex: 'GET' or 'POST'.
   * @param array $fields
   *   List of any HTTP GET/POST fields.
   * @return array
   *   The HTTP headers and response text.
   *   [0 => array $headers, 1 => string $body].
   */
  public function runInstall($method, $fields) {
    $checkInstalled = $this->setup->checkInstalled();
    if ($checkInstalled->isDatabaseInstalled() || $checkInstalled->isSettingInstalled()) {
      return $this->createError("CiviCRM is already installed");
    }

    $reqs = $this->setup->checkRequirements();
    if (count($reqs->getErrors())) {
      return $this->runStart($method, $fields);
    }

    $this->setup->installFiles();
    $this->setup->installDatabase();

    $m = $this->setup->getModel();
    $tplFile = $this->getResourcePath('finished.' . $m->cms . '.php');
    if (file_exists($tplFile)) {
      $tplVars = array();
      return array(array(), $this->render($tplFile, $tplVars));
    }
    else {
      return $this->createError("Installation succeeded. However, the final page ($tplFile) was not available.");
    }
  }

  /**
   * Partially bootstrap Civi services (such as localization).
   */
  protected function boot($method, $fields) {
    $model = $this->setup->getModel();

    define('CIVICRM_UF', $model->cms);

    // Set the Locale (required by CRM_Core_Config)
    global $tsLocale;
    $tsLocale = 'en_US';

    // CRM-16801 This validates that lang is valid by looking in $langs.
    // NB: the variable is initial a $_REQUEST for the initial page reload,
    // then becomes a $_POST when the installation form is submitted.
    $langs = $model->getField('lang', 'options');
    if (array_key_exists('lang', $fields)) {
      $model->lang = $fields['lang'];
    }
    if ($model->lang and isset($langs[$model->lang])) {
      $tsLocale = $model->lang;
    }

    \CRM_Core_Config::singleton(FALSE);
    $GLOBALS['civicrm_default_error_scope'] = NULL;

    // The translation files are in the parent directory (l10n)
    \CRM_Core_I18n::singleton();

    $this->setup->getDispatcher()->dispatch('civi.setupui.boot', new UIBootEvent($this, $method, $fields));
  }

  public function createError($message, $title = 'Error') {
    return array(array(), $this->render($this->getResourcePath('error.html'), [
      'errorTitle' => htmlentities($title),
      'errorMsg' => htmlentities($message),
      'installURLPath' => $this->urls['res'],
    ]));
  }

  /**
   * Render a *.php template file.
   *
   * @param string $_tpl_file
   *   The path to the file.
   * @param array $_tpl_params
   *   Any variables that should be exported to the scope of the template.
   * @return string
   */
  public function render($_tpl_file, $_tpl_params = array()) {
    extract($_tpl_params);
    ob_start();
    require $_tpl_file;
    return ob_get_clean();
  }

  public function getResourcePath($parts) {
    $parts = (array) $parts;
    array_unshift($parts, 'res');
    array_unshift($parts, $this->setup->getModel()->setupPath);
    return implode(DIRECTORY_SEPARATOR, $parts);
  }

  public function getUrl($name) {
    return isset($this->urls[$name]) ? $this->urls[$name] : NULL;
  }

  /**
   * @inheritdoc
   */
  public function setUrls($urls) {
    foreach ($urls as $k => $v) {
      $this->urls[$k] = $v;
    }
    return $this;
  }

  /**
   * Given an HTML submission, determine the name.
   *
   * @param array $fields
   *   HTTP inputs -- e.g. with a form element like this:
   *   `<input type="submit" name="civisetup[action][Foo]" value="Do the foo">`
   * @return string
   *   The name of the action.
   *   Ex: 'Foo'.
   */
  protected function parseAction($fields, $default) {
    if (empty($fields[self::PREFIX]['action'])) {
      return $default;
    }
    else {
      if (is_array($fields[self::PREFIX]['action'])) {
        foreach ($fields[self::PREFIX]['action'] as $name => $label) {
          return $name;
        }
      }
      elseif (is_string($fields[self::PREFIX]['action'])) {
        return $fields[self::PREFIX]['action'];
      }
    }
    return NULL;
  }

  public function renderBlocks($_tpl_params) {
    $buf = '';

    // Cleanup - Ensure 'name' is present.
    foreach (array_keys($this->blocks) as $name) {
      $this->blocks[$name]['name'] = $name;
    }

    // Sort by weight+name.
    uasort($this->blocks, function($a, $b) {
      if ($a['weight'] != $b['weight']) {
        return $a['weight'] - $b['weight'];
      }
      return strcmp($a['name'], $b['name']);
    });

    foreach ($this->blocks as $name => $block) {
      if (!$block['is_active']) {
        continue;
      }
      $buf .= sprintf("<div class=\"civicrm-setup-block-%s %s\">%s</div>",
        $name,
        $block['class'],
        $this->render(
          $block['file'],
          $_tpl_params + array('_tpl_block' => $block)
        )
      );
    }
    return $buf;
  }

  /**
   * @return \Civi\Setup
   */
  public function getSetup() {
    return $this->setup;
  }

}
