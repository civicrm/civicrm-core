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
   * @return SetupResponse
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
   * @return SetupResponse
   */
  public function runStart($method, $fields) {
    $checkInstalled = $this->setup->checkInstalled();
    if ($checkInstalled->isDatabaseInstalled() || $checkInstalled->isSettingInstalled()) {
      return $this->renderAlreadyInstalled();
    }

    /**
     * @var \Civi\Setup\Model $model
     */
    $model = $this->setup->getModel();

    $tplFile = $this->getResourcePath('installer.tpl.php');
    $tplVars = [
      'model' => $model,
      'reqs' => $this->setup->checkRequirements(),
    ];

    // $body = "<pre>" . htmlentities(print_r(['method' => $method, 'urls' => $this->urls, 'data' => $fields], 1)) . "</pre>";
    return $this->createPage(ts('CiviCRM Installer'), $this->render($tplFile, $tplVars));
  }

  /**
   * Perform the installation action.
   *
   * @param string $method
   *   Ex: 'GET' or 'POST'.
   * @param array $fields
   *   List of any HTTP GET/POST fields.
   * @return SetupResponse
   */
  public function runInstall($method, $fields) {
    $checkInstalled = $this->setup->checkInstalled();
    if ($checkInstalled->isDatabaseInstalled() || $checkInstalled->isSettingInstalled()) {
      return $this->renderAlreadyInstalled();
    }

    $reqs = $this->setup->checkRequirements();
    if (count($reqs->getErrors())) {
      return $this->runStart($method, $fields);
    }

    $this->setup->installFiles();
    $this->setup->installDatabase();
    return $this->renderFinished();
  }

  /**
   * Partially bootstrap Civi services (such as localization).
   */
  protected function boot($method, $fields) {
    /** @var \Civi\Setup\Model $model */
    $model = $this->setup->getModel();

    define('CIVICRM_UF', $model->cms);
    define('CIVICRM_TEMPLATE_COMPILEDIR', $model->templateCompilePath);
    define('CIVICRM_UF_BASEURL', $model->cmsBaseUrl);

    global $civicrm_root;
    $civicrm_root = $model->srcPath;

    // Set the Locale (required by CRM_Core_Config)
    global $tsLocale;
    $tsLocale = 'en_US';

    global $civicrm_paths;
    foreach ($model->paths as $pathVar => $pathValues) {
      foreach ($pathValues as $aspectName => $aspectValue) {
        if (in_array($aspectName, ['url', 'path'])) {
          $civicrm_paths[$pathVar][$aspectName] = $aspectValue;
        }
      }
    }

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

  /**
   * @param string $message
   * @param string $title
   * @return SetupResponse
   */
  public function createError($message, $title = 'Error') {
    return $this->createPage($title, sprintf('<h1>%s</h1>\n%s', htmlentities($title), htmlentities($message)));
  }

  /**
   * @param string $title
   * @param string $body
   * @return SetupResponse
   */
  public function createPage($title, $body) {
    /** @var \Civi\Setup\Model $model */
    $model = $this->setup->getModel();

    $r = new SetupResponse();
    $r->code = 200;
    $r->headers = [];
    $r->isComplete = FALSE;
    $r->title = $title;
    $r->body = $body;
    $r->assets = [
      ['type' => 'script-url', 'url' => $this->getUrl('jquery.js')],
      ['type' => 'script-url', 'url' => $this->urls['res'] . "jquery.setupui.js"],
      ['type' => 'script-code', 'code' => 'window.csj$ = jQuery.noConflict();'],
      ['type' => 'style-url', 'url' => $this->urls['res'] . "template.css"],
      ['type' => 'style-url', 'url' => $this->getUrl('font-awesome.css')],
    ];

    if (\CRM_Core_I18n::isLanguageRTL($model->lang)) {
      $r->assets[] = ['type' => 'style-url', 'url' => $this->urls['res'] . "template-rtl.css"];
    }

    return $r;
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
    $_tpl_params = array_merge($this->getCommonTplVars(), $_tpl_params);
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
    return $this->urls[$name] ?? NULL;
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
   *   `<button type="submit" name="civisetup[action][Foo]">Do the foo</button>`
   * @param string $default
   *   The action-name to return if no other action is identified.
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

  private function renderAlreadyInstalled() {
    // return $this->createError("CiviCRM is already installed");
    return $this->renderFinished();
  }

  /**
   * @return SetupResponse
   */
  private function renderFinished() {
    $m = $this->setup->getModel();
    $tplFile = $this->getResourcePath('finished.' . $m->cms . '.php');
    if (file_exists($tplFile)) {
      return $this->createPage(ts('CiviCRM Installed'), $this->render($tplFile));
    }
    else {
      return $this->createError("Installation succeeded. However, the final page ($tplFile) was not available.");
    }
  }

  /**
   * @return array
   */
  private function getCommonTplVars() {
    return [
      'ctrl' => $this,
      'civicrm_version' => \CRM_Utils_System::version(),
      'installURLPath' => $this->urls['res'],
    ];
  }

}
