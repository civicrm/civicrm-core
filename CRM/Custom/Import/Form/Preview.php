<?php

/**
 * Class CRM_Custom_Import_Form_Preview
 */
class CRM_Custom_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * @return CRM_Custom_Import_Parser_Api
   */
  protected function getParser(): CRM_Custom_Import_Parser_Api {
    if (!$this->parser) {
      $this->parser = new CRM_Custom_Import_Parser_Api();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
