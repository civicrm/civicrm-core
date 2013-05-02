<?php

/**
 * Copyright (C) 2006 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* This class is used to create the merchant callback results  
  * when a merchant-calculated-feedback structure is received
  *
  * Multiple results are generated depending on the possible
  * combinations for shipping options and address ids
  *
  * Refer demo/responsehandler.php for generating these results
  */
class GoogleMerchantCalculations {
  var $results_arr;
  var $schema_url = "http://checkout.google.com/schema/2";
  function GoogleMerchantCalculations() {
    $this->results_arr = array();
  }

  function AddResult($results) {
    $this->results_arr[] = $results;
  }

  function GetXML() {
    require_once ('xml-processing/xmlbuilder.php');

    $xml_data = new XmlBuilder();
    $xml_data->Push('merchant-calculation-results',
      array('xmlns' => $this->schema_url)
    );
    $xml_data->Push('results');

    foreach ($this->results_arr as $result) {
      if ($result->shipping_name != "") {
        $xml_data->Push('result', array('shipping-name' =>
            $result->shipping_name, 'address-id' => $result->address_id,
          ));
        $xml_data->Element('shipping-rate', $result->ship_price,
          array('currency' => $result->ship_currency)
        );
        $xml_data->Element('shippable', $result->shippable);
      }
      else $xml_data->Push('result', array('address-id' => $result->address_id));

      if ($result->tax_amount != "") $xml_data->Element('total-tax', $result->tax_amount,
        array('currency' => $result->tax_currency)
      );

      if ((count($result->coupon_arr) != 0) ||
        (count($result->giftcert_arr) != 0)
      ) {
        $xml_data->Push('merchant-code-results');

        foreach ($result->coupon_arr as $curr_coupon) {
          $xml_data->Push('coupon-result');
          $xml_data->Element('valid', $curr_coupon->coupon_valid);
          $xml_data->Element('code', $curr_coupon->coupon_code);
          $xml_data->Element('calculated-amount', $curr_coupon->coupon_amount,
            array('currency' => $curr_coupon->coupon_currency)
          );
          $xml_data->Element('message', $curr_coupon->coupon_message);
          $xml_data->Pop('coupon-result');
        }
        foreach ($result->giftcert_arr as $curr_gift) {
          $xml_data->Push('gift-result');
          $xml_data->Element('valid', $curr_gift->gift_valid);
          $xml_data->Element('code', $curr_gift->gift_code);
          $xml_data->Element('calculated-amount', $curr_gift->gift_amount,
            array('currency' => $curr_gift->gift_currency)
          );
          $xml_data->Element('message', $curr_gift->gift_message);
          $xml_data->Pop('gift-result');
        }
        $xml_data->Pop('merchant-code-results');
      }
      $xml_data->Pop('result');
    }
    $xml_data->Pop('results');
    $xml_data->Pop('merchant-calculation-results');
    return $xml_data->GetXML();
  }
}

