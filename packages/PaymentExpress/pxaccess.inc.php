<?php

#******************************************************************************
#* Name          : PxAccess.inc
#* Description   : The objects for PX Payment page  
#* Copyright (c) : 2004 Direct Payment solutions
#* Date          : 2003-12-24
#* Modifications : 2003-12-24 MifMessage class
#*				 : 2004-09-01 PxAccess, PxPayRequest, PxPayResponse classes
#*							  which encapsulate 3-DES to handle payment requests and
#*							  response.
#*				   2004-10-14 Implements complete transactions
#*				   2005-03-14 change unpack("H*", $enc); to unpack("H$enclen", $enc); 
#*							  due to the version 4.3.10 Php unpack function bugs
#*Version		 : 2.01.08
#******************************************************************************

# MifMessage.
# Use this class to parse a DPS PX MifMessage in XML form,
# and access the content.
class MifMessage
{
  var $xml_;
  var $xml_index_;
  var $xml_value_;

  # Constructor:
  # Create a MifMessage with the specified XML text.
  # The constructor returns a null object if there is a parsing error.
  function MifMessage($xml)
  {
    $p = xml_parser_create();
    xml_parser_set_option($p,XML_OPTION_CASE_FOLDING,0);
    $ok = xml_parse_into_struct($p, $xml, $value, $index);
    xml_parser_free($p);
    if ($ok)
    {
      $this->xml_ = $xml;
      $this->xml_value_ = $value;
      $this->xml_index_ = $index;
    }
    #print_r($this->xml_value_); # JH_DEBUG
  }

  # Return the value of the specified top-level attribute.
  # This method can only return attributes of the root element.
  # If the attribute is not found, return "".
  function get_attribute($attribute)
  {
    #$attribute = strtoupper($attribute);
    $attributes = $this->xml_value_[0]["attributes"];
    return $attributes[$attribute];
  }

  # Return the text of the specified element.
  # The element is given as a simplified XPath-like name.
  # For example, "Link/ServerOk" refers to the ServerOk element
  # nested in the Link element (nested in the root element).
  # If the element is not found, return "".
  function get_element_text($element)
  {
    #print_r($this->xml_value_); # JH_DEBUG
    $index = $this->get_element_index($element, 0);
    if ($index == 0)
    {
      return "";
    }
    else
    {
	## TW2004-09-24: Fixed bug when elemnt existent but empty
    #
    $elementObj = $this->xml_value_[$index];
    if (! array_key_exists("value", $elementObj))
      return "";
   
    return $this->xml_value_[$index]["value"];
    }
  }

  # (internal method)
  # Return the index of the specified element,
  # relative to some given root element index.
  #
  function get_element_index($element, $rootindex = 0)
  {
    #$element = strtoupper($element);
    $pos = strpos($element, "/");
    if ($pos !== false)
    {
      # element contains '/': find first part
      $start_path = substr($element,0,$pos);
      $remain_path = substr($element,$pos+1);
      $index = $this->get_element_index($start_path, $rootindex);
      if ($index == 0)
      {
        # couldn't find first part; give up.
        return 0;
      }
      # recursively find rest
      return $this->get_element_index($remain_path, $index);
    }
    else
    {
      # search from the parent across all its children
      # i.e. until we get the parent's close tag.
      $level = $this->xml_value_[$rootindex]["level"];
      if ($this->xml_value_[$rootindex]["type"] == "complete")
      {
        return 0;   # no children
      }
      $index = $rootindex+1;
      while ($index<count($this->xml_value_) && 
             !($this->xml_value_[$index]["level"]==$level && 
               $this->xml_value_[$index]["type"]=="close"))
      {
        # if one below parent and tag matches, bingo
        if ($this->xml_value_[$index]["level"] == $level+1 &&
#            $this->xml_value_[$index]["type"] == "complete" &&
            $this->xml_value_[$index]["tag"] == $element)
        {
          return $index;
        }
        $index++;
      }
      return 0;
    }
  }
}

class PxAccess
{
	var $Mac_Key, $Des_Key;
	var $PxAccess_Url;
	var $PxAccess_Userid;
	function PxAccess($Url, $UserId, $Des_Key, $Mac_Key){
		error_reporting(E_ERROR);
		$this->Mac_Key = pack("H*",$Mac_Key);
		$this->Des_Key = pack("H*", $Des_Key);
		$this->PxAccess_Url = $Url;
		$this->PxAccess_Userid = $UserId;
	}
	function makeRequest($request)
	{
		#Validate the REquest
		if($request->validData() == false) return "" ;
			
  		#$txnId=rand(1,100000);
		$txnId = uniqid("MI");  #You need to generate you own unqiue reference. JZ:2004-08-12
		$request->setTxnId($txnId);
		$request->setTs($this->getCurrentTS());
		$request->setSwVersion("2.01.01");
		$request->setAppletType("PHPPxAccess");
		
		
		$xml = $request->toXml();
		
	  if (strlen($xml)%8 != 0)
	  {
	    $xml = str_pad($xml, strlen($xml) + 8-strlen($xml)%8); # pad to multiple of 8
	  }
	  #add MAC code JZ2004-8-16
	  $mac = $this->makeMAC($xml,$this->Mac_Key );
	  $msg = $xml.$mac;
	  #$msg = $xml;
	  $enc = $this->encrypt_tripledes($msg, $this->Des_Key); #JZ2004-08-16: Include the MAC code
	  
	  $enclen = strlen($enc) * 2;
	  
	  $enc_hex = unpack("H$enclen", $enc); #JZ2005-03-14: there is a bug in the new version php unpack function 
	  #$enc_hex = @unpack("H*", $enc); #JZ2005-03-14: there is a bug in the new version php unpack function 
	  
	  #$enc_hex = $enc_hex[""]; #use this function if PHP version before 4.3.4
	   #$enc_hex = $enc_hex[1]; #use this function if PHP version after 4.3.4
	  $enc_hex = (version_compare(PHP_VERSION, "4.3.4", ">=")) ? $enc_hex[1] :$enc_hex[""];
	 
	  $PxAccess_Redirect = "$this->PxAccess_Url?userid=$this->PxAccess_Userid&request=$enc_hex";
  
		return $PxAccess_Redirect;
		
	}
		
	#******************************************************************************
	# This function ecrypts data using 3DES via libmcrypt
	#******************************************************************************
	function encrypt_tripledes($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	# $result = mcrypt_ecb(MCRYPT_DES, $key, $data, MCRYPT_ENCRYPT);
	# return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:
	  $td = mcrypt_module_open('tripledes', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mcrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  return $result;
	}
	
	
	#******************************************************************************
	# This function decrypts data using 3DES via libmcrypt
	#******************************************************************************
	function decrypt_tripledes($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	# $result = mcrypt_ecb(MCRYPT_DES, $key, $data, MCRYPT_DECRYPT);
	# return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:
	  $td = mcrypt_module_open('tripledes', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mdecrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  return $result;
	}
	
	#JZ2004-08-16
	
	#******************************************************************************
	# Generate and return a message authentication code (MAC) for a string.
	# (Uses ANSI X9.9 procedure.)
	#******************************************************************************
	function makeMAC($msg,$Mackey){
		
	 if (strlen($msg)%8 != 0)
	  {
	  	$extra = 8 - strlen($msg)%8;
	    $msg .= str_repeat(" ", $extra); # pad to multiple of 8
	  }
	  $mac = pack("C*", 0, 0, 0, 0, 0, 0, 0, 0); # start with all zeros
	  #$mac_result = unpack("C*", $mac);
	  
	   for ( $i=0; $i<strlen($msg)/8; $i++)
	  {
	    $msg8 = substr($msg, 8*$i, 8);
	    
		$mac ^= $msg8;
	    $mac = $this->encrypt_des($mac,$Mackey);
	    
	  }
		#$mac = pack("C*", $mac);
	    #$mac_result= encrypt_des($mac, $Mackey);
		
		$mac_result	= unpack("H8", $mac);
		#$mac_result	= $mac_result[""]; #use this function if PHP version before 4.3.4
		#$mac_result	= $mac_result[1]; #use this function if PHP version after 4.3.4
		$mac_result = (version_compare(PHP_VERSION, "4.3.4", ">=")) ? $mac_result[1]: $mac_result[""];

		return $mac_result;
	  
	   
	}
	 
	#******************************************************************************
	# This function ecrypts data using DES via libmcrypt
	# JZ2004-08-16
	#******************************************************************************
	function encrypt_des($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	#  $result = mcrypt_ecb(MCRYPT_3DES, $key, $data, MCRYPT_ENCRYPT);
	#  return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:
	  $td = mcrypt_module_open('des', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mcrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  mcrypt_module_close($td);
	
	  return $result;
	}


	#JZ2004-08-16
	function getResponse($resp_enc){
		#global $Mac_Key;
		$enc = pack("H*", $resp_enc);
		$resp = trim($this->decrypt_tripledes($enc, $this->Des_Key));
		$xml = substr($resp, 0, strlen($resp)-8);
	    $mac = substr($resp, -8);
		$checkmac = $this->makeMac($xml, $this->Mac_Key);
		if($mac != $checkmac){
			$xml = "<success>0</success><ResponseText>Response MAC Invalid</ResponseText>";
		}
		
		$pxresp = new PxPayResponse($xml);
		return $pxresp;
	
	}

	
	
	#******************************************************************************
	# Return the current time (GMT/UTC).The return time formatted YYYYMMDDHHMMSS.
	#JZ2004-08-30
	#******************************************************************************
	function getCurrentTS()
	{
	  
	  return gmstrftime("%Y%m%d%H%M%S", time());
	}
	
	

}

#******************************************************************************
# Class for PxPay request messages.
#******************************************************************************
class PxPayRequest extends PxPayMessage
{
	var $TxnId,$UrlFail,$UrlSuccess;
	var $AmountInput, $AppletVersion, $InputCurrency;
	var $EnableAddBillCard;
	var $TS;
	
	var $AppletType;
	
	#Constructor
 	function PxPayRequest(){
		$this->PxPayMessage();
		
	}
	
	function setAppletType($AppletType){
		$this->AppletType = $AppletType;
	}
	
	function getAppletType(){
		return $this->AppletType;
	}
	
	
	
	function setTs($Ts){
		$this->TS = $Ts;
	}
	function setEnableAddBillCard($EnableBillAddCard){
	 $this->EnableAddBillCard = $EnableBillAddCard;
	}
	
	function getEnableAddBillCard(){
		return $this->EnableAddBillCard;
	}
	function setInputCurrency($InputCurrency){
		$this->InputCurrency = $InputCurrency;
	}
	function getInputCurrency(){
		return $this->InputCurrency;
	}
	function setTxnId( $TxnId)
	{
		$this->TxnId = $TxnId;
	}
	function getTxnId(){
		return $this->TxnId;
	}
	
	function setUrlFail($UrlFail){
		$this->UrlFail = $UrlFail;
	}
	function getUrlFail(){
		return $this->UrlFail;
	}
	function setUrlSuccess($UrlSuccess){
		$this->UrlSuccess = $UrlSuccess;
	}
	function setAmountInput($AmountInput){
		$this->AmountInput = sprintf("%9.2f",$AmountInput); 
	}
	
	function getAmountInput(){
		return $this->AmountInput;
	}
	function setSwVersion($SwVersion){
		$this->AppletVersion = $SwVersion;
	}
	
	function getSwVersion(){
		return $this->AppletVersion;
	}
	#******************************************************************
	#Data validation 
	#******************************************************************
	function validData(){
		$msg = "";
		if($this->TxnType != "Purchase")
			if($this->TxnType != "Auth")
				if($this->TxnType != "GetCurrRate")
					if($this->TxnType != "Refund")
						if($this->TxnType != "Complete")
							if($this->TxnType != "Order1")
								$msg = "Invalid TxnType[$this->TxnType]<br>";
		
		if(strlen($this->MerchantReference) > 64)
			$msg = "Invalid MerchantReference [$this->MerchantReference]<br>";
		
		if(strlen($this->TxnId) > 16)
			$msg = "Invalid TxnId [$this->TxnId]<br>";
		if(strlen($this->TxnData1) > 255)
			$msg = "Invalid TxnData1 [$this->TxnData1]<br>";
		if(strlen($this->TxnData2) > 255)
			$msg = "Invalid TxnData2 [$this->TxnData2]<br>";
		if(strlen($this->TxnData3) > 255)
			$msg = "Invalid TxnData3 [$this->TxnData3]<br>";
			
		if(strlen($this->EmailAddress) > 255)
			$msg = "Invalid EmailAddress [$this->EmailAddress]<br>";
			
		if(strlen($this->UrlFail) > 255)
			$msg = "Invalid UrlFail [$this->UrlFail]<br>";
		if(strlen($this->UrlSuccess) > 255)
			$msg = "Invalid UrlSuccess [$this->UrlSuccess]<br>";
		if(strlen($this->BillingId) > 32)
			$msg = "Invalid BillingId [$this->BillingId]<br>";
		if(strlen($this->DpsBillingId) > 16)
			$msg = "Invalid DpsBillingId [$this->DpsBillingId]<br>";
			
		if ($msg != "") {
		    trigger_error($msg,E_USER_ERROR);
			return false;
		}
		return true;
	}

}

#******************************************************************************
# Abstract base class for PxPay messages.
# These are messages with certain defined elements,  which can be serialized to XML.

#******************************************************************************
class PxPayMessage {
	var $TxnType;
  	var $TxnData1;
  	var $TxnData2;
  	var $TxnData3;
  	var $MerchantReference;
  	var $EmailAddress;
  	var $BillingId;
  	var $DpsBillingId;
	var $DpsTxnRef;
	
	function PxPayMessage(){
	
	}
	function setDpsTxnRef($DpsTxnRef){
		$this->DpsTxnRef = $DpsTxnRef;
	}
	
	function getDpsTxnRef(){
		return $this->DpsTxnRef;
	}
	
	function setDpsBillingId($DpsBillingId){
		$this->DpsBillingId = $DpsBillingId;
	}
	
	function getDpsBillingId(){
		return $this->DpsBillingId;
	}
	function setBillingId($BillingId){
		$this->BillingId = $BillingId;
	}
	
	function getBillingId(){
		return $this->BillingId;
	}
	function setTxnType($TxnType){
		$this->TxnType = $TxnType;
	}
	function getTxnType(){
		return $this->TxnType;
	}
	function setMerchantReference($MerchantReference){
		$this->MerchantReference = $MerchantReference;
	}
	
	function getMerchantReference(){
		return $this->MerchantReference;
	}
	function setEmailAddress($EmailAddress){
		$this->EmailAddress = $EmailAddress;
		
	}
	
	function getEmailAddress(){
		return $this->EmailAddress;
	}
	
	function setTxnData1($TxnData1){
		$this->TxnData1 = $TxnData1;
		
	}
	function getTxnData1(){
		return $this->TxnData1;
	}
	function setTxnData2($TxnData2){
		$this->TxnData2 = $TxnData2;
		
	}
	function getTxnData2(){
		return $this->TxnData2;
	}
	
	function getTxnData3(){
		return $this->TxnData3;
	}
	function setTxnData3($TxnData3){
		$this->TxnData3 = $TxnData3;
		
	}
	function toXml(){
		$arr = get_object_vars($this);
		$root = get_class($this);
		if($root == "PxPayRequest")
			$root = "Request";
		elseif ($root == "PxPayResponse")
			$root = "Response";
		else
			$root ="Request";
			
		$xml  = "<$root>";
    	while (list($prop, $val) = each($arr))
        	$xml .= "<$prop>$val</$prop>" ;

		$xml .= "</$root>";
		return $xml;
	}
	
	
}

#******************************************************************************
# Class for PxPay response messages.
#******************************************************************************

class PxPayResponse extends PxPayMessage
{
	var $Success;
  var $StatusRequired;
  var $Retry;
  var $AuthCode;
  var $AmountSettlement;
  var $CurrencySettlement;
  var $CardName;
  var $CurrencyInput;
  var $UserId;
  var $ResponseText;
  #var $DpsTxnRef;
  var $MerchantTxnId;
  var $TS;
  
	function PxPayResponse($xml){
		$msg = new MifMessage($xml);
		$this->PxPayMessage();
		
		$TS = $msg->get_element_text("TS");
		$expiryTS = $this->getExpiredTS();
		if(strcmp($TS, $expiryTS) < 0 ){
			$this->Success = "0";
			$this->ResponseText = "Response TS out of range";
			return;
		}
		
		$this->setBillingId($msg->get_element_text("BillingId"));
		$this->setDpsBillingId($msg->get_element_text("DpsBillingId"));
		$this->setEmailAddress($msg->get_element_text("EmailAddress"));
		$this->setMerchantReference($msg->get_element_text("MerchantReference"));
		$this->setTxnData1($msg->get_element_text("TxnData1"));
		$this->setTxnData2($msg->get_element_text("TxnData2"));
		$this->setTxnData3($msg->get_element_text("TxnData3"));
		$this->setTxnType($msg->get_element_text("TxnType"));
		
		$this->Success = $msg->get_element_text("Success");
		$this->StatusRequired = $msg->get_element_text("StatusRequired");
		$this->Retry = $msg->get_element_text("Retry");
		$this->AuthCode = $msg->get_element_text("AuthCode");
		$this->AmountSettlement = $msg->get_element_text("AmountSettlement");
		$this->CurrencySettlement = $msg->get_element_text("CurrencySettlement");
		$this->CardName = $msg->get_element_text("CardName");
		$this->CurrencyInput = $msg->get_element_text("CurrencyInput");
		$this->UserId = $msg->get_element_text("UserId");
		$this->ResponseText = $msg->get_element_text("ResponseText");
		$this->DpsTxnRef = $msg->get_element_text("DpsTxnRef");
		$this->MerchantTxnId = $msg->get_element_text("MerchantTxnId");
		$this->TS = $msg->get_element_text("TS");
	}
	function getTS(){
		return $this->TS;
	}
	function getMerchantTxnId(){
		return $this->MerchantTxnId;
	}
	
	function getResponseText(){
		return $this->ResponseText;
	}
	function getUserId(){
		return $this->UserId;
	}
	function getCurrencyInput(){
		return $this->CurrencyInput;
	}
	function getCardName(){
		return $this->CardName;
	}
	function getCurrencySettlement(){
		$this->CurrencySettlement;
	}
	function getAmountSettlement(){
		return $this->AmountSettlement;
	}
	function getSuccess(){
		return $this->Success;
	}
	function getStatusRequired(){
		return $this->StatusRequired;
	}
	function getRetry(){
		return $this->Retry;
	}
	function getAuthCode(){
		return $this->AuthCode;
	}
	#******************************************************************************
	# Return the expired time, i.e. 2 days ago (GMT/UTC).
	#JZ2004-08-30
	#******************************************************************************
	function  getExpiredTS()
	{
	  
	  return gmstrftime("%Y%m%d%H%M%S", time()- 2 * 24 * 60 * 60);
	}
	
}

?>