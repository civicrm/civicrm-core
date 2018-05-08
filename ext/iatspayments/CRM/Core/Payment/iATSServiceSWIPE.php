<?php

/**
 * @file Copyright iATS Payments (c) 2014.
 * @author Alan Dixon
 *
 * This file is a part of CiviCRM published extension.
 *
 * This extension is free software; you can copy, modify, and distribute it
 * under the terms of the GNU Affero General Public License
 * Version 3, 19 November 2007.
 *
 * It is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License with this program; if not, see http://www.gnu.org/licenses/
 *
 * This code provides glue between CiviCRM payment model and the iATS Payment model encapsulated in the iATS_Service_Request object
 */

/**
 *
 */
class CRM_Core_Payment_iATSServiceSWIPE extends CRM_Core_Payment_iATSService {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable.
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *   the mode of operation: live or test.
   *
   * @return void
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('iATS Payments SWIPE');

    // Get merchant data from config.
    $config = CRM_Core_Config::singleton();
    // Live or test.
    $this->_profile['mode'] = $mode;
    // We only use the domain of the configured url, which is different for NA vs. UK.
    $this->_profile['iats_domain'] = parse_url($this->_paymentProcessor['url_site'], PHP_URL_HOST);
  }

  /**
   *
   */
  static public function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL, $force = FALSE) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_iATSServiceSWIPE($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   *
   */
  public function validatePaymentInstrument($values, &$errors) {
    // Override the default and don't do any validation because my values are encrypted.
  }

}
