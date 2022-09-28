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
class CRM_Case_XMLProcessor {

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
   * Get all relationship type display labels (not machine names)
   *
   * @param bool $fromXML
   *   TODO: This parameter is always FALSE now so no longer needed.
   *   Is this to be used for lookup of values from XML?
   *   Relationships are recorded in XML from the perspective of the non-client
   *   while relationships in the UI and everywhere else are from the
   *   perspective of the client.  Since the XML can't be expected to be
   *   switched, the direction needs to be translated.
   * @return array
   */
  public function &allRelationshipTypes($fromXML = FALSE) {
    if (!isset(Civi::$statics[__CLASS__]['reltypes'][$fromXML])) {
      // Note this now includes disabled types too. The only place this
      // function is being used is for comparison against a list, not
      // displaying a dropdown list or something like that, so we need
      // to include disabled.
      $relationshipInfo = civicrm_api3('RelationshipType', 'get', [
        'options' => ['limit' => 0],
      ]);

      Civi::$statics[__CLASS__]['reltypes'][$fromXML] = [];
      foreach ($relationshipInfo['values'] as $id => $info) {
        Civi::$statics[__CLASS__]['reltypes'][$fromXML][$id . '_b_a'] = ($fromXML) ? $info['label_a_b'] : $info['label_b_a'];
        /**
         * Exclude if bidirectional
         * (Why? I'm thinking this was for consistency with the dropdown
         * in ang/crmCaseType.js where it would be needed to avoid seeing
         * duplicates in the dropdown. Not sure if needed here but keeping
         * as-is.)
         */
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
    unset(Civi::$statics[__CLASS__]['reltypes']);
  }

}
