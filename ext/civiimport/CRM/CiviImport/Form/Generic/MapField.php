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
 * This class provides a generic import MapField form.
 *
 * It can be loaded outside the form controller.
 */
class CRM_CiviImport_Form_Generic_MapField extends \CRM_CiviImport_Form_MapField {
  use CRM_CiviImport_Form_Generic_GenericTrait;
  use \Civi\UserJob\UserJobTrait;

}
