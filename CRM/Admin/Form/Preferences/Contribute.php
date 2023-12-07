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

use Civi\Core\SettingsMetadata;

/**
 * This class generates form components for the display preferences.
 */
class CRM_Admin_Form_Preferences_Contribute extends CRM_Admin_Form_Preferences {

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();
    $invoiceSettings = SettingsMetadata::getMetadata(['name' => ['invoice_prefix', 'tax_term', 'invoice_notes', 'invoice_due_date', 'invoice_is_email_pdf', 'invoice_due_date_period', 'tax_display_settings']], NULL, TRUE);
    // Let the main template file deal with the main setting & then Contribute.tpl
    // can stick the invoice settings in a div that can show-hide-toggle if invoicing is enabled.
    $this->assign('fields', $this->filterMetadataByWeight(array_diff_key($this->getSettingsMetaData(), $invoiceSettings)));
    $this->assign('invoiceDependentFields', $invoiceSettings);
  }

}
