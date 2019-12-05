<?php

/**
 * Generate language files and classes
 */
class CRM_Core_CodeGen_I18n extends CRM_Core_CodeGen_BaseTask {

  public function run() {
    $this->generateInstallLangs();
    $this->generateSchemaStructure();
  }

  public function generateInstallLangs() {
    // CRM-7161: generate install/langs.php from the languages template
    // grep it for enabled languages and create a 'xx_YY' => 'Language name' $langs mapping
    $matches = [];
    preg_match_all('/, 1, \'([a-z][a-z]_[A-Z][A-Z])\', \'..\', \{localize\}\'\{ts escape="sql"\}(.+)\{\/ts\}\'\{\/localize\}, /', file_get_contents('templates/languages.tpl'), $matches);
    $langs = [];
    for ($i = 0; $i < count($matches[0]); $i++) {
      $langs[$matches[1][$i]] = $matches[2][$i];
    }
    file_put_contents('../install/langs.php', "<?php \$langs = " . var_export($langs, TRUE) . ";");
  }

  public function generateSchemaStructure() {
    echo "Generating CRM_Core_I18n_SchemaStructure...\n";
    $columns = [];
    $indices = [];
    $widgets = [];
    foreach ($this->tables as $table) {
      if ($table['localizable']) {
        $columns[$table['name']] = [];
        $widgets[$table['name']] = [];
      }
      else {
        continue;
      }
      foreach ($table['fields'] as $field) {
        $required = $field['required'] ? ' NOT NULL' : '';
        $default = $field['default'] ? ' DEFAULT ' . $field['default'] : '';
        $comment = $field['comment'] ? " COMMENT '" . $field['comment'] . "'" : '';
        if ($field['localizable']) {
          $columns[$table['name']][$field['name']] = $field['sqlType'] . $required . $default . $comment;
          $widgets[$table['name']][$field['name']] = $field['widget'];
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

    $template = new CRM_Core_CodeGen_Util_Template('php', FALSE);

    $template->assign('columns', $columns);
    $template->assign('indices', $indices);
    $template->assign('widgets', $widgets);

    $template->run('schema_structure.tpl', $this->config->phpCodePath . "/CRM/Core/I18n/SchemaStructure.php");
  }

}
