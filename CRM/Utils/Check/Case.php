<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id: $
 *
 */
class CRM_Utils_Check_Case {

  /**
   * @var CRM_Case_XMLRepository
   */
  protected $xmlRepo;

  /**
   * @var array<string>
   */
  protected $caseTypeNames;

  /**
   * @param CRM_Case_XMLRepository $xmlRepo
   * @param array<string> $caseTypeNames
   */
  function __construct($xmlRepo, $caseTypeNames) {
    $this->caseTypeNames = $caseTypeNames;
    $this->xmlRepo = $xmlRepo;
  }

  /**
   * Run some sanity checks.
   *
   * @return array<CRM_Utils_Check_Message>
   */
  public function checkAll() {
    $messages = array_merge(
      $this->checkCaseTypeNameConsistency()
    );
    return $messages;
  }

  /**
   * Check that the case-type names don't rely on double-munging.
   *
   * @return array<CRM_Utils_Check_Message> an empty array, or a list of warnings
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
          __FUNCTION__,
          ts('Case type "%2" has duplicate XML files ("%3" and "%4").<br /><a href="%1">Read more about this warning</a>', array(
            1 => CRM_Utils_System::getWikiBaseURL() . __FUNCTION__,
            2 => $caseTypeName,
            3 => $normalFile,
            4 => $mungedFile,
          )),
          ts('CiviCase')
        );
      }
      elseif ($normalFile && !$mungedFile) {
        // ok
      }
      elseif (!$normalFile && $mungedFile) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts('Case type "%2" corresponds to XML file ("%3") The XML file should be named "%4".<br /><a href="%1">Read more about this warning</a>', array(
            1 => CRM_Utils_System::getWikiBaseURL() . __FUNCTION__,
            2 => $caseTypeName,
            3 => $mungedFile,
            4 => "{$caseTypeName}.xml",
          )),
          ts('CiviCase')
        );
      }
      elseif (!$normalFile && !$mungedFile) {
        // ok -- probably a new or DB-based CaseType
      }
    }

    return $messages;
  }
}