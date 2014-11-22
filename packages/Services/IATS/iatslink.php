<?php
/************************************************************************ 
*                                                                       * 
*  FILE NAME: iatslink.php                                              *
*  Copyright (C) 2005 Ticketmaster Canada                               *
*  Requirements:  PHP5, cURL, SSL, creditcard.php                       * 
*  It only requires that cURL is installed                              * 
*                                                                       * 
*  V1.32: Aug. 18, 2008, Haibin                                       *
*                                                                       * 
************************************************************************/ 
/*
 * Code provided by Ticketmaster/IATS in their php API 
 * Used by IATS Payment processor code
 *
 * Requires 'creditcard' code used for validating cc numbers
 *
 */
 include_once( "creditcard.php" );
 
 class iatslink
 {
   // Constants.
   private $version;     //String 
   // Inputs common to both US and Canadian credit card processing.
   private $agentCode;
   private $password;
   private $cardType;
   private $cardNumber;
   private $cardExpiry;
   private $dollarAmount;  //double   
   private $webServer;
  
   // Optional inputs.
   private $preapprovalCode;
   private $invoiceNumber;
   private $comment;
   private $CVV2;
   private $issueNumber;
   private $testMode;    //boolean 
   private $proxyHost;     
   private $proxyPort;    //int
   private $proxyUsername;
   private $proxyPassword;
   // Inputs specific to US credit card processing.
   private $firstName;
   private $lastName;
   private $streetAddress;
   private $city;
   private $state;
   private $zipCode;
   // Outputs.
   private $status;    //int
   private $authorizationResult;
   private $error;
   // Constructor.  
   function iatslink()
   { 
      $this->version ="1.32";    
      // Proxy Setting  
      $this->proxyHost   = "";
      $this->proxyPort   = -1;
      $this->proxyUsername = "";
      $this->proxyPassword = "";  
      // Initialize inputs.
      
      $this->agentCode = "";
      $this->password = "";
      $this->cardType = "";
      $this->cardNumber = "";
      $this->cardExpiry = "";
      $this->dollarAmount = 0.00;
      $this->webServer = "www.iats.ticketmaster.com";
      $this->preapprovalCode = "";
      $this->invoiceNumber = "";
      $this->comment = "";
      $this->CVV2 = "";
      $this->issueNumber = "";
      
      $this->testMode = false;
      $this->firstName = "";
      $this->lastName = "";
      $this->streetAddress = "";
      $this->city = "";
      $this->state = "";
      $this->zipCode = "";
      // Initialize outputs.
      $this->status = 0;
      $this->authorizationResult = "REJECT: 1";
      
      $this->error = "AUTH ERROR!";
  
   }
   
   
   //  Methods for setting Proxy server
   
   
   public function setProxyServer($host,$port)
   {
      $this->proxyHost = $host;
      $this->proxyPort = $port;
   }
   
   public function setProxyUser($usern,$passw)
   {
      $this->proxyUsername = $usern;
      $this->proxyPassword = $passw;
   }
   
   /**
    * Methods for setting inputs common to processing either a US or Canadian credit card.
    */
   public function setAgentCode($newAgentCode)
   {
      $this->agentCode = $newAgentCode;
   }
   public function setPassword($newPassword)
   {
      $this->password = $newPassword;
   }
   public function setCardType($newCardType)
   {
      $this->cardType = $newCardType;
   }
   public function setCardNumber($newCardNumber)
   {
      $this->cardNumber = $newCardNumber;
   }
   public function setCardExpiry($newCardExpiry)
   {
      $this->cardExpiry = $newCardExpiry;
   }
   public function setDollarAmount($newDollarAmount)
   {
      $this->dollarAmount = $newDollarAmount;
   }
   public function setWebServer($newWebServer)
   {
      $this->webServer = $newWebServer;
   }
   public function setPreapprovalCode($newPreapprovalCode)
   {
      $this->preapprovalCode = $newPreapprovalCode;
   }
   public function setInvoiceNumber($newInvoiceNumber)
   {
      $this->invoiceNumber = $newInvoiceNumber;
   }
   public function setComment($newComment)
   {
      $this->comment = $newComment;
   }
   public function setCVV2($newCVV2)
   {
      $this->CVV2 = $newCVV2;
   }
   public function setIssueNumber($newIssueNumber)
   {
    $this->issueNumber = $newIssueNumber;
   }
   public function setTestMode($newTestMode)
   {
      $this->testMode = $newTestMode;
   }
   /**
    * Methods for setting inputs specific to processing a US credit card.
    */
   public function setFirstName($newFirstName)
   {
      $this->firstName = $newFirstName;
   }
   public function setLastName($newLastName)
   {
      $this->lastName = $newLastName;
   }
   public function setStreetAddress($newStreetAddress)
   {
      $this->streetAddress = $newStreetAddress;
   }
   public function setCity($newCity)
   {
      $this->city = $newCity;
   }
   public function setState($newState)
   {
      $this->state = $newState;
   }
   public function setZipCode($newZipCode)
   {
      $this->zipCode = $newZipCode;
   }
   /**
    * Methods for retrieving results of processing a credit card.
    */
   public function getStatus()
   {
      return $this->status;
   }
   public function getAuthorizationResult()
   {
      return $this->authorizationResult;
   }
   public function getError()
   {
      return $this->error;
   }
   /**
   * Methods for processing a credit card.
   */
   
   
   public function processCreditCard()
   {  
     
      //Check credit card number first
      $creditCard1 = new creditCard();
      try
      {
         // Get the creditCardNumber.
        
         if ($creditCard1->isValid($this->cardNumber) == false )
         {
          $this->status = 1;
          $this->authorizationResult = "REJECT: 40";
          $this->error = "INVALID CC NUMBER!";   
          return;
         } 
      } catch (Exception $e ) {
          $this->status = 1;
          $this->authorizationResult = "REJECT: 40";
          $this->error = "INVALID CC NUMBER!";   
          return;
      }
      
      if ($this->cardType != "")
      {
          
      }
      else 
      {
          $this->cardType = $creditCard1->ccType($this->cardNumber);
          if ($this->cardType == "UNKNOWN")
          {
           $this->status = 1;
           $this->authorizationResult = "REJECT: 40";
           $this->error = "UNKNOWN CC TYPE!";   
           return;  
          }
      }
     
         
      
      // Read input variables, send for processing, parse response and store in output variables.
      try
      {
  
         $params = "AgentCode=" . $this->agentCode;   
      $params = $params . "&Password=" . $this->password;  
      $params = $params . "&CCNum="   .  $creditCard1->cleanNum($this->cardNumber);
         $params = $params . "&CCExp="   .  $this->cardExpiry;
         $params = $params . "&MOP="     .  $this->cardType;
         $params = $params . "&Total="   .  $this->dollarAmount;
                      
         if ($this->invoiceNumber != "")
         {
          $params = $params . "&InvoiceNum=" . $this->invoiceNumber;
         }
         if ($this->preapprovalCode !="")
         {
          $params = $params . "&PreapprovalCode=" . $this->preapprovalCode; 
         }
         if ($this->comment !="")
         {
          $params = $params . "&Comment=" . $this->comment;
         }
         if ($this->CVV2 !="")
         {
          $params = $params . "&CVV2=" . $this->CVV2;
         }
         if ($this->issueNumber !="")
         {
          $params = $params . "&IssueNum=" . $this->issueNumber;
         }
         
         $params = $params . "&FirstName=" . $this->firstName;
         $params = $params . "&LastName="  . $this->lastName;
      $params = $params . "&Address="   . $this->streetAddress;
         $params = $params . "&City="      . $this->city;
         $params = $params . "&State="     . $this->state;
         $params = $params . "&ZipCode="   . $this->zipCode; 
 
         $params = $params . "&Version=" . $this->version;
       
         $url = "";
         $postAction = "/trams/authresult.pro";
         $user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
         
         try {           
           if ($this->testMode == true)
           {
            $url = "http://"  . $this->webServer . $postAction;     
           }
           else
           {  
            $url = "https://" . $this->webServer . $postAction;   
           }
      
                                 
           $ch = curl_init();
           curl_setopt($ch, CURLOPT_POST,1);
           curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
           curl_setopt($ch, CURLOPT_URL,$url);
           curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
           curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
           curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // this line makes it work under https
           
           if ( ($this->proxyHost !="") and ($this->proxyPort > 0) )
           {
             //set up proxy
            curl_setopt ($ch, CURLOPT_PROXY, $this->proxyHost); 
            curl_setopt ($ch, CURLOPT_PROXYPORT, $this->proxyPort); 
            curl_setopt ($ch, CURLOPT_PROXYUSERPWD, $this->proxyUsername . "," . $this->proxyPassword); 
            curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL,true); 
           }
           $iatsReturn = curl_exec ($ch);
           $this->error = curl_error($ch);
           $errorNumber = curl_errno($ch);
           //echo  $iatsReturn;
            
           curl_close ($ch);
                      
           if ($errorNumber !=0 ) // Error
           {  
              $this->status = 0;
              $this->error = "Error:" + $this->error;
              $this->authorizationResult = "REJECT: ERRORPOST";
              return;   
           } else {
              
              $this->status = 0;
              $this->authorizationResult = "REJECT: 1";
              $this->error = "AUTH ERROR!";
                            
              $iatsReturn = stristr($iatsReturn,"AUTHORIZATION RESULT:");
              $iatsReturn = substr($iatsReturn, strpos($iatsReturn,":")+1, strpos($iatsReturn,"<")-strpos($iatsReturn,":")-1);
              if ($iatsReturn == "")
              {
               $this->status = 0;
               $this->error = "PAGE ERROR";
               $this->authorizationResult = "REJECT: ERRPAGE";
              } 
              else{
               $this->status = 1;
               $this->error = "";
               $this->authorizationResult = $iatsReturn; 
              }
           }//end else  
           
          }catch (Exception $e) { 
            $this->status = 0;
            $this->error = "Error: ERRORCONN" ;
            $this->authorizationResult = "REJECT: ERRORCONN";
            return;   
          }
          
      } catch (Exception $e)  {
         $this->status = 0;
         $this->error = "12002";
         $this->authorizationResult = "REJECT: SYSERROR";
         return; 
      }
      
   }
   
   
 }
