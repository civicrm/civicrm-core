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

/**
 * Copyright (C) 2007
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Written and contributed by Phase2 Technology, LLC (http://www.phase2technology.com)
 *
 */

/** 
 * 
 * @package CRM 
 * @author Michael Morris and Gene Chi @ Phase2 Technology <mmorris@phase2technology.com>
 * $Id$ 
 * 
 */

/**
 * 
 * This class sends requests and receives responses from PayJunction (http://www.payjunction.com)
 *
 */
class pjpgHttpsPost
{ 
   var $pjpgRequest;
   var $pjpgResponse;
   
   function pjpgHttpsPost( $pjpgRequestOBJ )
   {
      $this->pjpgRequest=$pjpgRequestOBJ;

        
      // pjRequst
      $pjRequest = $this->pjpgRequest;

      // pjTxn
      $pjTxn = $pjRequest->txnArray[0]->txn;

      // pJCustInfo is pjpgCustInfo
      $pjCustInfo = $pjRequest->txnArray[0]->custInfo;

      // pjBilling 
      $pjBilling = $pjCustInfo->level3data['billing'][0];

      // pjRecur
      $pjRecur = $pjRequest->txnArray[0]->recur->params;


      /*
       * Assign PayJunction post array variable
       *   1. authentication
       *   2. credit card
       *   3. transactioin
       *   4. billing address
       *   5. schedule
       */

      // login and password
      $dc_logon                    = $pjBilling['logon'];
      $dc_password                 = $pjBilling['password'];

      // credit card
      $dc_first_name               = $pjBilling['first_name'];
      $dc_last_name                = $pjBilling['last_name'];
      $dc_number                   = $pjTxn['pan'];
      $dc_expiration_month         = substr($pjTxn['expdate'],4,2);
      $dc_expiration_year          = substr($pjTxn['expdate'],0,4);
      $dc_verification_number      = $pjTxn['cavv'];

      // transaction
      $dc_transaction_amount       = $pjTxn['amount'];
      $dc_notes                    = "No Comment";
      $dc_transaction_type         = "AUTHORIZATION_CAPTURE";
      $dc_version                  = "1.2";

      // billing address
      $dc_address                  = $pjBilling['address'];
      $dc_city                     = $pjBilling['city'];
      $dc_state                    = $pjBilling['province'];
      $dc_zipcode                  = $pjBilling['postal_code'];
      $dc_country                  = $pjBilling['country'];

      // schedule
      $dc_schedule_create          = $pjRecur['dc_schedule_create'];
      $dc_schedule_limit           = $pjRecur['num_recurs'];
      $dc_schedule_periodic_number = $pjRecur['period'];
      $dc_schedule_periodic_type   = $pjRecur['recur_unit'];
      $dc_schedule_start           = $pjRecur['dc_schedule_start'];

      
      /* 
       * PayJunction service URL
       * https://payjunction.com:443/quick_link
       * https://payjunction.com/quick_link
       */
      $url = $pjBilling['url_site'];


      /*
       * Build PayJunction transaction post array
       */
      // recurring transaction
      if ($dc_schedule_create == true)
      {
         // assign to "true" value
         $dc_schedule_create = "true";

         $post_array = array(
            "dc_logon"                    => $dc_logon,
            "dc_password"                 => $dc_password,
            "dc_first_name"               => $dc_first_name,
            "dc_last_name"                => $dc_last_name,
            "dc_expiration_month"         => $dc_expiration_month,
            "dc_expiration_year"          => $dc_expiration_year,
            "dc_number"                   => $dc_number,
            "dc_verification_number"      => $dc_verification_number,
            "dc_transaction_amount"       => $dc_transaction_amount,
            "dc_transaction_type"         => $dc_transaction_type,
            "dc_version"                  => $dc_version,
            "dc_address"                  => $dc_address,
            "dc_city"                     => $dc_city,
            "dc_state"                    => $dc_state,
            "dc_zipcode"                  => $dc_zipcode,
            "dc_country"                  => $dc_country,
            "dc_schedule_create"          => $dc_schedule_create,
            "dc_schedule_limit"           => $dc_schedule_limit,
            "dc_schedule_periodic_number" => $dc_schedule_periodic_number,
            "dc_schedule_periodic_type"   => $dc_schedule_periodic_type,
            "dc_schedule_start"           => $dc_schedule_start 
            );
      }
      // one time transaction
      else
      {
         $post_array = array(
            "dc_logon"                    => $dc_logon,
            "dc_password"                 => $dc_password,
            "dc_first_name"               => $dc_first_name,
            "dc_last_name"                => $dc_last_name,
            "dc_expiration_month"         => $dc_expiration_month,
            "dc_expiration_year"          => $dc_expiration_year,
            "dc_number"                   => $dc_number,
            "dc_verification_number"      => $dc_verification_number,
            "dc_transaction_amount"       => $dc_transaction_amount,
            "dc_transaction_type"         => $dc_transaction_type,
            "dc_version"                  => $dc_version,
            "dc_address"                  => $dc_address,
            "dc_city"                     => $dc_city,
            "dc_state"                    => $dc_state,
            "dc_zipcode"                  => $dc_zipcode,
            "dc_country"                  => $dc_country
            );
      }        


      reset($post_array);

      $request = "";
      while (list ($key, $val) = each($post_array))
      {
         $request .= $key . "=" . urlencode($val) . "&";
      }


      /*
       * PayJunction service request and response
       */ 
      $ch = curl_init($url); 
      curl_setopt($ch, CURLOPT_HEADER, FALSE); 
      curl_setopt($ch, CURLOPT_POST, TRUE); 
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request); 
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
      $content = curl_exec($ch); 
      curl_close($ch);


      // Build response array
      $content = array_values (split (chr (28), $content));
      while ($key_value = next ($content))
      {
         list ($key, $value) = split ("=", $key_value);
         $response[$key] = $value;
      }

      $this->pjpgResponse = $response;
   }


   function getPJpgResponse()
   {
      return $this->pjpgResponse;
   }

}//end class pjpgHttpsPost



class pjpgRequest
{
   var $txnTypes =array(purchase=> array('order_id','cust_id', 'amount', 'pan', 'expdate', 'crypt_type'),
                      refund => array('order_id', 'amount', 'txn_number', 'crypt_type'),
                      ind_refund => array('order_id','cust_id', 'amount','pan','expdate', 'crypt_type'),
                      preauth =>array('order_id','cust_id', 'amount', 'pan', 'expdate', 'crypt_type'),
                      completion => array('order_id', 'comp_amount','txn_number', 'crypt_type'),
                      purchasecorrection => array('order_id', 'txn_number', 'crypt_type'),
                      opentotals => array('ecr_number'),
                      batchclose => array('ecr_number'),
                      batchcloseall => array(),
                      cavv_purchase=> array('order_id','cust_id', 'amount', 'pan', 
                                        'expdate', 'cavv'),
                      cavv_preauth =>array('order_id','cust_id', 'amount', 'pan',         
                                        'expdate', 'cavv')
                       );
   var $txnArray;

   function pjpgRequest($txn)
   {
      if(is_array($txn))
      {
         $this->txnArray = $txn;
      }
      else
      {
         $temp[0]=$txn;
         $this->txnArray=$temp;
      }  
   }

}//end class pjpgRequest



class pjpgCustInfo
{
   var $level3template = array(cust_info=>                   
           array('email','instructions',
                 billing => array ('first_name', 'last_name', 'company_name', 'address',
                                    'city', 'province', 'postal_code', 'country', 
                                    'phone_number', 'fax','tax1', 'tax2','tax3', 
                                    'shipping_cost'),
                 shipping => array('first_name', 'last_name', 'company_name', 'address', 
                                   'city', 'province', 'postal_code', 'country', 
                                   'phone_number', 'fax','tax1', 'tax2', 'tax3',
                                   'shipping_cost'),
                 item   => array ('name', 'quantity', 'product_code', 'extended_amount')
                )
           );
                                     
   var $level3data;
   var $email;
   var $instructions; 
 
   function pjpgCustInfo($custinfo=0,$billing=0,$shipping=0,$items=0)
   {
      if($custinfo)
      {
         $this->setCustInfo($custinfo);
      } 
   }
 
   function setCustInfo($custinfo)
   {
      $this->level3data['cust_info']=array($custinfo);
   }

   function setEmail($email)
   {
      $this->email=$email;
      $this->setCustInfo(array(email=>$email,instructions=>$this->instructions));
   }

   function setInstructions($instructions)
   {
      $this->instructions=$instructions;
      $this->setCustinfo(array(email=>$this->email,instructions=>$instructions));
   }
 
   function setShipping($shipping)
   {
      $this->level3data['shipping']=array($shipping);
   } 
 
   function setBilling($billing)
   {
      $this->level3data['billing']=array($billing);
   } 

   function setItems($items)
   {
      if(! $this->level3data['item'])   
      {
         $this->level3data['item']=array($items);
      }
      else
      {
         $index=count($this->level3data['item']);
         $this->level3data['item'][$index]=$items; 
      }
   }

}//end class pjpgCustInfo



class pjpgRecur{

 var $params;
 var $recurTemplate = array('recur_unit','start_now','start_date',
                            'num_recurs','period','recur_amount');
 
 function pjpgRecur($params)
 { 
    $this->params = $params;
    
    if( (! $this->params['period']) )
    {
      $this->params['period'] = 1;
    }
 }

}//end class pjpgRecur



class pjpgTransaction 
{
   var $txn;
   var $custInfo = null;
   var $recur = null;

   function pjpgTransaction($txn)
   {
      $this->txn=$txn; 
   }

   function getCustInfo()
   {
      return $this->custInfo;
   }

   function setCustInfo($custInfo)
   {
      $this->custInfo = $custInfo;
      array_push($this->txn,$custInfo);
   }

   function getRecur()
   {
      return $this->recur;
   }

   function setRecur($recur)
   {
      $this->recur = $recur;
   }

   function getTransaction()
   {
      return $this->txn;
   } 

} //end class pjpgTransaction
?>
