<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
 * $Id$
 *
 * The XMLRepository is responsible for loading XML for case-types.
 */
class CRM_Case_XMLRepository {
  private static $singleton;

  /**
   * @var array<String,SimpleXMLElement>
   */
  protected $xml = array();

  /**
   * @var array|NULL
   */
  protected $hookCache = NULL;

  /**
   * @var array|NULL symbolic names of case-types
   */
  protected $allCaseTypes = NULL;

  /**
   * @param bool $fresh
   * @return CRM_Case_XMLProcessor
   */
  public static function singleton($fresh = FALSE) {
    if (!self::$singleton || $fresh) {
      self::$singleton = new static();
    }
    return self::$singleton;
  }

  /**
   * @param array<String,SimpleXMLElement> $xml
   */
  public function __construct($xml = array()) {
    $this->xml = $xml;
  }

  /**
   * @param string $caseType
   * @return SimpleXMLElement|FALSE
   */
  public function retrieve($caseType) {
    $caseType = CRM_Case_XMLProcessor::mungeCaseType($caseType);

    if (!CRM_Utils_Array::value($caseType, $this->xml)) {
      // first check custom templates directory
      $fileName = NULL;
      $config = CRM_Core_Config::singleton();
      if (isset($config->customTemplateDir) &&
        $config->customTemplateDir
      ) {
        // check if the file exists in the custom templates directory
        $fileName = implode(DIRECTORY_SEPARATOR,
          array(
            $config->customTemplateDir,
            'CRM',
            'Case',
            'xml',
            'configuration',
            "$caseType.xml",
          )
        );
      }

      if (!$fileName ||
        !file_exists($fileName)
      ) {
        // check if file exists locally
        $fileName = implode(DIRECTORY_SEPARATOR,
          array(
            dirname(__FILE__),
            'xml',
            'configuration',
            "$caseType.xml",
          )
        );

        if (!file_exists($fileName)) {
          // check if file exists locally
          $fileName = implode(DIRECTORY_SEPARATOR,
            array(
              dirname(__FILE__),
              'xml',
              'configuration.sample',
              "$caseType.xml",
            )
          );
        }

        if (!file_exists($fileName)) {
          $caseTypesViaHook = $this->getCaseTypesViaHook();
          if (isset($caseTypesViaHook[$caseType], $caseTypesViaHook[$caseType]['file'])) {
            $fileName = $caseTypesViaHook[$caseType]['file'];
          }
        }

        if (!file_exists($fileName)) {
          return FALSE;
        }
      }

      // read xml file
      $dom = new DomDocument();
      $dom->load($fileName);
      $dom->xinclude();
      $this->xml[$caseType] = simplexml_import_dom($dom);
    }
    return $this->xml[$caseType];
  }

  /**
   * @return array
   * @see CRM_Utils_Hook::caseTypes
   */
  public function getCaseTypesViaHook() {
    if ($this->hookCache === NULL) {
      $this->hookCache = array();
      CRM_Utils_Hook::caseTypes($this->hookCache);
    }
    return $this->hookCache;
  }
}
