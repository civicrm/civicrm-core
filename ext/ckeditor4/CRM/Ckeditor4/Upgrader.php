<?php
use CRM_Ckeditor4_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Ckeditor4_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Install extension.
   */
  public function install() {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'wysiwyg_editor',
      'label' => E::ts('CKEditor 4'),
      'name' => 'CKEditor',
      'is_default' => 1,
    ]);
  }

  /**
   * Uninstall CKEditor settings.
   */
  public function uninstall() {
    $domains = civicrm_api3('Domain', 'get', ['options' => ['limit' => 0]])['values'];
    foreach ($domains as $domain) {
      $currentSetting = \Civi::settings($domain['id'])->get('editor_id');
      if ($currentSetting === 'CKEditor') {
        \Civi::settings($domain['id'])->set('editor_id', 'Textarea');
      }
    }
    civicrm_api3('OptionValue', 'get', ['name' => 'CKEditor', 'api.option_value.delete' => ['id' => "\$value.id"]]);
  }

  /**
   * Add in Upgrade Step to Ensure that warning message about version doesn't show by default
   *
   * @see dev/core#6839
   */
  public function upgrade_1000(): bool {
    $presets = CRM_Core_OptionGroup::values('wysiwyg_presets', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');
    foreach (array_keys($presets) as $name) {
      $configName = CRM_Ckeditor4_Form_CKEditorConfig::getConfigFile($name);
      if ($configName) {
        $config = file_get_contents($configName);
        if (!str_contains($config, 'config.versionCheck')) {
          $config = $config = substr($config, 0, -3) . " config.versionCheck = false;\n};";
          CRM_Ckeditor4_Form_CKEditorConfig::saveConfigFile($name, $config);
        }
      }
    }
    return TRUE;
  }

}
