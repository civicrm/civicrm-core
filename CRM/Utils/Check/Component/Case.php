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
class CRM_Utils_Check_Component_Case extends CRM_Utils_Check_Component {

  const DOCTOR_WHEN = 'https://github.com/civicrm/org.civicrm.doctorwhen';

  /**
   * @var CRM_Case_XMLRepository
   */
  protected $xmlRepo;

  /**
   * @var array<string>
   */
  protected $caseTypeNames;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->caseTypeNames = CRM_Case_PseudoConstant::caseType('name');
    $this->xmlRepo = CRM_Case_XMLRepository::singleton();
  }

  /**
   * @inheritDoc
   */
  public function isEnabled() {
    return CRM_Case_BAO_Case::enabled();
  }

  /**
   * Check that the case-type names don't rely on double-munging.
   *
   * @return array<CRM_Utils_Check_Message>
   *   An empty array, or a list of warnings
   */
  public function checkCaseTypeNameConsistency() {
    $messages = [];

    foreach ($this->caseTypeNames as $caseTypeName) {
      $normalFile = $this->xmlRepo->findXmlFile($caseTypeName);
      $mungedFile = $this->xmlRepo->findXmlFile(CRM_Case_XMLProcessor::mungeCaseType($caseTypeName));

      if ($normalFile && $mungedFile && $normalFile == $mungedFile) {
        // ok
      }
      elseif ($normalFile && $mungedFile) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . $caseTypeName,
          ts('Case type "%1" has duplicate XML files ("%2" and "%3")', [
            1 => $caseTypeName,
            2 => $normalFile,
            3 => $mungedFile,
          ]) .
          '<br /><a href="' . CRM_Utils_System::getWikiBaseURL() . __FUNCTION__ . '">' .
          ts('Read more about this warning') .
          '</a>',
          ts('CiviCase'),
          \Psr\Log\LogLevel::WARNING,
          'fa-puzzle-piece'
        );
      }
      elseif ($normalFile && !$mungedFile) {
        // ok
      }
      elseif (!$normalFile && $mungedFile) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . $caseTypeName,
          ts('Case type "%1" corresponds to XML file ("%2") The XML file should be named "%3".', [
            1 => $caseTypeName,
            2 => $mungedFile,
            3 => "{$caseTypeName}.xml",
          ]) .
          '<br /><a href="' . CRM_Utils_System::getWikiBaseURL() . __FUNCTION__ . '">' .
          ts('Read more about this warning') .
          '</a>',
          ts('CiviCase'),
          \Psr\Log\LogLevel::WARNING,
          'fa-puzzle-piece'
        );
      }
      elseif (!$normalFile && !$mungedFile) {
        // ok -- probably a new or DB-based CaseType
      }
    }

    return $messages;
  }

  /**
   * Check that the timestamp columns are populated. (CRM-20958)
   *
   * @return array<CRM_Utils_Check_Message>
   *   An empty array, or a list of warnings
   */
  public function checkNullTimestamps() {
    $messages = [];

    $nullCount = 0;
    $nullCount += CRM_Utils_SQL_Select::from('civicrm_activity')
      ->where('created_date IS NULL OR modified_date IS NULL')
      ->select('COUNT(*)')
      ->execute()
      ->fetchValue();
    $nullCount += CRM_Utils_SQL_Select::from('civicrm_case')
      ->where('created_date IS NULL OR modified_date IS NULL')
      ->select('COUNT(*)')
      ->execute()
      ->fetchValue();

    if ($nullCount > 0) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        '<p>' .
        ts('The tables "<em>civicrm_activity</em>" and "<em>civicrm_case</em>" were updated to support two new fields, "<em>created_date</em>" and "<em>modified_date</em>". For historical data, these fields may appear blank. (%1 records have NULL timestamps.)', [
          1 => $nullCount,
        ]) .
        '</p><p>' .
        ts('At time of writing, this is not a problem. However, future extensions and improvements could rely on these fields, so it may be useful to back-fill them.') .
        '</p><p>' .
        ts('For further discussion, please visit %1', [
          1 => sprintf('<a href="%s" target="_blank">%s</a>', self::DOCTOR_WHEN, self::DOCTOR_WHEN),
        ]) .
        '</p>',
        ts('Timestamps for Activities and Cases'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-clock-o'
      );
    }

    return $messages;
  }

  /**
   * Check that the relationship types aren't going to cause problems.
   *
   * @return array<CRM_Utils_Check_Message>
   *   An empty array, or a list of warnings
   */
  public function checkRelationshipTypeProblems() {
    $messages = [];

    /**
     * There's no use-case to have two different relationship types
     * with the same machine name, and it will cause problems because the
     * system might match up the wrong type when comparing to xml.
     * A single bi-directional one CAN and probably does have the same
     * name_a_b and name_b_a and that's ok.
     */

    $dao = CRM_Core_DAO::executeQuery("SELECT rt1.*, rt2.id AS id2, rt2.name_a_b AS nameab2, rt2.name_b_a AS nameba2 FROM civicrm_relationship_type rt1 INNER JOIN civicrm_relationship_type rt2 ON (rt1.name_a_b = rt2.name_a_b OR rt1.name_a_b = rt2.name_b_a) WHERE rt1.id <> rt2.id");
    while ($dao->fetch()) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . $dao->id . "dupe1",
        ts("Relationship type <em>%1</em> has the same internal machine name as another type.
          <table>
            <tr><th>ID</th><th>name_a_b</th><th>name_b_a</th></tr>
            <tr><td>%2</td><td>%3</td><td>%4</td></tr>
            <tr><td>%5</td><td>%6</td><td>%7</td></tr>
          </table>", [
            1 => htmlspecialchars($dao->label_a_b),
            2 => $dao->id,
            3 => htmlspecialchars($dao->name_a_b),
            4 => htmlspecialchars($dao->name_b_a),
            5 => $dao->id2,
            6 => htmlspecialchars($dao->nameab2),
            7 => htmlspecialchars($dao->nameba2),
          ]) .
          '<br /><a href="' . CRM_Utils_System::getWikiBaseURL() . __FUNCTION__ . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Internal Name Duplicates'),
        \Psr\Log\LogLevel::WARNING,
        'fa-puzzle-piece'
      );
    }

    // Ditto for labels
    $dao = CRM_Core_DAO::executeQuery("SELECT rt1.*, rt2.id AS id2, rt2.label_a_b AS labelab2, rt2.label_b_a AS labelba2 FROM civicrm_relationship_type rt1 INNER JOIN civicrm_relationship_type rt2 ON (rt1.label_a_b = rt2.label_a_b OR rt1.label_a_b = rt2.label_b_a) WHERE rt1.id <> rt2.id");
    while ($dao->fetch()) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . $dao->id . "dupe2",
        ts("Relationship type <em>%1</em> has the same display label as another type.
          <table>
            <tr><th>ID</th><th>label_a_b</th><th>label_b_a</th></tr>
            <tr><td>%2</td><td>%3</td><td>%4</td></tr>
            <tr><td>%5</td><td>%6</td><td>%7</td></tr>
          </table>", [
            1 => htmlspecialchars($dao->label_a_b),
            2 => $dao->id,
            3 => htmlspecialchars($dao->label_a_b),
            4 => htmlspecialchars($dao->label_b_a),
            5 => $dao->id2,
            6 => htmlspecialchars($dao->labelab2),
            7 => htmlspecialchars($dao->labelba2),
          ]) .
          '<br /><a href="' . CRM_Utils_System::getWikiBaseURL() . __FUNCTION__ . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Display Label Duplicates'),
        \Psr\Log\LogLevel::WARNING,
        'fa-puzzle-piece'
      );
    }

    /**
     * If the name of one type matches the label of another type, there may
     * also be problems. This can happen if for example you initially set
     * it up and then keep changing your mind adding and deleting and renaming
     * a couple times in a certain order.
     */
    $dao = CRM_Core_DAO::executeQuery("SELECT rt1.*, rt2.id AS id2, rt2.name_a_b AS nameab2, rt2.name_b_a AS nameba2, rt2.label_a_b AS labelab2, rt2.label_b_a AS labelba2 FROM civicrm_relationship_type rt1 INNER JOIN civicrm_relationship_type rt2 ON (rt1.name_a_b = rt2.label_a_b OR rt1.name_b_a = rt2.label_a_b OR rt1.name_a_b = rt2.label_b_a OR rt1.name_b_a = rt2.label_b_a) WHERE rt1.id <> rt2.id");
    // No point displaying the same matching id twice, which can happen with
    // the query.
    $ids = [];
    while ($dao->fetch()) {
      if (isset($ids[$dao->id2])) {
        continue;
      }
      $ids[$dao->id] = $dao->id;
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . $dao->id . "dupe3",
        ts("Relationship type <em>%1</em> has an internal machine name that is the same as the display label as another type.
          <table>
            <tr><th>ID</th><th>name_a_b</th><th>name_b_a</th><th>label_a_b</th><th>label_b_a</th></tr>
            <tr><td>%2</td><td>%3</td><td>%4</td><td>%5</td><td>%6</td></tr>
            <tr><td>%7</td><td>%8</td><td>%9</td><td>%10</td><td>%11</td></tr>
          </table>", [
            1 => htmlspecialchars($dao->label_a_b),
            2 => $dao->id,
            3 => htmlspecialchars($dao->name_a_b),
            4 => htmlspecialchars($dao->name_b_a),
            5 => htmlspecialchars($dao->label_a_b),
            6 => htmlspecialchars($dao->label_b_a),
            7 => $dao->id2,
            8 => htmlspecialchars($dao->nameab2),
            9 => htmlspecialchars($dao->nameab2),
            10 => htmlspecialchars($dao->labelab2),
            11 => htmlspecialchars($dao->labelba2),
          ]) .
          '<br /><a href="' . CRM_Utils_System::getWikiBaseURL() . __FUNCTION__ . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Duplicates'),
        \Psr\Log\LogLevel::WARNING,
        'fa-puzzle-piece'
      );
    }

    /**
     * Check that ones that appear to be unidirectional don't have the same
     * machine name for both a_b and b_a. This can happen for example if you
     * forget to fill in the b_a label when creating, then go back and edit.
     */
    $dao = CRM_Core_DAO::executeQuery("SELECT rt1.* FROM civicrm_relationship_type rt1 WHERE rt1.name_a_b = rt1.name_b_a AND rt1.label_a_b <> rt1.label_b_a");
    while ($dao->fetch()) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . $dao->id . "ambiguous",
        ts("Relationship type <em>%1</em> appears to be unidirectional, but has the same internal machine name for both sides.
          <table>
            <tr><th>ID</th><th>name_a_b</th><th>name_b_a</th><th>label_a_b</th><th>label_b_a</th></tr>
            <tr><td>%2</td><td>%3</td><td>%4</td><td>%5</td><td>%6</td></tr>
          </table>", [
            1 => htmlspecialchars($dao->label_a_b),
            2 => $dao->id,
            3 => htmlspecialchars($dao->name_a_b),
            4 => htmlspecialchars($dao->name_b_a),
            5 => htmlspecialchars($dao->label_a_b),
            6 => htmlspecialchars($dao->label_b_a),
          ]) .
          '<br /><a href="' . CRM_Utils_System::getWikiBaseURL() . __FUNCTION__ . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Ambiguity'),
        \Psr\Log\LogLevel::WARNING,
        'fa-puzzle-piece'
      );
    }

    return $messages;
  }

}
