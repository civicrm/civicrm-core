<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */
class VAN_Auth {
  static $_header;

  static $_client;
  CONST DATABASE_NODE = 'MyVoterFile', WSDL_URL = 'https://secure.securevan.com/Services/V3/PersonService.asmx?wsdl', VAN_NS = 'https://api.securevan.com/Services/V3/';

  static
  function initialize() {
    static $initialized = FALSE;

    if ($initialized) {
      return;
    }

    require_once 'van/apiKey.php';

    $initialized = TRUE;
    $headerParams = array('APIKey' => new SOAPVar(VAN_API_KEY,
        XSD_STRING, NULL, NULL, NULL,
        self::VAN_NS
      ),
      'DatabaseMode' => new SOAPVar(self::DATABASE_NODE,
        XSD_STRING, NULL, NULL, NULL,
        self::VAN_NS
      ),
    );
    self::$_header = new SOAPHeader(self::VAN_NS,
      'Header',
      new SOAPVar($headerParams,
        SOAP_ENC_OBJECT
      )
    );

    self::$_client = new SOAPClient(self::WSDL_URL,
      array('trace' => TRUE)
    );
    self::$_client->__setSoapHeaders(array(self::$_header));
  }

  static
  function invoke($method, &$params) {
    self::initialize();
    return self::$_client->$method($params);
  }
}

