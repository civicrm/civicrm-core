<?php

/**
 * Class CRM_Search_Import_Form_Preview
 */
class CRM_Search_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * @return CRM_Search_Import_Parser_SearchBatch
   */
  protected function getParser(): CRM_Search_Import_Parser_SearchBatch {
    if (!$this->parser) {
      $this->parser = new CRM_Search_Import_Parser_SearchBatch();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
