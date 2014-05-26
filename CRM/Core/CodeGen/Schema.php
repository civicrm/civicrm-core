<?php

/**
 * Create SQL files to create and populate a new schema.
 */
class CRM_Core_CodeGen_Schema extends CRM_Core_CodeGen_BaseTask {
  /**
   *
   */
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

  /**
   * @param string $fileName
   */
  function generateCreateSql($fileName = 'civicrm.mysql') {
    echo "Generating sql file\n";
    $template = new CRM_Core_CodeGen_Util_Template('sql');

    $template->assign('database', $this->config->database);
    $template->assign('tables', $this->tables);
    $dropOrder = array_reverse(array_keys($this->tables));
    $template->assign('dropOrder', $dropOrder);
    $template->assign('mysql', 'modern');

    $template->run('schema.tpl', $this->config->sqlCodePath . $fileName);
  }

  /**
   * @param string $fileName
   */
  function generateDropSql($fileName = 'civicrm_drop.mysql') {
    echo "Generating sql drop tables file\n";
    $dropOrder = array_reverse(array_keys($this->tables));
    $template = new CRM_Core_CodeGen_Util_Template('sql');
    $template->assign('dropOrder', $dropOrder);
    $template->run('drop.tpl', $this->config->sqlCodePath . $fileName);
  }

  function generateNavigation() {
    echo "Generating navigation file\n";
    $template = new CRM_Core_CodeGen_Util_Template('sql');
    $template->run('civicrm_navigation.tpl', $this->config->sqlCodePath . "civicrm_navigation.mysql");
  }

  function generateLocaleDataSql() {
    $template = new CRM_Core_CodeGen_Util_Template('sql');

    global $tsLocale;
    $oldTsLocale = $tsLocale;
    foreach ($this->locales as $locale) {
      echo "Generating data files for $locale\n";
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
      $template->runConcat($sections, $this->config->sqlCodePath . "civicrm_data$ext.mysql");

      // write the acl sql script
      $template->run('civicrm_acl.tpl', $this->config->sqlCodePath . "civicrm_acl$ext.mysql");
    }
    $tsLocale = $oldTsLocale;
  }

  function generateSample() {
    $template = new CRM_Core_CodeGen_Util_Template('sql');
    $sections = array(
      'civicrm_sample.tpl',
      'civicrm_acl.tpl',
    );
    $template->runConcat($sections, $this->config->sqlCodePath . 'civicrm_sample.mysql');

    $template->run('case_sample.tpl', $this->config->sqlCodePath . 'case_sample.mysql');
  }

  /**
   * @return array
   */
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
