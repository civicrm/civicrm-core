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

use Civi\Import\ContributionParser;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class gets the name of the file to upload.
 */
class CRM_Contribute_Import_Form_MapField extends CRM_CiviImport_Form_MapField {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'contribution_import';
  }

  /**
   * Should contact fields be filtered which determining fields to show.
   *
   * This applies to Contribution import as we put all contact fields in the metadata
   * but only present those used for a match in QuickForm - the civiimport extension has
   * more functionality to update and create.
   *
   * @return bool
   */
  protected function isFilterContactFields() : bool {
    return TRUE;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {

    $this->addFormRule([
      'CRM_CiviImport_Form_MapField',
      'validateMapping',
    ], $this);

    $selectColumn1 = $this->getAvailableFields();

    $selectColumn2 = [];
    $softCreditTypes = CRM_Core_OptionGroup::values('soft_credit_type');
    foreach (array_keys($selectColumn1) as $fieldName) {
      if (str_starts_with($fieldName, 'soft_credit__contact__')) {
        $selectColumn2[$fieldName] = $softCreditTypes;
      }
    }

    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $sel->setOptions([$selectColumn1, $selectColumn2]);
    }
    $defaults = $this->getDefaults();
    $this->setDefaults($defaults);
    $this->addFormButtons();
  }

  /**
   * Get the fields available for import selection.
   *
   * @return array
   *   e.g ['first_name' => 'First Name', 'last_name' => 'Last Name'....
   */
  protected function getAvailableFields(): array {
    $return = [];
    foreach ($this->getFields() as $name => $field) {
      if ($this->isUpdateExisting() && in_array($name, ['contact_id', 'email', 'contact.first_name', 'contact.last_name', 'external_identifier', 'email_primary.email'], TRUE)) {
        continue;
      }
      if ($this->isUpdateExisting() && in_array($name, ['id', 'invoice_id', 'trxn_id'], TRUE)) {
        $field['title'] .= (' ' . ts('(match to contribution record)'));
      }
      if (($field['entity'] ?? '') === 'Contact' && $this->isFilterContactFields() && empty($field['match_rule'])) {
        // Filter out metadata that is intended for create & update - this is not available in the quick-form
        // but is now loaded in the Parser for the LexIM variant.
        continue;
      }
      $return[$name] = $field['html']['label'] ?? $field['title'];
    }
    return $return;
  }

  /**
   * Get the mapping name per the civicrm_mapping_field.type_id option group.
   *
   * @return string
   */
  public function getMappingTypeName(): string {
    return 'Import Contribution';
  }

  /**
   * @return \Civi\Import\ContributionParser
   */
  protected function getParser(): ContributionParser {
    if (!$this->parser) {
      $this->parser = new ContributionParser();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

  /**
   * Add the saved mapping to the defaults.
   *
   * @param array $defaults
   * @param array $fieldMapping
   * @param int $rowNumber
   *
   * @return void
   */
  public function addMappingToDefaults(array &$defaults, array $fieldMapping, int $rowNumber): void {
    if ($fieldMapping) {
      if ($fieldMapping['name'] !== ts('do_not_import')) {
        // $mapping contact_type is not really a contact type - the 'about this entity' data has been mangled
        // into that field - see https://lab.civicrm.org/dev/core/-/issues/654
        $softCreditTypeID = '';
        $entityData = json_decode($fieldMapping['contact_type'] ?? '', TRUE);
        if (!empty($entityData)) {
          $softCreditTypeID = (int) $entityData['soft_credit']['soft_credit_type_id'];
        }
        $fieldName = $fieldMapping['name'];
        $defaults["mapper[$rowNumber]"] = [$fieldName, $softCreditTypeID];
      }
    }
  }

}
