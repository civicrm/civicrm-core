<?php

/**
 * Drop-in replacement for CRM_Core_CodeGen_Schema which uses schema/**.entityType.php instead of xml/schema/**.xml
 *
 * @internal
 */
class CRM_Core_CodeGen_PhpSchema extends CRM_Core_CodeGen_BaseTask {

  public $locales;

  /**
   * CRM_Core_CodeGen_PhpSchema constructor.
   *
   * @param \CRM_Core_CodeGen_Main $config
   */
  public function __construct($config) {
    parent::__construct($config);
    $this->locales = $this->findLocales();
  }

  public function run() {
    CRM_Core_CodeGen_Util_File::createDir($this->config->sqlCodePath);

    $put = function ($files) {
      foreach ($files as $file => $content) {
        if (substr($content, -1) !== "\n") {
          $content .= "\n";
        }
        file_put_contents($this->config->sqlCodePath . $file, $content);
      }
    };

    echo "Generating sql file\n";
    $put($this->generateCreateSql());

    echo "Generating sql drop tables file\n";
    $put($this->generateDropSql());

    foreach ($this->locales as $locale) {
      echo "Generating data files for $locale\n";
      $put($this->generateLocaleDataSql($locale));
    }

    // also create the archive tables
    // $this->generateCreateSql('civicrm_archive.mysql' );
    // $this->generateDropSql('civicrm_archive_drop.mysql');

    echo "Generating navigation file\n";
    $put($this->generateNavigation());

    echo "Generating sample file\n";
    $put($this->generateSample());
  }

  public function generateCreateSql() {
    return ['civicrm.mysql' => \Civi::schemaHelper()->generateInstallSql()];
  }

  public function generateDropSql() {
    return ['civicrm_drop.mysql' => \Civi::schemaHelper()->generateUninstallSql()];
  }

  public function generateNavigation() {
    $template = new CRM_Core_CodeGen_Util_Template('sql');
    return ['civicrm_navigation.mysql' => $template->fetch('civicrm_navigation.tpl')];
  }

  /**
   * @param string $locale
   *   Ex: en_US, fr_FR
   * @return array
   */
  public function generateLocaleDataSql($locale) {
    $template = new CRM_Core_CodeGen_Util_Template('sql');
    CRM_Core_CodeGen_Util_MessageTemplates::assignSmartyVariables($template->getSmarty());
    global $tsLocale;
    $oldTsLocale = $tsLocale;

    try {

      $tsLocale = $locale;
      $template->assign('locale', $locale);
      $template->assign('db_version', $this->config->db_version);

      $sections = [
        'civicrm_country.tpl',
        'civicrm_state_province.tpl',
        'civicrm_currency.tpl',
        'civicrm_data.tpl',
        'civicrm_navigation.tpl',
        'civicrm_version_sql.tpl',
      ];

      $ext = ($locale !== 'en_US' ? ".$locale" : '');

      return [
        "civicrm_data$ext.mysql" => $template->fetchConcat($sections),
        "civicrm_acl$ext.mysql" => $template->fetch('civicrm_acl.tpl'),
      ];
    }
    finally {
      $tsLocale = $oldTsLocale;
    }
  }

  /**
   * @return array
   *   Array(string $fileName => string $fileContent).
   *   List of files
   */
  public function generateSample() {
    $template = new CRM_Core_CodeGen_Util_Template('sql');
    $sections = [
      'civicrm_sample.tpl',
      'civicrm_acl.tpl',
    ];
    return [
      'civicrm_sample.mysql' => $template->fetchConcat($sections),
      'case_sample.mysql' => $template->fetch('case_sample.tpl'),
    ];
  }

  /**
   * @return array
   */
  public function findLocales() {
    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton(FALSE);
    $locales = [];
    $localeDir = CRM_Core_I18n::getResourceDir();
    if (file_exists($localeDir)) {
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
