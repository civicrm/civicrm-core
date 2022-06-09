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
 * This class previews the uploaded file and returns summary
 * statistics
 */
class CRM_Event_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * @return CRM_Event_Import_Parser_Participant
   */
  protected function getParser(): CRM_Event_Import_Parser_Participant {
    if (!$this->parser) {
      $this->parser = new CRM_Event_Import_Parser_Participant();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
