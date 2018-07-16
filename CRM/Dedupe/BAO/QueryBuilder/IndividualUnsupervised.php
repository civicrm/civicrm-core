<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 5                                                  |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2018                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    $civicrm_email = CRM_Utils_Array::value('civicrm_email', $rg->params, array());

    $params = array(
      1 => array(CRM_Utils_Array::value('email', $civicrm_email, ''), 'String'),
    );

    return array(
      "civicrm_contact.{$rg->name}.{$rg->threshold}" => CRM_Core_DAO::composeQuery("
                SELECT contact.id as id1, {$rg->threshold} as weight
                FROM civicrm_contact as contact
                  JOIN civicrm_email as email ON email.contact_id=contact.id
                WHERE contact_type = 'Individual'
                  AND email = %1", $params, TRUE),
    );
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
    return array("civicrm_contact.{$rg->name}.{$rg->threshold}" => $query);
  }

  /**
   * An alternative version which might perform a lot better
   * than the above. Will need to do some testing
   *
   * @param string $rg
   *
   * @return array
   */
  public static function internalOptimized($rg) {
    $sql = "
CREATE TEMPORARY TABLE emails (
                               email varchar(255),
                               contact_id1 int,
                               contact_id2 int,
                               INDEX(contact_id1),
                               INDEX(contact_id2)
                              ) ENGINE=InnoDB
";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "
INSERT INTO emails
    SELECT email1.email as email, email1.contact_id as contact_id1, email2.contact_id as contact_id2
    FROM civicrm_email as email1
    JOIN civicrm_email as email2 USING (email)
    WHERE email1.contact_id < email2.contact_id
    AND  " . self::internalFilters($rg, "email1.contact_id", "email2.contact_id");
    CRM_Core_DAO::executeQuery($sql);

    $query = "
SELECT contact_id1 as id1, contact_id2 as id2, {$rg->threshold} as weight
FROM   emails
JOIN   civicrm_contact as contact1 on contact1.id=contact_id1
JOIN   civicrm_contact as contact2 on contact2.id=contact_id2
WHERE  contact1.contact_type='Individual'
AND    contact2.contact_type='Individual'
AND    " . self::internalFilters($rg);

    return array("civicrm_contact.{$rg->name}.{$rg->threshold}" => $query);
  }

}
