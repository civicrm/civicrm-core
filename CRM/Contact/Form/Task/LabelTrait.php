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

use Civi\Api4\Address;
use Civi\Api4\Contact;

/**
 * This class provides the common functionality for tasks that create labels.
 *
 * @internal - not supported for external use, may change without notice.
 *
 */
trait CRM_Contact_Form_Task_LabelTrait {

  /**
   *
   * @throws \CRM_Core_Exception
   * @internal - not supported for external use, may change without notice.
   *
   */
  public function createLabels(): void {
    /*
     * CRM-8338: replace ids of household members with the id of their household
     * so we can merge labels by household.
     */
    if ($this->getSubmittedValue('merge_same_household')) {
      $this->mergeContactIdsByHousehold();
    }

    //get the total number of contacts to fetch from database.
    $contactGet = Contact::get()
      ->addWhere('is_deceased', '=', FALSE)
      ->addWhere('id', 'IN', $this->getContactIDs())
      ->addOrderBy('sort_name');
    if ($this->getSubmittedValue('do_not_mail')) {
      $contactGet->addWhere('do_not_mail', '=', FALSE);
    }

    if ($this->getSubmittedValue('location_type_id')) {
      $contactGet->addChain('address', Address::get()
        ->addWhere('contact_id', '=', '$id')
        ->addWhere('location_type_id', '=', $this->getSubmittedValue('location_type_id'))
        ->setSelect($this->getAddressFields())
      );
    }
    else {
      $contactGet->addChain('address', Address::get()
        ->addWhere('contact_id', '=', '$id')
        ->addWhere('is_primary', '=', TRUE)
        ->setSelect($this->getAddressFields())
      );
    }
    $rows = (array) $contactGet->execute()->indexBy('id');
    foreach ($rows as &$contact) {
      // do we need to do this?
      if (!empty($contact['addressee_display'])) {
        $contact['addressee_display'] = trim($contact['addressee_display']);
      }
      // do we need to do this?
      if (!empty($contact['addressee'])) {
        $contact['addressee'] = $contact['addressee_display'];
      }
      // do we need to do this?
      if (isset($contact['address'])) {
        foreach ($contact['address'][0] ?? [] as $field => $value) {
          if ($field !== 'id') {
            $contact[$field] = $value;
          }
        }
        unset($contact['address']);
      }
    }

    if ($this->getSubmittedValue('merge_same_address')) {
      CRM_Core_BAO_Address::mergeSameAddress($rows);
    }

    // format the addresses according to CIVICRM_ADDRESS_FORMAT (CRM-1327)
    // Iterate contact IDs not rows to get original sort order.
    foreach ($this->getContactIDs() as $id) {
      if (isset($rows[$id])) {
        $rows[$id] = [CRM_Utils_Address::formatMailingLabel($rows[$id])];
      }
    }

    //call function to create labels
    $this->createLabel($rows, $this->getSubmittedValue('label_name'));
  }

  /**
   * Create labels (pdf).
   *
   * @param array $contactRows
   *   Associated array of contact data.
   * @param string $format
   *   Format in which labels needs to be printed.
   */
  private function createLabel(array $contactRows, $format) {
    $pdf = new CRM_Utils_PDF_Label($format, 'mm');
    $pdf->Open();
    $pdf->AddPage();

    //build contact string that needs to be printed
    $val = NULL;
    foreach ($contactRows as $value) {
      foreach ($value as $v) {
        $val .= "$v\n";
      }

      $pdf->AddPdfLabel($val);
      $val = '';
    }
    if (CIVICRM_UF === 'UnitTests') {
      throw new CRM_Core_Exception_PrematureExitException('pdf output called', ['contactRows' => $contactRows, 'format' => $format, 'pdf' => $pdf]);
    }
    $pdf->Output('MailingLabels_CiviCRM.pdf', 'D');
  }

  /**
   * @return string[]
   */
  public function getAddressFields(): array {
    return [
      'city',
      'street_address',
      'name',
      'postal_code',
      'postal_code_suffix',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'country_id:label',
      'country_id',
      'county_id:label',
      'county_id',
      'state_province_id:label',
      'state_province_id',
    ];
  }

}
