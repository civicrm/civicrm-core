<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Page for configuring CKEditor options.
 *
 * Note that while this is implemented as a CRM_Core_Page, it is actually a form.
 * Because the form needs to be submitted and refreshed via javascript, it seemed like
 * Quickform and CRM_Core_Form/Controller might get in the way.
 */
class CRM_Admin_Page_CKEditorConfig extends CRM_Core_Page {

  const CONFIG_FILEPATH = '[civicrm.files]/persist/crm-ckeditor-';

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
    'filebrowserBrowseUrl',
    'filebrowserImageBrowseUrl',
    'filebrowserFlashBrowseUrl',
    'filebrowserUploadUrl',
    'filebrowserImageUploadUrl',
    'filebrowserFlashUploadUrl',
  ];

  public $preset;

  /**
   * Run page.
   *
   * @return string
   */
  public function run() {
    $this->preset = CRM_Utils_Array::value('preset', $_REQUEST, 'default');

    // If the form was submitted, take appropriate action.
    if (!empty($_POST['revert'])) {
      self::deleteConfigFile($this->preset);
      self::setConfigDefault();
    }
    elseif (!empty($_POST['config'])) {
      $this->save($_POST);
    }

    $settings = $this->getConfigSettings();

    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'bower_components/ckeditor/ckeditor.js', 0, 'page-header')
      ->addScriptFile('civicrm', 'bower_components/ckeditor/samples/toolbarconfigurator/js/fulltoolbareditor.js', 1)
      ->addScriptFile('civicrm', 'bower_components/ckeditor/samples/toolbarconfigurator/js/abstracttoolbarmodifier.js', 2)
      ->addScriptFile('civicrm', 'bower_components/ckeditor/samples/toolbarconfigurator/js/toolbarmodifier.js', 3)
      ->addScriptFile('civicrm', 'js/wysiwyg/admin.ckeditor-configurator.js', 10)
      ->addStyleFile('civicrm', 'bower_components/ckeditor/samples/toolbarconfigurator/css/fontello.css')
      ->addStyleFile('civicrm', 'bower_components/ckeditor/samples/css/samples.css')
      ->addVars('ckConfig', [
        'plugins' => array_values($this->getCKPlugins()),
        'blacklist' => $this->blackList,
        'settings' => $settings,
      ]);

    $configUrl = self::getConfigUrl($this->preset) ?: self::getConfigUrl('default');

    $this->assign('preset', $this->preset);
    $this->assign('presets', CRM_Core_OptionGroup::values('wysiwyg_presets', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name'));
    $this->assign('skins', $this->getCKSkins());
    $this->assign('skin', CRM_Utils_Array::value('skin', $settings));
    $this->assign('extraPlugins', CRM_Utils_Array::value('extraPlugins', $settings));
    $this->assign('configUrl', $configUrl);
    $this->assign('revertConfirm', htmlspecialchars(ts('Are you sure you want to revert all changes?', ['escape' => 'js'])));

    CRM_Utils_System::appendBreadCrumb([
      [
        'url' => CRM_Utils_System::url('civicrm/admin/setting/preferences/display', 'reset=1'),
        'title' => ts('Display Preferences'),
      ],
    ]);

    return parent::run();
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

    // Use all params starting with config_
    foreach ($params as $key => $val) {
      $val = trim($val);
      if (strpos($key, 'config_') === 0 && strlen($val)) {
        if ($val != 'true' && $val != 'false' && $val != 'null' && $val[0] != '{' && $val[0] != '[' && !is_numeric($val)) {
          $val = json_encode($val, JSON_UNESCAPED_SLASHES);
        }
        elseif ($val[0] == '{' || $val[0] == '[') {
          if (!is_array(json_decode($val, TRUE))) {
            // Invalid JSON. Do not save.
            continue;
          }
        }
        $pos = strrpos($config, '};');
        $key = preg_replace('/^config_/', 'config.', $key);
        $setting = "\n\t{$key} = {$val};\n";
        $config = substr_replace($config, $setting, $pos, 0);
      }
    }
    self::saveConfigFile($this->preset, $config);
    if (!empty($params['save'])) {
      CRM_Core_Session::setStatus(ts("You may need to clear your browser's cache to see the changes in CiviCRM."), ts('CKEditor Saved'), 'success');
    }
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
      $dir = CRM_Utils_file::addTrailingSlash($dir, '/');
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
    $file = self::getConfigFile($this->preset) ?: self::getConfigFile('default');
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
      $config = self::fileHeader() . "CKEDITOR.editorConfig = function( config ) {\n\tconfig.allowedContent = true;\n};\n";
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
