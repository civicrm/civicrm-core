<?php

/**
 * Create SQL files to create and populate a new schema.
 *
 * @deprecated
 *   Replaced by CRM_Core_CodeGen_PhpSchema.
 *   Delete this after civicrm-core and civix drop their references.
 *   Maybe allow grace-period of a couple months.
 */
class CRM_Core_CodeGen_Schema extends CRM_Core_CodeGen_BaseTask {

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

    // also create the archive tables
    // $this->generateCreateSql('civicrm_archive.mysql' );
    // $this->generateDropSql('civicrm_archive_drop.mysql');

    echo "Generating navigation file\n";
    $put($this->generateNavigation());

    echo "Generating sample file\n";
    $put($this->generateSample());
  }

  public function generateCreateSql() {
    $template = new CRM_Core_CodeGen_Util_Template('sql');

    $template->assign('database', $this->config->database);
    $template->assign('tables', $this->tables);
    $dropOrder = array_reverse(array_keys($this->tables));
    $template->assign('dropOrder', $dropOrder);
    $template->assign('mysql', 'modern');
    CRM_Core_CodeGen_Util_MessageTemplates::assignSmartyVariables($template->getSmarty());
    return ['civicrm.mysql' => $template->fetch('schema.tpl')];
  }

  public function generateDropSql() {
    $dropOrder = array_reverse(array_keys($this->tables));
    $template = new CRM_Core_CodeGen_Util_Template('sql');
    $template->assign('dropOrder', $dropOrder);
    $template->assign('isOutputLicense', TRUE);
    return ['civicrm_drop.mysql' => $template->fetch('drop.tpl')];
  }

  public function generateNavigation() {
    $template = new CRM_Core_CodeGen_Util_Template('sql');
    return ['civicrm_navigation.mysql' => $template->fetch('civicrm_navigation.tpl')];
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

}
