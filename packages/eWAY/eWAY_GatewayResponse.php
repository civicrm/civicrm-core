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
 **************************************************************************************************************************/
 
class GatewayResponse
{
	var $txAmount              = 0;
	var $txTransactionNumber   = "";
	var $txInvoiceReference    = "";
	var $txOption1             = "";
	var $txOption2             = "";
	var $txOption3             = "";
	var $txStatus              = "";
	var $txAuthCode            = "";
	var $txError               = "";
    var $txBeagleScore         = "";

	function __construct()
	{
	   // Empty Constructor
    }
   
	function ProcessResponse($Xml)
	{
#####################################################################################
#                                                                                   #
#      $xtr = simplexml_load_string($Xml) or die ("Unable to load XML string!");    #
#                                                                                   #
#      $this->txError             = $xtr->ewayTrxnError;                            #
#      $this->txStatus            = $xtr->ewayTrxnStatus;                           #
#      $this->txTransactionNumber = $xtr->ewayTrxnNumber;                           #
#      $this->txOption1           = $xtr->ewayTrxnOption1;                          #
#      $this->txOption2           = $xtr->ewayTrxnOption2;                          #
#      $this->txOption3           = $xtr->ewayTrxnOption3;                          #
#      $this->txAmount            = $xtr->ewayReturnAmount;                         #
#      $this->txAuthCode          = $xtr->ewayAuthCode;                             #
#      $this->txInvoiceReference  = $xtr->ewayTrxnReference;                        #
#                                                                                   #
#####################################################################################

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
   
   
   /************************************************************************
   * Simple function to use in place of the 'simplexml_load_string' call.
   * 
   * It returns the NodeValue for a given NodeName
   * or returns and empty string.
   ************************************************************************/
   function GetNodeValue($NodeName, &$strXML)
   {
      $OpeningNodeName = "<" . $NodeName . ">";
      $ClosingNodeName = "</" . $NodeName . ">";
      
      $pos1 = stripos($strXML, $OpeningNodeName);
      $pos2 = stripos($strXML, $ClosingNodeName);
      
      if ( ($pos1 === false) || ($pos2 === false) )
         return "";
         
      $pos1 += strlen($OpeningNodeName);
      $len   = $pos2 - $pos1;

      $return = substr($strXML, $pos1, $len);                                       
      
      return ($return);
   }
   

   function TransactionNumber()
   {
      return $this->txTransactionNumber; 
   }

   function InvoiceReference() 
   {
      return $this->txInvoiceReference; 
   }

   function Option1() 
   {
      return $this->txOption1; 
   }

   function Option2() 
   {
      return $this->txOption2; 
   }

   function Option3() 
   {
      return $this->txOption3; 
   }

   function AuthorisationCode()
   {
      return $this->txAuthCode; 
   }

   function Error()
   {
      return $this->txError; 
   }   

   function Amount() 
   {
      return $this->txAmount; 
   }   

   function Status()
   {
      return $this->txStatus;
   }

   function BeagleScore ()
   {
       return $this->txBeagleScore ;
   }
}

?>
