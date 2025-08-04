<?php

use Civi\Api4\UserJob;
use CRM_Civiimport_ExtensionUtil as E;

class CRM_CiviImport_Form_DataSource extends CRM_Import_Form_DataSource {

  /**
   * Common form elements.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->assign('errorMessage', $this->getErrorMessage());

    $this->assign('urlPath', 'civicrm/import/datasource');
    $this->assign('urlPathVar', 'snippet=4&user_job_id=' . $this->get('user_job_id'));
    if ($this->isImportDataUploaded()) {
      $this->add('checkbox', 'use_existing_upload', ts('Use data already uploaded'), [
        'onChange' => "
          CRM.$('.crm-import-datasource-form-block-dataSource').toggle();
          CRM.$('#data-source-form-block').toggle()",
      ]);
    }
    if ($this->getTemplateID()) {
      $this->setTemplateDefaults();
    }

    $this->add('select', 'dataSource', ts('Data Source'), $this->getDataSources(), TRUE,
      ['onchange' => 'buildDataSourceFormBlock(this.value);']
    );

    $this->addMappingSelector();

    // When we call buildDataSourceFields we add them to the form both for purposes of
    // initial display, but also so they are available during `postProcess`. Hence
    // we need to add them to the form when first displaying it, or when a csv has been
    // uploaded or csv described but NOT when the existing file is used. We have
    // to check `_POST` for this because we want them to be not-added BEFORE validation
    // as `buildDataSourceFields` also adds rules, which will run before `use_existing_upload`
    // is treated as submitted.
    if (empty($_POST['use_existing_upload'])) {
      $this->buildDataSourceFields();
    }
    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Continue'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName(): string {
    return 'CRM/Import/DataSource.tpl';
  }

  /**
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function addMappingSelector(): void {
    $templates = (array) UserJob::get()
      ->addWhere('is_template', '=', TRUE)
      ->addWhere('job_type', '=', $this->getUserJobType())
      ->execute();
    $mappings = [];
    foreach ($templates as $template) {
      $mappings[] = ['id' => $template['id'], 'text' => substr($template['name'], 7)];
    }
    if ($mappings) {
      $this->add('select2', 'userJobTemplate', E::ts('Saved Field Mapping'), $mappings, FALSE, ['class' => 'crm-select2', 'placeholder' => E::ts('- select -')]);
    }
  }

  public function setDefaultValues(): array {
    $defaults = array_merge($this->dataSourceDefaults, [
      'dataSource' => $this->getDefaultDataSource(),
    ], $this->templateValues);
    if ($this->templateID) {
      $defaults['userJobTemplate'] = $this->templateID;
    }
    return $defaults;
  }

}
