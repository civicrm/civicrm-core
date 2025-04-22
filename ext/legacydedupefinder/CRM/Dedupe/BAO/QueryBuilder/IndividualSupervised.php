<?php

/**
 * TODO: How to handle NULL values/records?
 * Class CRM_Dedupe_BAO_QueryBuilder_IndividualSupervised
 */
class CRM_Dedupe_BAO_QueryBuilder_IndividualSupervised extends CRM_Dedupe_BAO_QueryBuilder {

  /**
   * Record - what do I do.
   *
   * @param object $rg
   *
   * @return array
   */
  public static function record($rg) {

    $civicrm_contact = $rg->params['civicrm_contact'] ?? [];
    $civicrm_email = $rg->params['civicrm_email'] ?? [];

    $params = [
      1 => [
        $civicrm_contact['first_name'] ?? '',
        'String',
      ],
      2 => [
        $civicrm_contact['last_name'] ?? '',
        'String',
      ],
      3 => [
        $civicrm_email['email'] ?? '',
        'String',
      ],
    ];

    return [
      "civicrm_contact.{$rg->name}.{$rg->threshold}" => CRM_Core_DAO::composeQuery("
                SELECT contact.id as id1, {$rg->threshold} as weight
                FROM civicrm_contact as contact
                  JOIN civicrm_email as email ON email.contact_id=contact.id
                WHERE contact_type = 'Individual'
                  AND first_name = %1
                  AND last_name = %2
                  AND email = %3", $params, TRUE),
    ];
  }

  /**
   * Internal - what do I do.
   *
   * @param object $rg
   *
   * @return array
   */
  public static function internal($rg) {
    $query = self::filterQueryByContactList($rg->contactIds, "
            SELECT contact1.id as id1, contact2.id as id2, {$rg->threshold} as weight
            FROM civicrm_contact as contact1
              JOIN civicrm_email as email1 ON email1.contact_id=contact1.id
              JOIN civicrm_contact as contact2 ON
                contact1.first_name = contact2.first_name AND
                contact1.last_name = contact2.last_name
              JOIN civicrm_email as email2 ON
                email2.contact_id=contact2.id AND
                email1.email=email2.email
            WHERE contact1.contact_type = 'Individual'");

    return [
      "civicrm_contact.{$rg->name}.{$rg->threshold}" => $query,
    ];
  }

}
