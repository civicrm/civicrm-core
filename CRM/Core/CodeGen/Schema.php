<?php

/**
 * Create SQL files to create and populate a new schema.
 */
class CRM_Core_CodeGen_Schema extends CRM_Core_CodeGen_BaseTask {
  function __construct() {
    parent::__construct();
    $this->locales = $this->findLocales();
  }

  function run() {
    CRM_Core_CodeGen_Util_File::createDir($this->config->sqlCodePath);

    $this->generateCreateSql();
    $this->generateDropSql();

    $this->generateLocaleDataSql();

    // also create the archive tables
    // $this->generateCreateSql('civicrm_archive.mysql' );
    // $this->generateDropSql('civicrm_archive_drop.mysql');

    $this->generateNavigation();
    $this->generateSample();
  }

  function generateCreateSql($fileName = 'civicrm.mysql') {
    $schema_tool = new \Doctrine\ORM\Tools\SchemaTool($this->config->doctrine->entity_manager);
    $sql = $schema_tool->getCreateSchemaSql($this->config->doctrine->metadata);
    $sql_file_path = CRM_Utils_Path::join($this->config->sqlCodePath, $fileName);
    $sql_file = fopen($sql_file_path, 'w');
    foreach ($sql as $line) {
      fwrite($sql_file, "$line;\n");
    }
    fclose($sql_file);
  }

  function generateDropSql($fileName = 'civicrm_drop.mysql') {
    $table_names = array();
    foreach ($this->config->doctrine->metadata as $class_metadata) {
      $table_names[] = $class_metadata->getTableName();
    }
    $dropOrder = array_reverse($table_names);
    $template = new CRM_Core_CodeGen_Util_Template($this->config, 'sql');
    $template->assign('dropOrder', $dropOrder);
    $template->run('drop.tpl', CRM_Utils_Path::join($this->config->sqlCodePath, $fileName));
  }

  function generateNavigation() {
    $template = new CRM_Core_CodeGen_Util_Template($this->config, 'sql');
    $template->run('civicrm_navigation.tpl', CRM_Utils_Path::join($this->config->sqlCodePath, "civicrm_navigation.mysql"));
  }

  function generateLocaleDataSql() {
    $template = new CRM_Core_CodeGen_Util_Template($this->config, 'sql');

    global $tsLocale;
    $oldTsLocale = $tsLocale;
    foreach ($this->locales as $locale) {
      $tsLocale = $locale;
      $template->assign('locale', $locale);
      $template->assign('db_version', $this->config->db_version);

      $sections = array(
        'civicrm_country.tpl',
        'civicrm_state_province.tpl',
        'civicrm_currency.tpl',
        'civicrm_data.tpl',
        'civicrm_navigation.tpl',
        'civicrm_version_sql.tpl',
      );

      $ext = ($locale != 'en_US' ? ".$locale" : '');
      // write the initialize base-data sql script
      $template->runConcat($sections, CRM_Utils_Path::join($this->config->sqlCodePath, "civicrm_data$ext.mysql"));

      // write the acl sql script
      $template->run('civicrm_acl.tpl', CRM_Utils_Path::join($this->config->sqlCodePath, "civicrm_acl$ext.mysql"));
    }
    $tsLocale = $oldTsLocale;
  }

  function generateSample() {
    $template = new CRM_Core_CodeGen_Util_Template($this->config, 'sql');
    $sections = array(
      'civicrm_sample.tpl',
      'civicrm_acl.tpl',
    );
    $template->runConcat($sections, CRM_Utils_Path::join($this->config->sqlCodePath, 'civicrm_sample.mysql'));
  }

  function findLocales() {
    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton(FALSE);
    $locales = array();
    if (substr($config->gettextResourceDir, 0, 1) === '/') {
      $localeDir = $config->gettextResourceDir;
    }
    else {
      $localeDir = '../' . $config->gettextResourceDir;
    }
    if (file_exists($localeDir)) {
      $config->gettextResourceDir = $localeDir;
      $locales = preg_grep('/^[a-z][a-z]_[A-Z][A-Z]$/', scandir($localeDir));
    }

    $localesMask = getenv('CIVICRM_LOCALES');
    if (!empty($localesMask)) {
      $mask = explode(',', $localesMask);
      $locales = array_intersect($locales, $mask);
    }

    if (!in_array('en_US', $locales)) {
      array_unshift($locales, 'en_US');
    }

    return $locales;
  }
}
