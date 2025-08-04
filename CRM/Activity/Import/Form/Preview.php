<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class previews the uploaded file and returns summary statistics.
 */
class CRM_Activity_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * @return CRM_Activity_Import_Parser_Activity
   */
  protected function getParser(): CRM_Activity_Import_Parser_Activity {
    if (!$this->parser) {
      $this->parser = new CRM_Activity_Import_Parser_Activity();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
