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
 * Class CRM_Dedupe_BAO_QueryBuilder_IndividualUnsupervised
 */
class CRM_Dedupe_BAO_QueryBuilder_IndividualUnsupervised extends CRM_Dedupe_BAO_QueryBuilder {

  /**
   * @param $rg
   *
   * @return array
   */
  public static function record($rg) {
    $civicrm_email = CRM_Utils_Array::value('civicrm_email', $rg->params, []);

    $params = [
      1 => [CRM_Utils_Array::value('email', $civicrm_email, ''), 'String'],
    ];

    return [
      "civicrm_contact.{$rg->name}.{$rg->threshold}" => CRM_Core_DAO::composeQuery("
                SELECT contact.id as id1, {$rg->threshold} as weight
                FROM civicrm_contact as contact
                  JOIN civicrm_email as email ON email.contact_id=contact.id
                WHERE contact_type = 'Individual'
                  AND email = %1", $params, TRUE),
    ];
  }

  /**
   * @param $rg
   *
   * @return array
   */
  public static function internal($rg) {
    $query = "
            SELECT contact1.id as id1, contact2.id as id2, {$rg->threshold} as weight
            FROM civicrm_contact as contact1
              JOIN civicrm_email as email1 ON email1.contact_id=contact1.id
              JOIN civicrm_contact as contact2
              JOIN civicrm_email as email2 ON
                email2.contact_id=contact2.id AND
                email1.email=email2.email
            WHERE contact1.contact_type = 'Individual'
              AND " . self::internalFilters($rg);
    return ["civicrm_contact.{$rg->name}.{$rg->threshold}" => $query];
  }

}
