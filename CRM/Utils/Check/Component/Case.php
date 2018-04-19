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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
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
    $messages = array();

    foreach ($this->caseTypeNames as $caseTypeName) {
      $normalFile = $this->xmlRepo->findXmlFile($caseTypeName);
      $mungedFile = $this->xmlRepo->findXmlFile(CRM_Case_XMLProcessor::mungeCaseType($caseTypeName));

      if ($normalFile && $mungedFile && $normalFile == $mungedFile) {
        // ok
      }
      elseif ($normalFile && $mungedFile) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . $caseTypeName,
          ts('Case type "%1" has duplicate XML files ("%2" and "%3")', array(
            1 => $caseTypeName,
            2 => $normalFile,
            3 => $mungedFile,
          )) .
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
          ts('Case type "%1" corresponds to XML file ("%2") The XML file should be named "%3".', array(
            1 => $caseTypeName,
            2 => $mungedFile,
            3 => "{$caseTypeName}.xml",
          )) .
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
    $messages = array();

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
        ts('The tables "<em>civicrm_activity</em>" and "<em>civicrm_case</em>" were updated to support two new fields, "<em>created_date</em>" and "<em>modified_date</em>". For historical data, these fields may appear blank. (%1 records have NULL timestamps.)', array(
          1 => $nullCount,
        )) .
        '</p><p>' .
        ts('At time of writing, this is not a problem. However, future extensions and improvements could rely on these fields, so it may be useful to back-fill them.') .
        '</p><p>' .
        ts('For further discussion, please visit %1', array(
          1 => sprintf('<a href="%s" target="_blank">%s</a>', self::DOCTOR_WHEN, self::DOCTOR_WHEN),
        )) .
        '</p>',
        ts('Timestamps for Activities and Cases'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-clock-o'
      );
    }

    return $messages;
  }

}
