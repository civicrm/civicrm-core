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
 * $Id$
 *
 */

/**
 * This class defines the DataSource interface but must be subclassed to be
 * useful.
 */
abstract class CRM_Import_DataSource {

  /**
   * Provides information about the data source.
   *
   * @return array
   *   Description of this data source, including:
   *   - title: string, translated, required
   *   - permissions: array, optional
   *
   */
  abstract public function getInfo();

  /**
   * Set variables up before form is built.
   *
   * @param CRM_Core_Form $form
   */
  abstract public function preProcess(&$form);

  /**
   * This is function is called by the form object to get the DataSource's form snippet.
   *
   * It should add all fields necessary to get the data uploaded to the temporary table in the DB.
   *
   * @param CRM_Core_Form $form
   */
  abstract public function buildQuickForm(&$form);

  /**
   * Process the form submission.
   *
   * @param array $params
   * @param string $db
   * @param CRM_Core_Form $form
   */
  abstract public function postProcess(&$params, &$db, &$form);

  /**
   * Determine if the current user has access to this data source.
   *
   * @return bool
   */
  public function checkPermission() {
    $info = $this->getInfo();
    return empty($info['permissions']) || CRM_Core_Permission::check($info['permissions']);
  }

}
