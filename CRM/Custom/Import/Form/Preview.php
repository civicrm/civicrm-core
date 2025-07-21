<?php

use Civi\Import\CustomValueParser;

/**
 * Class CRM_Custom_Import_Form_Preview
 */
class CRM_Custom_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * @return \Civi\Import\CustomValueParser
   */
  protected function getParser(): CustomValueParser {
    if (!$this->parser) {
      $this->parser = new CustomValueParser();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
