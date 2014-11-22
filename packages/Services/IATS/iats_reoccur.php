<?php
/************************************************************************ 
*                                                                       * 
*  FILE NAME: iatslinkReoccur.php                                       *
*  Copyright (C) 2005 Ticketmaster Canada                               *
*  Requirements:  PHP5, cURL, SSL, creditcard.php                       * 
*  It only requires that cURL is installed                              * 
*                                                                       * 
*  V1.32: Aug. 18, 2008, Haibin                                        *
*                                                                       * 
************************************************************************/ 
 include_once( "creditcard.php" );
 
 class iatslinkReoccur
 {
   // Constants.
   private $version;     //String 
   
   // Inputs common to both US and Canadian credit card processing.
   private $agentCode;
   private $password;
   private $webServer;
 
   private $firstName;
   private $lastName;
   private $streetAddress;
   private $city;
   private $state;
   private $zipCode;
   
   private $cardType;
   private $cardNumber;
   private $cardExpiry;
   private $dollarAmount;  //double   
   private $customerCode;
   private $reoccuringStatus;  //ON,OFF
   private $beginDate;       //YYYY-MM-DD
   private $endDate;         //YYYY-MM-DD
   private $scheduleType;    //MONTHLY,WEEKLY
   private $scheduleDate;    //MONTHLY:1-31;WEEKLY:1-7
   
   private $reportResultFileName;
   
   private $serverType; //=1, UK 
   
   // 
   private $cookieFile;
   private $loginOK; 
   // Optional inputs.
   private $invoiceNumber; 
   private $testMode;    //boolean 
   
   private $proxyHost;     
   private $proxyPort;    //int
   private $proxyUsername;
   private $proxyPassword;
   
   // Outputs.
   private $status;    //int
   private $authorizationResult;
   private $error;
   // Constructor.  
   function iatslinkReoccur()
   {
      // Initialize inputs.  
      $this->version ="1.32";
      
      $this->agentCode = "";
      $this->password = "";
      
      $this->cardType = "";
      $this->cardNumber = "";
      $this->cardExpiry = "";
      $this->dollarAmount = 0.00;
      
      $this->webServer = "www.iats.ticketmaster.com";
      $this->serverType =0;
      
      $this->customerCode = "";
      $this->reoccuringStatus = "OFF";
      $this->beginDate = "";     
      $this->endDate = "";       
      $this->scheduleType ="MONTHLY";  
      $this->scheduleDate ="1";  
      
      $this->firstName = "";
      $this->lastName = "";
      $this->streetAddress = "";
      $this->city = "";
      $this->state = "";
      $this->zipCode = "";
      
      $this->invoiceNumber = "";
      $this->testMode = false;
      $this->reportResultFileName = "";
      // Proxy Setting  
      $this->proxyHost   = "";
      $this->proxyPort   = -1;
      $this->proxyUsername = "";
      $this->proxyPassword = "";
      
      // Initialize outputs.
      $this->status = 0;
      $this->authorizationResult = "FAILURE: SYSERROR";     
      $this->error = "ERROR";
      
      //
      $loginOK = false;
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
  
   public function setServerType($newServerType)
   {
      $this->serverType = $newServerType;
   }
   public function setInvoiceNumber($newInvoiceNumber)
   {
      $this->invoiceNumber = $newInvoiceNumber;
   }
   public function setTestMode($newTestMode)
   {
      $this->testMode = $newTestMode;
   }
   
   public function setReportResultFileName($newFileName)
   {
    $this->reportResultFileName = $newFileName;
   }
   
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
   public function setCustomerCode($newCode)
   {
     $this->customerCode =$newCode;  
   }
   
   public function setReoccuringStatus($newStatus)
   {
     $this->reoccuringStatus =$newStatus;
   }  
   
   public function setBeginDate($newDate)
   {
     $this->beginDate = $newDate;
   }  
   public function setEndDate($newDate)
   {
     $this->endDate = $newDate;
   }  
   public function setScheduleType($newType)
   {
     $this->scheduleType =$newType;
   }  
   public function setScheduleDate($newDate)
   {
     $this->scheduleDate = $newDate;
   }  
   
   /**
    * Methods for retrieving results of processing.
    */
   public function getCustomerCode()
   {
     return $this->customerCode;
   } 
   
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
   * Methods for processing reoccurring.
   */
   
   
   public function doLogin()
   {
    $this->cookieFile = "cookie" .date("his"). ".txt";
    $this->loginOK = false;
    if ($this->serverType==1)
      { $LoginAction = "/itravel/itravel.pro";
        if ($this->testMode == true)
        {
              $url = "http://"  . $this->webServer . $LoginAction;     
        }
        else
        {  
              $url = "https://" . $this->webServer . $LoginAction;   
        }
  
        $params2 = "UserName=" . $this->agentCode;   
     $params2 = $params2 . "&Password=" . $this->password;  
     $params2 = $params2 . "&Version=" . $this->version;
     
     $user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"; 
     $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$params2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   // this line makes it work under https
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        $iatsReturn = curl_exec ($ch);
        $this->error = curl_error($ch);
        $errorNumber = curl_errno($ch);
        curl_close($ch);        
        // echo  $iatsReturn;
        if ( (strpos($iatsReturn,"\"CCName\"")==false) || (strpos($iatsReturn,"\"CCNum\"")==false))
        {
          $this->status = 0;
          $this->error = "Error 1";
          $this->authorizationResult = "FAILED: INVALID AGENT CODE/PASSWORD";
          @unlink( $this->cookieFile);
            
          return false;     
        } 
        $this->loginOK = true;
        return true;
      }
  
   }
  
   
   public function createReoccCustomer()
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
     
      if ($this->serverType ==1) 
      {
        $this->doLogin();               
        if ($this->loginOK == false)
        {
          $this->status = 0;
          $this->error = "ERROR 1";
          $this->authorizationResult = "FAILED: INVALID AGENT CODE/PASSWORD";  
          return;  
        }   
      } 
      
      // Read input variables, send for processing, parse response and store in output variables.
      try
      {
  
         $params = "AgentCode=" . $this->agentCode;   
      $params = $params . "&Password=" . $this->password;  
        
         $params = $params . "&FirstName=" . $this->firstName;
         $params = $params . "&LastName="  . $this->lastName;
      $params = $params . "&Address="   . $this->streetAddress;
         $params = $params . "&City="      . $this->city;
         $params = $params . "&State="     . $this->state;
         $params = $params . "&ZipCode="   . $this->zipCode; 
         
         $params = $params . "&CCNum1="       .  $creditCard1->cleanNum($this->cardNumber);
         $params = $params . "&CCEXPIRY1="    .  $this->cardExpiry;
         $params = $params . "&MOP1="         .  $this->cardType;
         $params = $params . "&Amount1="      .  $this->dollarAmount;
         
         $params = $params . "&BeginDate1="   .  $this->beginDate;
         $params = $params . "&EndDate1="     .  $this->endDate;
         $params = $params . "&ScheduleType1="     .  $this->scheduleType;
         $params = $params . "&ScheduleDate1="     .  $this->scheduleDate;
         $params = $params . "&Reoccurring1="      .  $this->reoccuringStatus;
                      
         if ($this->invoiceNumber != "")
         {
          $params = $params . "&InvoiceNum=" . $this->invoiceNumber;
         }
         
         $params = $params . "&Version=" . $this->version;
       
         $url = "";
         $postAction = "/itravel/Customer_Create.pro";
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
           if ($this->serverType==1) {
              curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
           } else {
              curl_setopt($ch, CURLOPT_USERPWD,$this->agentCode . ":" . $this->password); //               
           }
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
            
           curl_close ($ch); @unlink( $this->cookieFile);
                      
           if ($errorNumber !=0 ) // Error
           {  
              $this->status = 0;
              $this->error = "Error:" + $this->error;
              $this->authorizationResult = "FAILURE: SENDERROR";
              return;   
           } else {
              
              $this->status = 0;
              $this->authorizationResult = "FAILURE: UNKNOWN";
              $this->error = "ERROR!";
              
              if (strpos($iatsReturn,"HTTP 401.") >0 ){
                  $this->status = 0;
                 $this->error = "Error 1";
                 $this->authorizationResult = "INVALID AGENT CODE / PASSWORD";
                 //echo $iatsReturn //full error msg
                 return;
               }
               if (strpos($iatsReturn,"Reoccurring1") <=0 ){
                  $this->status = 0;
                 $this->error = "FAILURE";
                 $this->authorizationResult = $iatsReturn; 
                 return;
               }
              $iatsReturn = stristr($iatsReturn,"CustCode ");
              $iatsReturn = stristr($iatsReturn,"value=");
              $ipos2 = strpos($iatsReturn,">");
              $iatsReturn = substr($iatsReturn, 7,$ipos2 - 8);
         
              if ($iatsReturn == "")
              {
               $this->status = 0;
               $this->error = "PAGE ERROR";
               $this->authorizationResult = "FAILURE: ERRPAGE";
              } 
              else{
               $this->status = 1;
               $this->error = "";             
               $this->authorizationResult = $iatsReturn; 
               $this->customerCode = $iatsReturn;
              }
           }//end else  
           
          }catch (Exception $e) { 
            $this->status = 0;
            $this->error = "FAILURE: ERRORCONN" ;
            $this->authorizationResult = "FAILURE: ERRORCONN";
            return;   
          }
          
      } catch (Exception $e)  {
         $this->status = 0;
         $this->error = "12002";
         $this->authorizationResult = "FAILURE: SYSERROR";
         return; 
      }
      
   } //end create customer
    
   
   public function updateReoccCustomer()
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
     
      if ($this->serverType ==1) 
      {
       $this->doLogin();               
       if ($this->loginOK == false)
       {
          $this->status = 0;
          $this->error = "ERROR 1";
          $this->authorizationResult = "FAILED: INVALID AGENT CODE/PASSWORD";  
          return; 
       }    
      } 
         
      
      // Read input variables, send for processing, parse response and store in output variables.
      try
      {
  
         $params = "AgentCode=" . $this->agentCode;   
      $params = $params . "&Password=" . $this->password; 
       
         $params = $params . "&CustCode="      .  $this->customerCode; 
         $params = $params . "&FirstName=" . $this->firstName;
         $params = $params . "&LastName="  . $this->lastName;
      $params = $params . "&Address="   . $this->streetAddress;
         $params = $params . "&City="      . $this->city;
         $params = $params . "&State="     . $this->state;
         $params = $params . "&ZipCode="   . $this->zipCode; 
         
         $params = $params . "&CCNum1="       .  $creditCard1->cleanNum($this->cardNumber);
         $params = $params . "&CCEXPIRY1="   .  $this->cardExpiry;
         $params = $params . "&MOP1="         .  $this->cardType;
         $params = $params . "&Amount1="      .  $this->dollarAmount;
         
         $params = $params . "&BeginDate1="   .  $this->beginDate;
         $params = $params . "&EndDate1="     .  $this->endDate;
         $params = $params . "&ScheduleType1="     .  $this->scheduleType;
         $params = $params . "&ScheduleDate1="     .  $this->scheduleDate;
         $params = $params . "&Reoccurring1="      .  $this->reoccuringStatus;
         
         
                      
         if ($this->invoiceNumber != "")
         {
          $params = $params . "&InvoiceNum=" . $this->invoiceNumber;
         }
         
         $params = $params . "&Version=" . $this->version;
       
         $url = "";
         $postAction = "/itravel/Customer_Update.pro";
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
           if ($this->serverType==1) {
              curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
           } else {
              curl_setopt($ch, CURLOPT_USERPWD,$this->agentCode . ":" . $this->password); //               
           } 
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
            
           curl_close ($ch); @unlink( $this->cookieFile);
                      
           if ($errorNumber !=0 ) // Error
           {  
              $this->status = 0;
              $this->error = "Error:" + $this->error;
              $this->authorizationResult = "FAILURE: SENDERROR";
              return;   
           } else {
              
              $this->status = 0;
              $this->authorizationResult = "FAILURE: UNKNOWN";
              $this->error = "ERROR!";
              
              if (strpos($iatsReturn,"HTTP 401.") >0 ){
                  $this->status = 0;
                 $this->error = "Error 1";
                 $this->authorizationResult = "INVALID AGENT CODE / PASSWORD";
                 //echo $iatsReturn //full error msg
                 return;
               }
               if (strpos($iatsReturn,"Reoccurring1") <=0 ){
                  $this->status = 0;
                 $this->error = "FAILURE";
                 $this->authorizationResult = $iatsReturn; 
                 return;
               }
              
               $this->status = 1;
               $this->error = "";             
               $this->authorizationResult = "OK: THE CUSTOMER HAS BEEN UPDATED"; 
              
           }//end else  
           
          }catch (Exception $e) { 
            $this->status = 0;
            $this->error = "FAILURE: ERRORCONN" ;
            $this->authorizationResult = "FAILURE: ERRORCONN";
            return;   
          }
          
      } catch (Exception $e)  {
         $this->status = 0;
         $this->error = "12002";
         $this->authorizationResult = "FAILURE: SYSERROR";
         return; 
      }
      
   } //end update customer
   
   public function deleteReoccCustomer()
   {  
      if ($this->serverType ==1) 
      {
       $this->doLogin();               
       if ($this->loginOK == false)
       {
          $this->status = 0;
          $this->error = "ERROR 1";
          $this->authorizationResult = "FAILED: INVALID AGENT CODE/PASSWORD";  
          return; 
       }    
      } 
          
      // Read input variables, send for processing, parse response and store in output variables.
      try
      {
  
         $params = "AgentCode=" . $this->agentCode;   
      $params = $params . "&Password=" . $this->password; 
       
         $params = $params . "&CustCode="      .  $this->customerCode; 
                  
         $params = $params . "&Version=" . $this->version;
       
         $url = "";
         $postAction = "/itravel/Customer_Delete.pro";
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
           if ($this->serverType==1) {
              curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
           } else {
              curl_setopt($ch, CURLOPT_USERPWD,$this->agentCode . ":" . $this->password); //               
           } 
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
            
           curl_close ($ch); @unlink( $this->cookieFile);
                      
           if ($errorNumber !=0 ) // Error
           {  
              $this->status = 0;
              $this->error = "Error:" + $this->error;
              $this->authorizationResult = "FAILURE: SENDERROR";
              return;   
           } else {
              
              $this->status = 0;
              $this->authorizationResult = "FAILURE: UNKNOWN";
              $this->error = "ERROR!";
              
              if (strpos($iatsReturn,"HTTP 401.") >0 ){
                  $this->status = 0;
                 $this->error = "Error 1";
                 $this->authorizationResult = "INVALID AGENT CODE / PASSWORD";
                 //echo $iatsReturn //full error msg
                 return;
               }
               if (strpos($iatsReturn,"Reoccurring1") <=0 ){
                  $this->status = 0;
                 $this->error = "FAILURE";
                 $this->authorizationResult = $iatsReturn; 
                 return;
               }
         
              
               $this->status = 1;
               $this->error = "";             
               $this->authorizationResult = "OK: THE CUSTOMER HAS BEEN DELETE"; 
              
           }//end else  
           
          }catch (Exception $e) { 
            $this->status = 0;
            $this->error = "FAILURE: ERRORCONN" ;
            $this->authorizationResult = "FAILURE: ERRORCONN";
            return;   
          }
          
      } catch (Exception $e)  {
         $this->status = 0;
         $this->error = "12002";
         $this->authorizationResult = "FAILURE: SYSERROR";
         return; 
      }
      
   } //end delete customer
  
    public function processWithCustomerCode()
   {            
      // Read input variables, send for processing, parse response and store in output variables.
      try
      {
  
         $params = "AgentCode=" . $this->agentCode;   
      $params = $params . "&Password=" . $this->password;  
      $params = $params . "&CustCode=" . $this->customerCode; 
      
         $params = $params . "&Total="   .  $this->dollarAmount;
                      
         if ($this->invoiceNumber != "")
         {
          $params = $params . "&InvoiceNum=" . $this->invoiceNumber;
         }
                 
         $params = $params . "&Version=" . $this->version;
       
         $url = "";
         $postAction = "/trams/custcodeauthresult.pro";
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
              $this->authorizationResult = "REJECT: ERRORSEND";
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
      
   }  // end processWithCustomerCode
  
   
   public function getReoccurringCustomerList()
   {  
      if ($this->serverType ==1) 
      {
       $this->doLogin();               
       if ($this->loginOK == false)
       {
          $this->status = 0;
          $this->error = "ERROR 1";
          $this->authorizationResult = "FAILED: INVALID AGENT CODE/PASSWORD";  
          return; 
       }    
      }        
      
      // Read input variables, send for processing, parse response and store in output variables.
      try
      {
  
         $params = "AgentCode=" . $this->agentCode;   
      $params = $params . "&Password=" . $this->password;  
            
         $params = $params . "&Version=" . $this->version;
       
         $url = "";
         $postAction = "/itravel/Customer_ListReocc.pro";
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
           if ($this->serverType==1) {
              curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
           } else {
              curl_setopt($ch, CURLOPT_USERPWD,$this->agentCode . ":" . $this->password); //               
           } 
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
            
           curl_close ($ch);  @unlink( $this->cookieFile);
                      
           if ($errorNumber !=0 ) // Error
           {  
              $this->status = 0;
              $this->error = "Error:" + $this->error;
              $this->authorizationResult = "FAILURE: SENDERROR";
              return;   
           } else {
              
              $this->status = 0;
              $this->authorizationResult = "FAILURE: UNKNOWN";
              $this->error = "ERROR!";
              
              if (strpos($iatsReturn,"HTTP 401.") >0 ){
                  $this->status = 0;
                 $this->error = "Error 1";
                 $this->authorizationResult = "INVALID AGENT CODE / PASSWORD";
                 //echo $iatsReturn; //full error msg
                 return;
               }
               
                          
               if (strpos($iatsReturn,"<!-- #REOCCBEGIN -->") <=0 ){
                  $this->status = 0;
                 $this->error = "FAILURE";
                 $this->authorizationResult = $iatsReturn; 
                 return;
               }
               
               $fReportFile = fopen($this->reportResultFileName,"w");
                                          
               $iatsReturn = strstr($iatsReturn,"<!-- #REOCCBEGIN -->");
               $ipos1 = strpos($iatsReturn,"#REOCCBEGIN -->");
               $resultLine = "";
              while ($ipos1 > 0 ) {
               $ipos2 = strpos($iatsReturn,"<!-- #REOCCEND -->");
               $resultLine = substr($iatsReturn, $ipos1+15,$ipos2 - 15 - $ipos1);
               fwrite($fReportFile,$resultLine . "\n");
               //print $resultLine . "\n";
               $iatsReturn = substr($iatsReturn,$ipos2+18,strlen($iatsReturn)-$ipos2-18);
               //$iatsReturn = strstr($iatsReturn,"<!-- #REOCCBEGIN -->");
                $ipos1 = strpos($iatsReturn,"#REOCCBEGIN -->");
                //echo $iatsReturn;
                //break;
              }
              fclose($fReportFile); 
              
               $this->status = 1;
               $this->error = "";             
               $this->authorizationResult = "OK: GOT RESULT FILE"; 
              
           }//end else  
           
          }catch (Exception $e) { 
            $this->status = 0;
            $this->error = "FAILURE: ERRORCONN" ;
            $this->authorizationResult = "FAILURE: ERRORCONN";
            return;   
          }
          
      } catch (Exception $e)  {
         $this->status = 0;
         $this->error = "12002";
         $this->authorizationResult = "FAILURE: SYSERROR";
         return; 
      }
      
   } //end get customer list
   
 }
