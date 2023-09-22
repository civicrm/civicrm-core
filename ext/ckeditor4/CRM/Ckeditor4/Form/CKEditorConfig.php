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

use CRM_Ckeditor4_ExtensionUtil as E;

/**
 * Form for configuring CKEditor options.
 */
class CRM_Ckeditor4_Form_CKEditorConfig extends CRM_Core_Form {

  const CONFIG_FILEPATH = '[civicrm.files]/persist/crm-ckeditor-';

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Settings that cannot be configured in "advanced options"
   *
   * @var array
   */
  public $blackList = [
    'on',
    'skin',
    'extraPlugins',
    'toolbarGroups',
    'removeButtons',
    'customConfig',
    'filebrowserBrowseUrl',
    'filebrowserImageBrowseUrl',
    'filebrowserFlashBrowseUrl',
    'filebrowserUploadUrl',
    'filebrowserImageUploadUrl',
    'filebrowserFlashUploadUrl',
  ];

  /**
   * Prepare form
   */
  public function preProcess() {
    CRM_Utils_Request::retrieve('preset', 'String', $this, FALSE, 'default', 'GET');

    CRM_Utils_System::appendBreadCrumb([
      [
        'url' => CRM_Utils_System::url('civicrm/admin/setting/preferences/display', 'reset=1'),
        'title' => ts('Display Preferences'),
      ],
    ]);

    // Initial build
    if (empty($_POST['qfKey'])) {
      $this->addResources();
    }
  }

  /**
   * Add resources during initial build or rebuild
   *
   * @throws CRM_Core_Exception
   */
  public function addResources() {
    $settings = $this->getConfigSettings();

    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'bower_components/ckeditor/ckeditor.js', 0, 'page-header')
      ->addScriptFile('civicrm', 'bower_components/ckeditor/samples/toolbarconfigurator/js/fulltoolbareditor.js', 1)
      ->addScriptFile('civicrm', 'bower_components/ckeditor/samples/toolbarconfigurator/js/abstracttoolbarmodifier.js', 2)
      ->addScriptFile('civicrm', 'bower_components/ckeditor/samples/toolbarconfigurator/js/toolbarmodifier.js', 3)
      ->addScriptFile('ckeditor4', 'js/admin.ckeditor-configurator.js', 10)
      ->addStyleFile('civicrm', 'bower_components/ckeditor/samples/toolbarconfigurator/css/fontello.css')
      ->addStyleFile('civicrm', 'bower_components/ckeditor/samples/css/samples.css')
      ->addVars('ckConfig', [
        'plugins' => array_values($this->getCKPlugins()),
        'blacklist' => $this->blackList,
        'settings' => $settings,
      ]);

    $configUrl = self::getConfigUrl($this->get('preset')) ?: self::getConfigUrl('default');

    $this->assign('preset', $this->get('preset'));
    $this->assign('presets', CRM_Core_OptionGroup::values('wysiwyg_presets', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name'));
    $this->assign('skins', $this->getCKSkins());
    $this->assign('skin', CRM_Utils_Array::value('skin', $settings));
    $this->assign('extraPlugins', CRM_Utils_Array::value('extraPlugins', $settings));
    $this->assign('configUrl', $configUrl);
  }

  /**
   * Build form
   */
  public function buildQuickForm() {
    $revertConfirm = json_encode(ts('Are you sure you want to revert all changes?'));
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
      ],
      // Hidden button used to refresh form
      [
        'type' => 'submit',
        'class' => 'hiddenElement',
        'name' => ts('Save'),
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
      [
        'type' => 'refresh',
        'name' => ts('Revert to Default'),
        'icon' => 'fa-undo',
        'js' => ['onclick' => "return confirm($revertConfirm);"],
      ],
    ]);
  }

  /**
   * Handle form submission
   */
  public function postProcess() {
    if (!empty($_POST[$this->getButtonName('refresh')])) {
      self::deleteConfigFile($this->get('preset'));
      self::setConfigDefault();
    }
    else {
      if (!empty($_POST[$this->getButtonName('next')])) {
        $this->save($_POST);
        CRM_Core_Session::setStatus(ts("You may need to clear your browser's cache to see the changes in CiviCRM."), ts('CKEditor Saved'), 'success');
      }
      // The "submit" hidden button saves but does not redirect
      if (!empty($_POST[$this->getButtonName('submit')])) {
        $this->save($_POST);
        $this->addResources();
      }
      else {
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/ckeditor', ['reset' => 1]));
      }
    }
  }

  /**
   * Generate the config js file based on posted data.
   *
   * @param array $params
   */
  public function save($params) {
    $config = self::fileHeader()
      // Standardize line-endings
      . preg_replace('~\R~u', "\n", $params['config']);

    // Generate a whitelist of allowed config params
    $allOptions = json_decode(file_get_contents(E::path('js/ck-options.json')), TRUE);
    // These two aren't really blacklisted they're just in a different part of the form
    $blackList = array_diff($this->blackList, ['skin', 'extraPlugins']);
    // All options minus blacklist = whitelist
    $whiteList = array_diff(array_column($allOptions, 'id'), $blackList);

    // Save whitelisted params starting with config_
    foreach ($params as $key => $val) {
      $val = trim($val);
      if (strpos($key, 'config_') === 0 && strlen($val) && in_array(substr($key, 7), $whiteList)) {
        if ($val != 'true' && $val != 'false' && $val != 'null' && $val[0] != '{' && $val[0] != '[' && !is_numeric($val)) {
          $val = '"' . $val . '"';
        }
        try {
          $val = CRM_Utils_JS::encode(CRM_Utils_JS::decode($val, TRUE));
          $pos = strrpos($config, '};');
          $key = preg_replace('/^config_/', 'config.', $key);
          $setting = "\n\t{$key} = {$val};\n";
          $config = substr_replace($config, $setting, $pos, 0);
        }
        catch (CRM_Core_Exception $e) {
          CRM_Core_Session::setStatus(ts("Error saving %1.", [1 => $key]), ts('Invalid Value'), 'error');
        }
      }
    }
    self::saveConfigFile($this->get('preset'), $config);
  }

  /**
   * Get available CKEditor plugin list.
   *
   * @return array
   */
  private function getCKPlugins() {
    $plugins = [];
    $pluginDir = Civi::paths()->getPath('[civicrm.root]/bower_components/ckeditor/plugins');

    foreach (glob($pluginDir . '/*', GLOB_ONLYDIR) as $dir) {
      $dir = rtrim(str_replace('\\', '/', $dir), '/');
      $name = substr($dir, strrpos($dir, '/') + 1);
      $dir = CRM_Utils_File::addTrailingSlash($dir, '/');
      if (is_file($dir . 'plugin.js')) {
        $plugins[$name] = [
          'id' => $name,
          'text' => ucfirst($name),
          'icon' => NULL,
        ];
        if (is_dir($dir . "icons")) {
          if (is_file($dir . "icons/$name.png")) {
            $plugins[$name]['icon'] = "bower_components/ckeditor/plugins/$name/icons/$name.png";
          }
          elseif (glob($dir . "icons/*.png")) {
            $icon = CRM_Utils_Array::first(glob($dir . "icons/*.png"));
            $icon = rtrim(str_replace('\\', '/', $icon), '/');
            $plugins[$name]['icon'] = "bower_components/ckeditor/plugins/$name/icons/" . substr($icon, strrpos($icon, '/') + 1);
          }
        }
      }
    }

    return $plugins;
  }

  /**
   * Get available CKEditor skins.
   *
   * @return array
   */
  private function getCKSkins() {
    $skins = [];
    $skinDir = Civi::paths()->getPath('[civicrm.root]/bower_components/ckeditor/skins');
    foreach (glob($skinDir . '/*', GLOB_ONLYDIR) as $dir) {
      $dir = rtrim(str_replace('\\', '/', $dir), '/');
      $skins[] = substr($dir, strrpos($dir, '/') + 1);
    }
    return $skins;
  }

  /**
   * @return array
   */
  private function getConfigSettings() {
    $matches = $result = [];
    $file = self::getConfigFile($this->get('preset')) ?: self::getConfigFile('default');
    $result['skin'] = 'moono';
    if ($file) {
      $contents = file_get_contents($file);
      preg_match_all("/\sconfig\.(\w+)\s?=\s?([^;]*);/", $contents, $matches);
      foreach ($matches[1] as $i => $match) {
        $result[$match] = trim($matches[2][$i], ' "\'');
      }
    }
    return $result;
  }

  /**
   * @param string $preset
   *   Omit to get an array of all presets
   * @return array|null|string
   */
  public static function getConfigUrl($preset = NULL) {
    $items = [];
    $presets = CRM_Core_OptionGroup::values('wysiwyg_presets', FALSE, FALSE, FALSE, NULL, 'name');
    foreach ($presets as $key => $name) {
      if (self::getConfigFile($name)) {
        $items[$name] = Civi::paths()->getUrl(self::CONFIG_FILEPATH . $name . '.js', 'absolute');
      }
    }
    return $preset ? CRM_Utils_Array::value($preset, $items) : $items;
  }

  /**
   * @param string $preset
   *
   * @return null|string
   */
  public static function getConfigFile($preset = 'default') {
    $fileName = Civi::paths()->getPath(self::CONFIG_FILEPATH . $preset . '.js');
    return is_file($fileName) ? $fileName : NULL;
  }

  /**
   * @param string $preset
   * @param string $contents
   */
  public static function saveConfigFile($preset, $contents) {
    $file = Civi::paths()->getPath(self::CONFIG_FILEPATH . $preset . '.js');
    file_put_contents($file, $contents);
  }

  /**
   * Delete config file.
   */
  public static function deleteConfigFile($preset) {
    $file = self::getConfigFile($preset);
    if ($file) {
      unlink($file);
    }
  }

  /**
   * Create default config file if it doesn't exist
   */
  public static function setConfigDefault() {
    if (!self::getConfigFile()) {
      $config = self::fileHeader() . "CKEDITOR.editorConfig = function( config ) {\n\tconfig.allowedContent = true;\n\tconfig.entities = false;\n};\n";
      // Make sure directories exist
      if (!is_dir(Civi::paths()->getPath('[civicrm.files]/persist'))) {
        mkdir(Civi::paths()->getPath('[civicrm.files]/persist'));
      }
      $newFileName = Civi::paths()->getPath(self::CONFIG_FILEPATH . 'default.js');
      file_put_contents($newFileName, $config);
    }
  }

  /**
   * @return string
   */
  public static function fileHeader() {
    return "/**\n"
    . " * CKEditor config file auto-generated by CiviCRM (" . date('Y-m-d H:i:s') . ").\n"
    . " *\n"
    . " * Note: This file will be overwritten if settings are modified at:\n"
    . " * @link " . CRM_Utils_System::url('civicrm/admin/ckeditor', NULL, TRUE, NULL, FALSE) . "\n"
    . " */\n";
  }

}
