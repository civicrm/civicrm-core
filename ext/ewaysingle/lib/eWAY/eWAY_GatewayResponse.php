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
 * Licensed to CiviCRM under the Academic Free License version 3.0
 * Written & Contributed by Dolphin Software P/L - March 2008
 *
 * 'eWAY_GatewayResponse.php' - Loosley based on the standard supplied eWay sample code 'GatewayResponse.php'
 *
 * The 'simplexml_load_string' has been removed as it was causing major issues
 * with Drupal V5.7 / CiviCRM 1.9 installtion's Home page.
 * Filling the Home page with "Warning: session_start() [function.session-start]: Node no longer exists in ..." messages
 *
 * Found web reference indicating 'simplexml_load_string' was a probable cause.
 * As soon as 'simplexml_load_string' was removed the problem fixed itself.
 *
 * Additionally the '$txStatus' var has been set as a string rather than a boolean.
 * This is because the returned $params['trxn_result_code'] is in fact a string and not a boolean.
 */
class GatewayResponse {
  public $txAmount              = 0;
  public $txTransactionNumber   = "";
  public $txInvoiceReference    = "";
  public $txOption1             = "";
  public $txOption2             = "";
  public $txOption3             = "";
  public $txStatus              = "";
  public $txAuthCode            = "";
  public $txError               = "";
  public $txBeagleScore         = "";

  public function __construct() {
    // Empty Constructor
  }

  public function ProcessResponse($Xml) {
    //$xtr = simplexml_load_string($Xml) or die ("Unable to load XML string!");

    //$this->txError             = $xtr->ewayTrxnError;
    //$this->txStatus            = $xtr->ewayTrxnStatus;
    //$this->txTransactionNumber = $xtr->ewayTrxnNumber;
    //$this->txOption1           = $xtr->ewayTrxnOption1;
    //$this->txOption2           = $xtr->ewayTrxnOption2;
    //$this->txOption3           = $xtr->ewayTrxnOption3;
    //$this->txAmount            = $xtr->ewayReturnAmount;
    //$this->txAuthCode          = $xtr->ewayAuthCode;
    //$this->txInvoiceReference  = $xtr->ewayTrxnReference;

    $this->txError             = self::GetNodeValue("ewayTrxnError", $Xml);
    $this->txStatus            = self::GetNodeValue("ewayTrxnStatus", $Xml);
    $this->txTransactionNumber = self::GetNodeValue("ewayTrxnNumber", $Xml);
    $this->txOption1           = self::GetNodeValue("ewayTrxnOption1", $Xml);
    $this->txOption2           = self::GetNodeValue("ewayTrxnOption2", $Xml);
    $this->txOption3           = self::GetNodeValue("ewayTrxnOption3", $Xml);
    $amount                    = self::GetNodeValue("ewayReturnAmount", $Xml);
    $this->txAuthCode          = self::GetNodeValue("ewayAuthCode", $Xml);
    $this->txInvoiceReference  = self::GetNodeValue("ewayTrxnReference", $Xml);
    $this->txBeagleScore       = self::GetNodeValue("ewayBeagleScore", $Xml);
    $this->txAmount = (int) $amount;
  }

  /**
   * Simple function to use in place of the 'simplexml_load_string' call.
   *
   * It returns the NodeValue for a given NodeName
   * or returns and empty string.
   */
  public function GetNodeValue($NodeName, &$strXML) {
    $OpeningNodeName = "<" . $NodeName . ">";
    $ClosingNodeName = "</" . $NodeName . ">";

    $pos1 = stripos($strXML, $OpeningNodeName);
    $pos2 = stripos($strXML, $ClosingNodeName);

    if (($pos1 === FALSE) || ($pos2 === FALSE)) {
      return "";
    }

    $pos1 += strlen($OpeningNodeName);
    $len   = $pos2 - $pos1;

    $return = substr($strXML, $pos1, $len);

    return ($return);
  }

  public function TransactionNumber() {
    return $this->txTransactionNumber;
  }

  public function InvoiceReference() {
    return $this->txInvoiceReference;
  }

  public function Option1() {
    return $this->txOption1;
  }

  public function Option2() {
    return $this->txOption2;
  }

  public function Option3() {
    return $this->txOption3;
  }

  public function AuthorisationCode() {
    return $this->txAuthCode;
  }

  public function Error() {
    return $this->txError;
  }

  public function Amount() {
    return $this->txAmount;
  }

  public function Status() {
    return $this->txStatus;
  }

  public function BeagleScore () {
    return $this->txBeagleScore;
  }

}
