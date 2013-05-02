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
 * $Id: $
 *
 */
class CRM_Utils_Type {
  CONST
    T_INT        = 1,
    T_STRING     = 2,
    T_ENUM       = 2,
    T_DATE       = 4,
    T_TIME       = 8,
    T_BOOL       = 16,
    T_BOOLEAN    = 16,
    T_TEXT       = 32,
    T_LONGTEXT   = 32,
    T_BLOB       = 64,
    T_TIMESTAMP  = 256,
    T_FLOAT      = 512,
    T_MONEY      = 1024,
    T_EMAIL      = 2048,
    T_URL        = 4096,
    T_CCNUM      = 8192,
    T_MEDIUMBLOB = 16384;

  CONST
    TWO       = 2,
    FOUR      = 4,
    SIX       = 6,
    EIGHT     = 8,
    TWELVE    = 12,
    SIXTEEN   = 16,
    TWENTY    = 20,
    MEDIUM    = 20,
    THIRTY    = 30,
    BIG       = 30,
    FORTYFIVE = 45,
    HUGE      = 45;

  /**
   * Convert Constant Data type to String
   *
   * @param  $type       integer datatype
   *
   * @return $string     String datatype respective to integer datatype
   *
   * @access public
   * @static
   */
  static function typeToString($type) {
    switch ($type) {
      case 1:
        $string = 'Int';
        break;

      case 2:
        $string = 'String';
        break;

      case 3:
        $string = 'Enum';
        break;

      case 4:
        $string = 'Date';
        break;

      case 8:
        $string = 'Time';
        break;

      case 16:
        $string = 'Boolean';
        break;

      case 32:
        $string = 'Text';
        break;

      case 64:
        $string = 'Blob';
        break;

        // CRM-10404
      case 12:
      case 256:
        $string = 'Timestamp';
        break;

      case 512:
        $string = 'Float';
        break;

      case 1024:
        $string = 'Money';
        break;

      case 2048:
        $string = 'Date';
        break;

      case 4096:
        $string = 'Email';
        break;

      case 16384:
        $string = 'Mediumblob';
        break;
    }

    return (isset($string)) ? $string : "";
  }

  /**
   * Verify that a variable is of a given type
   *
   * @param mixed   $data         The variable
   * @param string  $type         The type
   * @param boolean $abort        Should we abort if invalid
   *
   * @return mixed                The data, escaped if necessary
   * @access public
   * @static
   */
  public static function escape($data, $type, $abort = TRUE) {
    switch ($type) {
      case 'Integer':
      case 'Int':
        if (CRM_Utils_Rule::integer($data)) {
          return $data;
        }
        break;

      case 'Positive':
        // the below 2 are for custom fields of this type
        // CRM-8925
      case 'Country':
      case 'StateProvince':
        if (CRM_Utils_Rule::positiveInteger($data)) {
          return $data;
        }
        break;

      case 'Link':
        if (CRM_Utils_Rule::url($data = trim($data))) {
          return $data;
        }
        break;

      case 'Boolean':
        if (CRM_Utils_Rule::boolean($data)) {
          return $data;
        }
        break;

      case 'Float':
      case 'Money':
        if (CRM_Utils_Rule::numeric($data)) {
          return $data;
        }
        break;

      case 'String':
      case 'Memo':
        return CRM_Core_DAO::escapeString($data);

      case 'Date':
      case 'Timestamp':
        // a null date or timestamp is valid
        if (strlen(trim($data)) == 0) {
          return trim($data);
        }

        if ((preg_match('/^\d{8}$/', $data) ||
            preg_match('/^\d{14}$/', $data)
          ) &&
          CRM_Utils_Rule::mysqlDate($data)
        ) {
          return $data;
        }
        break;

      case 'ContactReference':
        if (strlen(trim($data)) == 0) {
          return trim($data);
        }

        if (CRM_Utils_Rule::validContact($data)) {
          return $data;
        }
        break;

      default:
        CRM_Core_Error::fatal("Cannot recognize $type for $data");
        break;
    }

    if ($abort) {
      $data = htmlentities($data);
      CRM_Core_Error::fatal("$data is not of the type $type");
    }
    return NULL;
  }

  /**
   * Verify that a variable is of a given type
   *
   * @param mixed   $data         The variable
   * @param string  $type         The type
   * @param boolean $abort        Should we abort if invalid
   * @name string   $name	    The name of the attribute
   *
   * @return mixed                The data, escaped if necessary
   * @access public
   * @static
   */
  public static function validate($data, $type, $abort = TRUE, $name = 'One of parameters ') {
    switch ($type) {
      case 'Integer':
      case 'Int':
        if (CRM_Utils_Rule::integer($data)) {
          return $data;
        }
        break;

      case 'Positive':
        if (CRM_Utils_Rule::positiveInteger($data)) {
          return $data;
        }
        break;

      case 'Boolean':
        if (CRM_Utils_Rule::boolean($data)) {
          return $data;
        }
        break;

      case 'Float':
      case 'Money':
        if (CRM_Utils_Rule::numeric($data)) {
          return $data;
        }
        break;

      case 'Text':
      case 'String':
      case 'Link':
      case 'Memo':
        return $data;

      case 'Date':
        // a null date is valid
        if (strlen(trim($data)) == 0) {
          return trim($data);
        }

        if (preg_match('/^\d{8}$/', $data) &&
          CRM_Utils_Rule::mysqlDate($data)
        ) {
          return $data;
        }
        break;

      case 'Timestamp':
        // a null timestamp is valid
        if (strlen(trim($data)) == 0) {
          return trim($data);
        }

        if ((preg_match('/^\d{14}$/', $data) ||
            preg_match('/^\d{8}$/', $data)
          ) &&
          CRM_Utils_Rule::mysqlDate($data)
        ) {
          return $data;
        }
        break;

      case 'ContactReference':
        // null is valid
        if (strlen(trim($data)) == 0) {
          return trim($data);
        }

        if (CRM_Utils_Rule::validContact($data)) {
          return $data;
        }
        break;

      default:
        CRM_Core_Error::fatal("Cannot recognize $type for $data");
        break;
    }

    if ($abort) {
      $data = htmlentities($data);
      CRM_Core_Error::fatal("$name (value: $data) is not of the type $type");
    }

    return NULL;
  }
}

