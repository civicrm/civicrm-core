<?php

/**
 * TODO: How to handle NULL values/records?
 * Class CRM_Dedupe_BAO_QueryBuilder_IndividualGeneral
 */
class CRM_Dedupe_BAO_QueryBuilder_IndividualGeneral extends CRM_Dedupe_BAO_QueryBuilder {

  /**
   * @param $rg
   *
   * @return array
   */
  public static function record($rg) {
    $civicrm_contact = $rg->params['civicrm_contact'] ?? NULL;
    $civicrm_address = $rg->params['civicrm_address'] ?? NULL;

    // Since definitely have first and last name, escape them upfront.
    $first_name = CRM_Core_DAO::escapeString($civicrm_contact['first_name'] ?? '');
    $last_name = CRM_Core_DAO::escapeString($civicrm_contact['last_name'] ?? '');
    $street_address = CRM_Core_DAO::escapeString($civicrm_address['street_address'] ?? '');

    $query = "
            SELECT contact1.id id1, {$rg->threshold} as weight
            FROM civicrm_contact AS contact1
              JOIN civicrm_address AS address1 ON contact1.id=address1.contact_id
            WHERE contact1.contact_type = 'Individual'
              AND contact1.first_name = '$first_name'
              AND contact1.last_name = '$last_name'
              AND address1.street_address = '$street_address'
              ";

    if ($birth_date = CRM_Core_DAO::escapeString($civicrm_contact['birth_date'] ?? '')) {
      $query .= " AND (contact1.birth_date IS NULL or contact1.birth_date = '$birth_date')\n";
    }

    if ($suffix_id = CRM_Core_DAO::escapeString($civicrm_contact['suffix_id'] ?? '')) {
      $query .= " AND (contact1.suffix_id IS NULL or contact1.suffix_id = $suffix_id)\n";
    }

    if ($middle_name = CRM_Core_DAO::escapeString($civicrm_contact['middle_name'] ?? '')) {
      $query .= " AND (contact1.middle_name IS NULL or contact1.middle_name = '$middle_name')\n";
    }

    return ["civicrm_contact.{$rg->name}.{$rg->threshold}" => $query];
  }

  /**
   * @param $rg
   *
   * @return array
   */
  public static function internal($rg) {
    $query = "
            SELECT contact1.id id1,  contact2.id id2, {$rg->threshold} weight
            FROM civicrm_contact AS contact1
              JOIN civicrm_contact AS contact2 ON (
                contact1.first_name = contact2.first_name AND
                contact1.last_name = contact2.last_name AND
                contact1.contact_type = contact2.contact_type)
              JOIN civicrm_address AS address1 ON address1.contact_id = contact1.id
              JOIN civicrm_address AS address2 ON (
                address2.contact_id =  contact2.id AND
                address2.street_address = address1.street_address)
            WHERE contact1.contact_type = 'Individual'
              AND (contact1.suffix_id IS NULL OR contact2.suffix_id IS NULL OR contact1.suffix_id = contact2.suffix_id)
              AND (contact1.middle_name IS NULL OR contact2.middle_name IS NULL OR contact1.middle_name = contact2.middle_name)
              AND (contact1.birth_date IS NULL OR contact2.birth_date IS NULL OR contact1.birth_date = contact2.birth_date)
              AND " . self::internalFilters($rg);
    return ["civicrm_contact.{$rg->name}.{$rg->threshold}" => $query];
  }

}
