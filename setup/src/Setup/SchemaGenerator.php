<?php
namespace Civi\Setup;

class SchemaGenerator {

  /**
   * Generate an example set of data, including the basic data as well
   * as some example records/entities (e.g. case-types, membership types).
   *
   * @param string $srcPath
   *
   * @return string
   */
  public static function generateSampleData($srcPath) {
    $versionFile = implode(DIRECTORY_SEPARATOR, [$srcPath, 'xml', 'version.xml']);
    $xml = \CRM_Core_CodeGen_Util_Xml::parse($versionFile);

    $template = new Template($srcPath, 'sql');
    $template->assign('db_version', $xml->version_no);

    // If you're going to use the full data generator...
    //    "DROP TABLE IF EXISTS zipcodes"
    //    .... file_get_contents($sqlPath . DIRECTORY_SEPARATOR . 'zipcodes.mysql')...

    $sections = [
      'civicrm_country.tpl',
      'civicrm_state_province.tpl',
      'civicrm_currency.tpl',
      'civicrm_data.tpl',
      'civicrm_acl.tpl',
      'civicrm_sample.tpl',
      'case_sample.tpl',
      'civicrm_version_sql.tpl',
      'civicrm_navigation.tpl',
    ];

    // DROP TABLE IF EXISTS zipcodes;

    return $template->getConcatContent($sections);
  }

  /**
   * Generate a minimalist set of basic data, such as
   * common option-values and countries.
   *
   * @param string $srcPath
   *
   * @return string
   *   SQL
   */
  public static function generateBasicData($srcPath) {
    $versionFile = implode(DIRECTORY_SEPARATOR, [$srcPath, 'xml', 'version.xml']);
    $xml = \CRM_Core_CodeGen_Util_Xml::parse($versionFile);

    $template = new Template($srcPath, 'sql');
    $template->assign('db_version', $xml->version_no);

    $sections = [
      'civicrm_country.tpl',
      'civicrm_state_province.tpl',
      'civicrm_currency.tpl',
      'civicrm_data.tpl',
      'civicrm_acl.tpl',
      'civicrm_version_sql.tpl',
      'civicrm_navigation.tpl',
    ];
    return $template->getConcatContent($sections);
  }

}
