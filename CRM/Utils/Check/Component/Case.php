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
class CRM_Utils_Check_Component_Case extends CRM_Utils_Check_Component {

  const DOCTOR_WHEN = 'https://github.com/civicrm/org.civicrm.doctorwhen';

  /**
   * @var CRM_Case_XMLRepository
   */
  protected $xmlRepo;

  /**
   * @var string[]
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
    return CRM_Core_Component::isEnabled('CiviCase');
  }

  /**
   * Check that the case-type names don't rely on double-munging.
   *
   * @return CRM_Utils_Check_Message[]
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
   * @return CRM_Utils_Check_Message[]
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
   * @return CRM_Utils_Check_Message[]
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
          '<br /><a href="' . CRM_Utils_System::docURL2('user/case-management/what-you-need-to-know#relationship-type-internal-name-duplicates', TRUE) . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Internal Name Duplicates'),
        \Psr\Log\LogLevel::ERROR,
        'fa-exchange'
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
          '<br /><a href="' . CRM_Utils_System::docURL2('user/case-management/what-you-need-to-know#relationship-type-display-label-duplicates', TRUE) . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Display Label Duplicates'),
        \Psr\Log\LogLevel::ERROR,
        'fa-exchange'
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
          '<br /><a href="' . CRM_Utils_System::docURL2('user/case-management/what-you-need-to-know#relationship-type-cross-duplication', TRUE) . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Cross-Duplication'),
        \Psr\Log\LogLevel::WARNING,
        'fa-exchange'
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
        __FUNCTION__ . $dao->id . "ambiguousname",
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
          '<br /><a href="' . CRM_Utils_System::docURL2('user/case-management/what-you-need-to-know#relationship-type-ambiguity', TRUE) . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Ambiguity'),
        \Psr\Log\LogLevel::WARNING,
        'fa-exchange'
      );
    }

    /**
     * Check that ones that appear to be unidirectional don't have the same
     * label for both a_b and b_a. This can happen for example if you
     * created it as unidirectional, then edited it later trying to make it
     * bidirectional.
     */
    $dao = CRM_Core_DAO::executeQuery("SELECT rt1.* FROM civicrm_relationship_type rt1 WHERE rt1.label_a_b = rt1.label_b_a AND rt1.name_a_b <> rt1.name_b_a");
    while ($dao->fetch()) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . $dao->id . "ambiguouslabel",
        ts("Relationship type <em>%1</em> appears to be unidirectional internally, but has the same display label for both sides. Possibly you created it initially as unidirectional and then made it bidirectional later.
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
          '<br /><a href="' . CRM_Utils_System::docURL2('user/case-management/what-you-need-to-know#relationship-type-ambiguity', TRUE) . '">' .
          ts('Read more about this warning') .
          '</a>',
        ts('Relationship Type Ambiguity'),
        \Psr\Log\LogLevel::WARNING,
        'fa-exchange'
      );
    }

    /**
     * Check for missing roles listed in the xml but not defined as
     * relationship types.
     */

    // Don't use database since might be in xml files.
    $caseTypes = civicrm_api3('CaseType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];
    // Don't use pseudoconstant since want all and also name and label.
    $relationshipTypes = civicrm_api3('RelationshipType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];
    $allConfigured = array_column($relationshipTypes, 'id', 'name_a_b')
      + array_column($relationshipTypes, 'id', 'name_b_a')
      + array_column($relationshipTypes, 'id', 'label_a_b')
      + array_column($relationshipTypes, 'id', 'label_b_a');
    $missing = [];
    foreach ($caseTypes as $caseType) {
      foreach ($caseType['definition']['caseRoles'] ?? [] as $role) {
        if (!isset($allConfigured[$role['name']])) {
          $missing[$role['name']] = $role['name'];
        }
      }
    }
    if (!empty($missing)) {
      $tableRows = [];
      foreach ($relationshipTypes as $relationshipType) {
        $tableRows[] = ts('<tr><td>%1</td><td>%2</td><td>%3</td><td>%4</td><td>%5</td></tr>', [
          1 => $relationshipType['id'],
          2 => htmlspecialchars($relationshipType['name_a_b']),
          3 => htmlspecialchars($relationshipType['name_b_a']),
          4 => htmlspecialchars($relationshipType['label_a_b']),
          5 => htmlspecialchars($relationshipType['label_b_a']),
        ]);
      }
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . "missingroles",
        '<p>' . ts("The following roles listed in your case type definitions do not match any relationship type defined in the system: <em>%1</em>.", [1 => htmlspecialchars(implode(', ', $missing))]) . '</p>'
          . "<p>" . ts("This might be because of a mismatch if you are using external xml files to manage case types. If using xml files, then use either the name_a_b or name_b_a value from the following table. (Out of the box you would use name_b_a, which lists them on the case from the client perspective.) If you are not using xml files, you can edit your case types at Administer - CiviCase - Case Types.") . '</p>'
          . '<table>'
          . '<tr><th>ID</th><th>name_a_b</th><th>name_b_a</th><th>label_a_b</th><th>label_b_a</th></tr>'
          . implode("\n", $tableRows)
          . '</table>'
          . '<br /><a href="' . CRM_Utils_System::docURL2('user/case-management/what-you-need-to-know#missing-roles', TRUE) . '">'
          . ts('Read more about this warning')
          . '</a>',
        ts('Missing Roles'),
        \Psr\Log\LogLevel::ERROR,
        'fa-exclamation'
      );
    }

    return $messages;
  }

  /**
   * Check any xml definitions stored as external files to see if they
   * have label as the role and where the label is different from the name.
   * We don't have to think about edge cases because there are already
   * status checks above for those.
   *
   * @return CRM_Utils_Check_Message[]
   *   An empty array, or a list of warnings
   */
  public function checkExternalXmlFileRoleNames() {
    $messages = [];

    // Get config for relationship types
    $relationship_types = civicrm_api3('RelationshipType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];
    // keyed on name, with id as the value, e.g. 'Case Coordinator is' => 10
    $names_a_b = array_column($relationship_types, 'id', 'name_a_b');
    $names_b_a = array_column($relationship_types, 'id', 'name_b_a');
    $labels_a_b = array_column($relationship_types, 'id', 'label_a_b');
    $labels_b_a = array_column($relationship_types, 'id', 'label_b_a');

    $dao = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_case_type WHERE definition IS NULL OR definition=''");
    while ($dao->fetch()) {
      $case_type = civicrm_api3('CaseType', 'get', [
        'id' => $dao->id,
      ])['values'][$dao->id];
      if (empty($case_type['definition'])) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . "missingcasetypedefinition",
          '<p>' . ts('Unable to locate xml file for Case Type "<em>%1</em>".',
          [
            1 => htmlspecialchars(empty($case_type['title']) ? $dao->id : $case_type['title']),
          ]) . '</p>',
          ts('Missing Case Type Definition'),
          \Psr\Log\LogLevel::ERROR,
          'fa-exclamation'
        );
        continue;
      }

      if (empty($case_type['definition']['caseRoles'])) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . "missingcaseroles",
          '<p>' . ts('CaseRoles seems to be missing in the xml file for Case Type "<em>%1</em>".',
          [
            1 => htmlspecialchars(empty($case_type['title']) ? $dao->id : $case_type['title']),
          ]) . '</p>',
          ts('Missing Case Roles'),
          \Psr\Log\LogLevel::ERROR,
          'fa-exclamation'
        );
        continue;
      }

      // Loop thru each role in the xml.
      foreach ($case_type['definition']['caseRoles'] as $role) {
        $name_to_suggest = NULL;
        $xml_name = $role['name'];
        if (isset($names_a_b[$xml_name]) || isset($names_b_a[$xml_name])) {
          // It matches a name, so either name and label are the same or it's
          // an edge case already dealt with by core status checks, so do
          // nothing.
          continue;
        }
        elseif (isset($labels_b_a[$xml_name])) {
          // $labels_b_a[$xml_name] gives us the id, so then look up name_b_a
          // from the original relationship_types array which is keyed on id.
          // We do b_a first because it's the more standard one, although it
          // will only make a difference in edge cases which we leave to the
          // other checks.
          $name_to_suggest = $relationship_types[$labels_b_a[$xml_name]]['name_b_a'];
        }
        elseif (isset($labels_a_b[$xml_name])) {
          $name_to_suggest = $relationship_types[$labels_a_b[$xml_name]]['name_a_b'];
        }

        // If it didn't match any name or label then that's weird.
        if (empty($name_to_suggest)) {
          $messages[] = new CRM_Utils_Check_Message(
            __FUNCTION__ . "invalidcaserole",
            '<p>' . ts('CaseRole "<em>%1</em>" in the xml file for Case Type "<em>%2</em>" doesn\'t seem to match any existing relationship type.',
            [
              1 => htmlspecialchars($xml_name),
              2 => htmlspecialchars(empty($case_type['title']) ? $dao->id : $case_type['title']),
            ]) . '</p>',
            ts('Invalid Case Role'),
            \Psr\Log\LogLevel::ERROR,
            'fa-exclamation'
          );
        }
        else {
          $messages[] = new CRM_Utils_Check_Message(
            __FUNCTION__ . "suggestedchange",
            '<p>' . ts('Please edit the XML file for case type "<em>%2</em>" so that the case role label "<em>%1</em>" is changed to its corresponding name "<em>%3</em>". Using label is deprecated as of version 5.20.',
            [
              1 => htmlspecialchars($xml_name),
              2 => htmlspecialchars(empty($case_type['title']) ? $dao->id : $case_type['title']),
              3 => htmlspecialchars($name_to_suggest),
            ]) . '</p>',
            ts('Case Role using display label instead of internal machine name'),
            \Psr\Log\LogLevel::WARNING,
            'fa-code'
          );
        }
      }
    }
    return $messages;
  }

  /**
   * At some point the valid names changed so that you can't have e.g. spaces.
   * For systems upgraded that use external xml files it's then not clear why
   * the other messages about outdated filenames are coming up because when
   * you then fix it as suggested it then gives a red error just saying it
   * can't find it.
   */
  public function checkCaseTypeNameValidity() {
    $messages = [];
    $dao = CRM_Core_DAO::executeQuery("SELECT id, name, title FROM civicrm_case_type");
    while ($dao->fetch()) {
      if (!CRM_Case_BAO_CaseType::isValidName($dao->name)) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . "invalidcasetypename",
          '<p>' . ts('Case Type "<em>%1</em>" has invalid characters in the internal machine name (<em>%2</em>). Only letters, numbers, and underscore are allowed.',
          [
            1 => htmlspecialchars(empty($dao->title) ? $dao->id : $dao->title),
            2 => htmlspecialchars($dao->name),
          ]) . '</p>',
          ts('Invalid Case Type Name'),
          \Psr\Log\LogLevel::ERROR,
          'fa-exclamation'
        );
      }
    }
    return $messages;
  }

}
