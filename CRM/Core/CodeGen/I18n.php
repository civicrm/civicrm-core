<?php

/**
 * Generate language files and classes
 */
class CRM_Core_CodeGen_I18n extends CRM_Core_CodeGen_BaseTask {
  function run() {
    $this->generateInstallLangs();
    $this->generateSchemaStructure();
  }

  function generateInstallLangs() {
    // CRM-7161: generate install/langs.php from the languages template
    // grep it for enabled languages and create a 'xx_YY' => 'Language name' $langs mapping
    $matches = array();
    $languages_file_path = CRM_Utils_Path::join($this->config->civicrm_root_path, 'xml', 'templates', 'languages.tpl');
    preg_match_all('/, 1, \'([a-z][a-z]_[A-Z][A-Z])\', \'..\', \{localize\}\'\{ts escape="sql"\}(.+)\{\/ts\}\'\{\/localize\}, /', file_get_contents($languages_file_path), $matches);
    $langs = array();
    for ($i = 0; $i < count($matches[0]); $i++) {
      $langs[$matches[1][$i]] = $matches[2][$i];
    }
    $install_file_path = CRM_Utils_Path::join($this->config->civicrm_root_path, 'install', 'langs.php');
    file_put_contents($install_file_path, "<?php \$langs = " . var_export($langs, true) . ";");
  }

  function generateSchemaStructure() {
    $columns = array();
    $indices = array();
    foreach ($this->config->doctrine->dao_metadata as $table) {
      if ($table['localizable']) {
        $columns[$table['name']] = array();
      }
      else {
        continue;
      }
      foreach ($table['fields'] as $field) {
        if ($field['localizable']) {
          $columns[$table['name']][$field['name']] = $field['sqlType'];
        }
      }
      if (isset($table['index'])) {
        foreach ($table['index'] as $index) {
          if ($index['localizable']) {
            $indices[$table['name']][$index['name']] = $index;
          }
        }
      }
    }

    $template = new CRM_Core_CodeGen_Util_Template($this->config, 'php');

    $template->assign('columns', $columns);
    $template->assign('indices', $indices);
    $template->run('schema_structure.tpl', $this->config->phpCodePath . "/CRM/Core/I18n/SchemaStructure.php");
  }
}
