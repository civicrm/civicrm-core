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
 * This class delegates to the chosen DataSource to grab the data to be imported.
 */
class CRM_Contact_Import_Form_DataSource extends CRM_Import_Form_DataSource {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'contact_import';
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();

    // duplicate handling options
    $this->addRadio('onDuplicate', ts('For Duplicate Contacts'), [
      CRM_Import_Parser::DUPLICATE_SKIP => ts('Skip'),
      CRM_Import_Parser::DUPLICATE_UPDATE => ts('Update'),
      CRM_Import_Parser::DUPLICATE_FILL => ts('Fill'),
      CRM_Import_Parser::DUPLICATE_NOCHECK => ts('No Duplicate Checking'),
    ]);

    $js = ['onClick' => "buildSubTypes();buildDedupeRules();"];
    // contact types option
    $contactTypeOptions = $contactTypeAttributes = [];
    if (CRM_Contact_BAO_ContactType::isActive('Individual')) {
      $contactTypeOptions['Individual'] = ts('Individual');
      $contactTypeAttributes['Individual'] = $js;
    }
    if (CRM_Contact_BAO_ContactType::isActive('Household')) {
      $contactTypeOptions['Household'] = ts('Household');
      $contactTypeAttributes['Household'] = $js;
    }
    if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
      $contactTypeOptions['Organization'] = ts('Organization');
      $contactTypeAttributes['Organization'] = $js;
    }
    $this->addRadio('contactType', ts('Contact Type'), $contactTypeOptions, [], NULL, FALSE, $contactTypeAttributes);

    $this->addElement('select', 'contactSubType', ts('Subtype'));
    $this->addElement('select', 'dedupe_rule_id', ts('Dedupe Rule'));

    if (CRM_Utils_GeocodeProvider::getUsableClassName()) {
      $this->addElement('checkbox', 'doGeocodeAddress', ts('Geocode addresses during import?'));
    }

    if (Civi::settings()->get('address_standardization_provider') === 'USPS') {
      $this->addElement('checkbox', 'disableUSPS', ts('Disable USPS address validation during import?'));
    }
  }

  /**
   * Set the default values of various form elements.
   *
   * @return array
   *   reference to the array of default values
   */
  public function setDefaultValues(): array {
    return array_merge([
      'contactType' => 'Individual',
      'disableUSPS' => TRUE,
    ], parent::setDefaultValues());
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle(): string {
    return ts('Choose Data Source');
  }

}
