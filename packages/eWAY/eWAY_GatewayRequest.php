<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

/**************************************************************************************************************************
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
 **************************************************************************************************************************/
 
class GatewayRequest
{
	var $txCustomerID = "";

	var $txAmount = 0;

	var $txCardholderName = "";

	var $txCardNumber = "";

	var $txCardExpiryMonth = "01";

	var $txCardExpiryYear = "00";

	var $txTransactionNumber = "";

	var $txCardholderFirstName = "";

	var $txCardholderLastName = "";

	var $txCardholderEmailAddress = "";

	var $txCardholderAddress = "";

	var $txCardholderPostalCode = "";

	var $txInvoiceReference = "";

	var $txInvoiceDescription = "";

    var $txCVN = "";

	var $txOption1 = "";

	var $txOption2 = "";

	var $txOption3 = "";
    
    var $txCustomerBillingCountry = "";

    var $txCustomerIPAddress = "";

   function GatewayRequest()   
   {
      // Empty Constructor
   }

   function GetTransactionNumber()
   {
      return $this->txTransactionNumber;
   }

   function EwayCustomerID($value) 
   {
      $this->txCustomerID=$value;
   }

   function InvoiceAmount($value)
   {
      $this->txAmount=$value;
   }

   function CardHolderName($value)
   {
      $this->txCardholderName=$value;
   }

   function CardExpiryMonth($value)  
   {
      $this->txCardExpiryMonth=$value;
   }

   function CardExpiryYear($value)
   {
      $this->txCardExpiryYear=$value;
   }

   function TransactionNumber($value)
   {
      $this->txTransactionNumber=$value;
   }

   function PurchaserFirstName($value)
   {
      $this->txCardholderFirstName=$value;
   }

   function PurchaserLastName($value)
   {
      $this->txCardholderLastName=$value;
   }

   function CardNumber($value)
   {
      $this->txCardNumber=$value;
   }

   function PurchaserAddress($value)
   {
      $this->txCardholderAddress=$value;
   }

   function PurchaserPostalCode($value)
   {
      $this->txCardholderPostalCode=$value;
   }

   function PurchaserEmailAddress($value)
   {
      $this->txCardholderEmailAddress=$value;
   }

   function InvoiceReference($value) 
   {
      $this->txInvoiceReference=$value; 
   }

   function InvoiceDescription($value) 
   {
      $this->txInvoiceDescription=$value; 
   }

   function CVN($value) 
   {
      $this->txCVN=$value; 
   }

   function EwayOption1($value) 
   {
      $this->txOption1=$value; 
   }

   function EwayOption2($value) 
   {
      $this->txOption2=$value; 
   }

   function EwayOption3($value) 
   {
      $this->txOption3=$value; 
   }

   function CustomerBillingCountry($value) 
   {
       $this->txCustomerBillingCountry=$value; 
   }

   function CustomerIPAddress($value) 
   {
       $this->txCustomerIPAddress=$value; 
   }

   function ToXml()
   {
      // We don't really need the overhead of creating an XML DOM object
      // to really just concatenate a string together.

      $xml = "<ewaygateway>";
      $xml .= $this->CreateNode("ewayCustomerID",                 $this->txCustomerID);
      $xml .= $this->CreateNode("ewayTotalAmount",                $this->txAmount);
      $xml .= $this->CreateNode("ewayCardHoldersName",            $this->txCardholderName);
      $xml .= $this->CreateNode("ewayCardNumber",                 $this->txCardNumber);
      $xml .= $this->CreateNode("ewayCardExpiryMonth",            $this->txCardExpiryMonth);
      $xml .= $this->CreateNode("ewayCardExpiryYear",             $this->txCardExpiryYear);
      $xml .= $this->CreateNode("ewayTrxnNumber",                 $this->txTransactionNumber);
      $xml .= $this->CreateNode("ewayCustomerInvoiceDescription", $this->txInvoiceDescription);
      $xml .= $this->CreateNode("ewayCustomerFirstName",          $this->txCardholderFirstName);
      $xml .= $this->CreateNode("ewayCustomerLastName",           $this->txCardholderLastName);
      $xml .= $this->CreateNode("ewayCustomerEmail",              $this->txCardholderEmailAddress);
      $xml .= $this->CreateNode("ewayCustomerAddress",            $this->txCardholderAddress);
      $xml .= $this->CreateNode("ewayCustomerPostcode",           $this->txCardholderPostalCode);
      $xml .= $this->CreateNode("ewayCustomerInvoiceRef",         $this->txInvoiceReference);
      $xml .= $this->CreateNode("ewayCVN",                        $this->txCVN);
      $xml .= $this->CreateNode("ewayOption1",                    $this->txOption1);
      $xml .= $this->CreateNode("ewayOption2",                    $this->txOption2);
      $xml .= $this->CreateNode("ewayOption3",                    $this->txOption3);
      $xml .= $this->CreateNode("ewayCustomerIPAddress",          $this->txCustomerIPAddress);
      $xml .= $this->CreateNode("ewayCustomerBillingCountry",     $this->txCustomerBillingCountry);
      $xml .= "</ewaygateway>";
      
      return $xml;
   }
   
   
   /********************************************************
   * Builds a simple XML Node
   *
   * 'NodeName' is the anem of the node being created.
   * 'NodeValue' is its value
   *
   ********************************************************/
   function CreateNode($NodeName, $NodeValue)
   {
    require_once 'XML/Util.php';

    $xml = new XML_Util();
    $node = "<" . $NodeName . ">" . $xml->replaceEntities($NodeValue) . "</" . $NodeName . ">";
    return $node;
   }
   
} // class GatewayRequest

?>
