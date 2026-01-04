<?php

/**
 * Class CRM_Search_Import_Form_MapField
 */
class CRM_Search_Import_Form_MapField extends CRM_Import_Form_MapField {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'search_batch_import';
  }

  /**
   * @return CRM_Search_Import_Parser
   */
  protected function getParser(): CRM_Search_Import_Parser {
    if (!$this->parser) {
      $this->parser = new CRM_Search_Import_Parser();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
