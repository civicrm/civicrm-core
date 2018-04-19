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

use Civi\Cxn\Rpc\Constants;
use Civi\Cxn\Rpc\DefaultCertificateValidator;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class helps to manage connections to third-party apps.
 */
class CRM_Cxn_BAO_Cxn extends CRM_Cxn_DAO_Cxn {

  /**
   * Determine the current site's callback URL.
   *
   * @return string
   */
  public static function getSiteCallbackUrl() {
    $config = CRM_Core_Config::singleton();

    if (preg_match('/^(http|https):/', $config->resourceBase)) {
      $civiUrl = $config->resourceBase;
    }
    else {
      $civiUrl = rtrim(CRM_Utils_System::baseURL(), '/') . '/' . ltrim($config->resourceBase, '/');
    }

    // In practice, this may not be necessary, but we want to prevent
    // edge-cases that downgrade security-level below system policy.
    if (Civi::settings()->get('enableSSL')) {
      $civiUrl = preg_replace('/^http:/', 'https:', $civiUrl);
    }

    return rtrim($civiUrl, '/') . '/extern/cxn.php';
  }

  /**
   * Update the AppMeta for any existing connections.
   *
   * @param array $appMeta
   * @throws \Civi\Cxn\Rpc\Exception\CxnException
   */
  public static function updateAppMeta($appMeta) {
    \Civi\Cxn\Rpc\AppMeta::validate($appMeta);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_cxn SET app_meta = %1 WHERE app_guid = %2', array(
      1 => array(json_encode($appMeta), 'String'),
      2 => array($appMeta['appId'], 'String'),
    ));
  }

  /**
   * Get the AppMeta for an existing connection.
   *
   * @param string $cxnId
   * @return array
   * @throws \Civi\Cxn\Rpc\Exception\CxnException
   */
  public static function getAppMeta($cxnId) {
    $appMetaJson = CRM_Core_DAO::getFieldValue('CRM_Cxn_DAO_Cxn', $cxnId, 'app_meta', 'cxn_guid', TRUE);
    $appMeta = json_decode($appMetaJson, TRUE);
    \Civi\Cxn\Rpc\AppMeta::validate($appMeta);
    return $appMeta;
  }

  /**
   * Parse the CIVICRM_CXN_CA constant. It may have the following
   * values:
   *   - 'CiviRootCA'|undefined -- Use the production civicrm.org root CA
   *   - 'CiviTestRootCA' -- Use the test civicrm.org root CA
   *   - 'none' -- Do not perform any certificate verification.
   *
   * This constant is emphatically *not* exposed through Civi's "Settings"
   * system (or any other runtime-editable datastore). Manipulating
   * this setting can expose the system to man-in-the-middle attacks,
   * and allowing runtime manipulation would create a new vector
   * for escalating privileges. This setting must only be manipulated
   * by developers and sysadmins who already have full privileges
   * to edit the source.
   *
   * @return string|NULL
   *   The PEM-encoded root certificate. NULL if verification is disabled.
   * @throws CRM_Core_Exception
   */
  public static function getCACert() {
    if (!defined('CIVICRM_CXN_CA') || CIVICRM_CXN_CA === 'CiviRootCA') {
      $file = Constants::getCert();
    }
    elseif (CIVICRM_CXN_CA === 'CiviTestRootCA') {
      $file = Constants::getTestCert();
    }
    elseif (CIVICRM_CXN_CA === 'none') {
      return NULL;
    }
    else {
      throw new \CRM_Core_Exception("CIVICRM_CXN_CA is invalid.");
    }

    $content = file_get_contents($file);
    if (empty($content)) {
      // Fail hard. Returning an empty value is not acceptable.
      throw new \CRM_Core_Exception("Error loading CA certificate: $file");
    }
    return $content;
  }

  /**
   * Construct a client for performing registration actions.
   *
   * @return \Civi\Cxn\Rpc\RegistrationClient
   * @throws CRM_Core_Exception
   */
  public static function createRegistrationClient() {
    $cxnStore = new \CRM_Cxn_CiviCxnStore();
    $viaPort = defined('CIVICRM_CXN_VIA') ? CIVICRM_CXN_VIA : NULL;
    $client = new \Civi\Cxn\Rpc\RegistrationClient(
      $cxnStore, \CRM_Cxn_BAO_Cxn::getSiteCallbackUrl(), $viaPort);
    $client->setLog(new \CRM_Utils_SystemLogger());
    $client->setCertValidator(self::createCertificateValidator());
    $client->setHttp(CRM_Cxn_CiviCxnHttp::singleton());
    return $client;
  }

  /**
   * Construct a server for handling API requests.
   *
   * @return \Civi\Cxn\Rpc\ApiServer
   */
  public static function createApiServer() {
    $cxnStore = new CRM_Cxn_CiviCxnStore();
    $apiServer = new \Civi\Cxn\Rpc\ApiServer($cxnStore);
    $apiServer->setLog(new CRM_Utils_SystemLogger());
    $apiServer->setCertValidator(self::createCertificateValidator());
    $apiServer->setHttp(CRM_Cxn_CiviCxnHttp::singleton());
    $apiServer->setRouter(array('CRM_Cxn_ApiRouter', 'route'));
    return $apiServer;
  }

  /**
   * @return DefaultCertificateValidator
   * @throws CRM_Core_Exception
   */
  public static function createCertificateValidator() {
    $caCert = self::getCACert();
    if ($caCert === NULL) {
      return new DefaultCertificateValidator(
        NULL,
        NULL,
        NULL,
        NULL
      );
    }
    else {
      return new DefaultCertificateValidator(
        $caCert,
        DefaultCertificateValidator::AUTOLOAD,
        DefaultCertificateValidator::AUTOLOAD,
        CRM_Cxn_CiviCxnHttp::singleton()
      );
    }
  }

}
