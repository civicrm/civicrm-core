<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Case_XMLProcessor {

  /**
   * FIXME: This does *NOT* belong in a static property, but we're too late in
   * the 4.5-cycle to do the necessary cleanup.
   *
   * Format is [int $id => string $relTypeCname].
   *
   * @var array|null
   */
  public static $activityTypes = NULL;

  /**
   * @param $caseType
   *
   * @return FALSE|SimpleXMLElement
   */
  public function retrieve($caseType) {
    return CRM_Case_XMLRepository::singleton()->retrieve($caseType);
  }

  /**
   * This function was previously used to convert a case-type's
   * machine-name to a file-name. However, it's mind-boggling
   * that the file-name might be a munged version of the
   * machine-name (which is itself a munged version of the
   * display-name), and naming is now a more visible issue (since
   * the overhaul of CaseType admin UI).
   *
   * Usage note: This is called externally by civix stubs as a
   * sort of side-ways validation of the case-type's name
   * (validation which was needed because of the unintuitive
   * double-munge). We should update civix templates and then
   * remove this function in Civi 4.6 or 5.0.
   *
   * @param string $caseType
   * @return string
   * @deprecated
   * @see CRM_Case_BAO_CaseType::isValidName
   */
  public static function mungeCaseType($caseType) {
    // trim all spaces from $caseType
    $caseType = str_replace('_', ' ', $caseType);
    $caseType = CRM_Utils_String::munge(ucwords($caseType), '', 0);
    return $caseType;
  }

  /**
   * @param bool $indexName
   * @param bool $all
   *
   * @return array
   */
  public function &allActivityTypes($indexName = TRUE, $all = FALSE) {
    if (self::$activityTypes === NULL) {
      self::$activityTypes = CRM_Case_PseudoConstant::caseActivityType($indexName, $all);
    }
    return self::$activityTypes;
  }

  /**
   * Get all relationship type labels
   *
   * TODO: These should probably be names, but under legacy behavior this has
   * been labels.
   *
   * @param bool $fromXML
   *   Is this to be used for lookup of values from XML?
   *   Relationships are recorded in XML from the perspective of the non-client
   *   while relationships in the UI and everywhere else are from the
   *   perspective of the client.  Since the XML can't be expected to be
   *   switched, the direction needs to be translated.
   * @return array
   */
  public function &allRelationshipTypes($fromXML = FALSE) {
    if (!isset(Civi::$statics[__CLASS__]['reltypes'][$fromXML])) {
      $relationshipInfo = CRM_Core_PseudoConstant::relationshipType('label', TRUE);

      Civi::$statics[__CLASS__]['reltypes'][$fromXML] = [];
      foreach ($relationshipInfo as $id => $info) {
        Civi::$statics[__CLASS__]['reltypes'][$fromXML][$id . '_b_a'] = ($fromXML) ? $info['label_a_b'] : $info['label_b_a'];
        if ($info['label_b_a'] !== $info['label_a_b']) {
          Civi::$statics[__CLASS__]['reltypes'][$fromXML][$id . '_a_b'] = ($fromXML) ? $info['label_b_a'] : $info['label_a_b'];
        }
      }
    }

    return Civi::$statics[__CLASS__]['reltypes'][$fromXML];
  }

  /**
   * FIXME: This should not exist
   */
  public static function flushStaticCaches() {
    self::$activityTypes = NULL;
    unset(Civi::$statics[__CLASS__]['reltypes']);
  }

}
