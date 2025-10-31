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
use Civi\Import\GenericParser;

/**
 * This class provides a generic import MapField form.
 *
 * It can be loaded outside the form controller.
 */
class CRM_CiviImport_Form_Generic_DataSource extends \CRM_CiviImport_Form_DataSource {

  use CRM_CiviImport_Form_Generic_GenericTrait;

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'import_generic';
  }

  public function getParser(): GenericParser {
    /* @var \Civi\Import\GenericParser $parser */
    $parser = parent::getParser();
    $parser->setBaseEntity($this->getBaseEntity());
    return $parser;
  }

}
