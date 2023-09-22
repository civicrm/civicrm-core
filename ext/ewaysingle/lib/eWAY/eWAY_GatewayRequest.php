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
 * 'eWAY_GatewayRequest.php' - Based on the standard supplied eWay sample code 'GatewayResponse.php'
 *
 * The only significant change from the original is that the 'CVN' field is uncommented,
 * unlike the distributed sample code.
 *
 * ALSO: Added a 'GetTransactionNumber' function.
 *
 */
use CRM_Ewaysingle_ExtensionUtil as E;

class GatewayRequest {
  public $txCustomerID = "";

  public $txAmount = 0;

  public $txCardholderName = "";

  public $txCardNumber = "";

  public $txCardExpiryMonth = "01";

  public $txCardExpiryYear = "00";

  public $txTransactionNumber = "";

  public $txCardholderFirstName = "";

  public $txCardholderLastName = "";

  public $txCardholderEmailAddress = "";

  public $txCardholderAddress = "";

  public $txCardholderPostalCode = "";

  public $txInvoiceReference = "";

  public $txInvoiceDescription = "";

  public $txCVN = "";

  public $txOption1 = "";

  public $txOption2 = "";

  public $txOption3 = "";

  public $txCustomerBillingCountry = "";

  public $txCustomerIPAddress = "";

  public function __construct() {
    // Empty Constructor
  }

  public function GetTransactionNumber() {
    return $this->txTransactionNumber;
  }

  public function EwayCustomerID($value) {
    $this->txCustomerID = $value;
  }

  public function InvoiceAmount($value) {
    $this->txAmount = $value;
  }

  public function CardHolderName($value) {
    $this->txCardholderName = $value;
  }

  public function CardExpiryMonth($value) {
    $this->txCardExpiryMonth = $value;
  }

  public function CardExpiryYear($value) {
    $this->txCardExpiryYear = $value;
  }

  public function TransactionNumber($value) {
    $this->txTransactionNumber = $value;
  }

  public function PurchaserFirstName($value) {
    $this->txCardholderFirstName = $value;
  }

  public function PurchaserLastName($value) {
    $this->txCardholderLastName = $value;
  }

  public function CardNumber($value) {
    $this->txCardNumber = $value;
  }

  public function PurchaserAddress($value) {
    $this->txCardholderAddress = $value;
  }

  public function PurchaserPostalCode($value) {
    $this->txCardholderPostalCode = $value;
  }

  public function PurchaserEmailAddress($value) {
    $this->txCardholderEmailAddress = $value;
  }

  public function InvoiceReference($value) {
    $this->txInvoiceReference = $value;
  }

  public function InvoiceDescription($value) {
    $this->txInvoiceDescription = $value;
  }

  public function CVN($value) {
    $this->txCVN = $value;
  }

  public function EwayOption1($value) {
    $this->txOption1 = $value;
  }

  public function EwayOption2($value) {
    $this->txOption2 = $value;
  }

  public function EwayOption3($value) {
    $this->txOption3 = $value;
  }

  public function CustomerBillingCountry($value) {
    $this->txCustomerBillingCountry = $value;
  }

  public function CustomerIPAddress($value) {
    $this->txCustomerIPAddress = $value;
  }

  public function ToXml() {
    // We don't really need the overhead of creating an XML DOM object
    // to really just concatenate a string together.

    $xml = "<ewaygateway>";
    $xml .= $this->CreateNode("ewayCustomerID", $this->txCustomerID);
    $xml .= $this->CreateNode("ewayTotalAmount", $this->txAmount);
    $xml .= $this->CreateNode("ewayCardHoldersName", $this->txCardholderName);
    $xml .= $this->CreateNode("ewayCardNumber", $this->txCardNumber);
    $xml .= $this->CreateNode("ewayCardExpiryMonth", $this->txCardExpiryMonth);
    $xml .= $this->CreateNode("ewayCardExpiryYear", $this->txCardExpiryYear);
    $xml .= $this->CreateNode("ewayTrxnNumber", $this->txTransactionNumber);
    $xml .= $this->CreateNode("ewayCustomerInvoiceDescription", $this->txInvoiceDescription);
    $xml .= $this->CreateNode("ewayCustomerFirstName", $this->txCardholderFirstName);
    $xml .= $this->CreateNode("ewayCustomerLastName", $this->txCardholderLastName);
    $xml .= $this->CreateNode("ewayCustomerEmail", $this->txCardholderEmailAddress);
    $xml .= $this->CreateNode("ewayCustomerAddress", $this->txCardholderAddress);
    $xml .= $this->CreateNode("ewayCustomerPostcode", $this->txCardholderPostalCode);
    $xml .= $this->CreateNode("ewayCustomerInvoiceRef", $this->txInvoiceReference);
    $xml .= $this->CreateNode("ewayCVN", $this->txCVN);
    $xml .= $this->CreateNode("ewayOption1", $this->txOption1);
    $xml .= $this->CreateNode("ewayOption2", $this->txOption2);
    $xml .= $this->CreateNode("ewayOption3", $this->txOption3);
    $xml .= $this->CreateNode("ewayCustomerIPAddress", $this->txCustomerIPAddress);
    $xml .= $this->CreateNode("ewayCustomerBillingCountry", $this->txCustomerBillingCountry);
    $xml .= "</ewaygateway>";

    return $xml;
  }

  /**
   * Builds a simple XML Node
   *
   * 'NodeName' is the anem of the node being created.
   * 'NodeValue' is its value
   *
   */
  public function CreateNode($NodeName, $NodeValue) {
    $node = "<" . $NodeName . ">" . self::replaceEntities($NodeValue) . "</" . $NodeName . ">";
    return $node;
  }

  /**
   * replace XML entities
   *
   * <code>
   * // replace XML entites:
   * $string = self::replaceEntities('This string contains < & >.');
   * </code>
   *
   * @param string $string          string where XML special chars
   *                                should be replaced
   *
   * @return string string with replaced chars
   */
  public static function replaceEntities($string) {
    return strtr($string, [
      '&'  => '&amp;',
      '>'  => '&gt;',
      '<'  => '&lt;',
      '"'  => '&quot;',
      '\'' => '&apos;',
    ]);
  }

}
