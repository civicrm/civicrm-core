<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Case_XMLProcessor {

  static protected $_xml;

  function retrieve($caseType) {
    // trim all spaces from $caseType
    $caseType = str_replace('_', ' ', $caseType);
    $caseType = CRM_Utils_String::munge(ucwords($caseType), '', 0);

    if (!CRM_Utils_Array::value($caseType, self::$_xml)) {
      if (!self::$_xml) {
        self::$_xml = array();
      }

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
          array(dirname(__FILE__),
            'xml',
            'configuration',
            "$caseType.xml",
          )
        );

        if (!file_exists($fileName)) {
          // check if file exists locally
          $fileName = implode(DIRECTORY_SEPARATOR,
            array(dirname(__FILE__),
              'xml',
              'configuration.sample',
              "$caseType.xml",
            )
          );
        }
        if (!file_exists($fileName)) {
          return FALSE;
        }
      }

      // read xml file
      $dom = new DomDocument();
      $dom->load($fileName);
      $dom->xinclude();
      self::$_xml[$caseType] = simplexml_import_dom($dom);
    }
    return self::$_xml[$caseType];
  }

  function &allActivityTypes($indexName = TRUE, $all = FALSE) {
    static $activityTypes = NULL;
    if (!$activityTypes) {
      $activityTypes = CRM_Case_PseudoConstant::caseActivityType($indexName, $all);
    }
    return $activityTypes;
  }

  function &allRelationshipTypes() {
    static $relationshipTypes = array();

    if (!$relationshipTypes) {
      $relationshipInfo = CRM_Core_PseudoConstant::relationshipType();

      $relationshipTypes = array();
      foreach ($relationshipInfo as $id => $info) {
        $relationshipTypes[$id] = $info['label_b_a'];
      }
    }

    return $relationshipTypes;
  }
}

