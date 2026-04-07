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

use Civi\Import\MembershipParser;

/**
 * This class gets the name of the file to upload
 */
class CRM_Member_Import_Form_MapField extends CRM_CiviImport_Form_MapField {

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addFormRule([__CLASS__, 'formRule'], $this);
    $this->addMapper();
    $this->setDefaults($this->getDefaults());
    $this->addFormButtons();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $files
   * @param self $self
   *
   * @return array|bool
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    $mappedFields = $self->getMappedFields($fields['mapper']);
    if (!in_array('Membership.id', $mappedFields)) {
      $errors = $self->validateRequiredContactFields();
      // FIXME: should use the schema titles, not redeclare them
      $requiredFields = [
        'Membership.membership_type_id' => ts('Membership Type'),
        'Membership.start_date' => ts('Membership Start Date'),
      ];
      foreach ($requiredFields as $field => $title) {
        if (!in_array($field, $mappedFields)) {
          if (!isset($errors['_qf_default'])) {
            $errors['_qf_default'] = '';
          }
          $errors['_qf_default'] .= ts('Missing required field: %1', [1 => $title]) . '<br />';
        }
      }
    }
    return $errors ?: TRUE;
  }

  /**
   * Get the mapping name per the civicrm_mapping_field.type_id option group.
   *
   * @return string
   */
  public function getMappingTypeName(): string {
    return 'Import Membership';
  }

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'membership_import';
  }

  /**
   * @return \Civi\Import\MembershipParser
   */
  protected function getParser(): MembershipParser {
    if (!$this->parser) {
      $this->parser = new MembershipParser();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
