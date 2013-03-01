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

/* This class is used to create a Google Checkout result for merchant
  * as a response to merchant-calculations feedback structure 
  * Refer demo/responsehandlerdemo.php for usage of this code
  * 
  * Methods are provided to set the shipping, tax, coupons and gift certificate
  * options
  */
class GoogleResult {
  var $shipping_name;
  var $address_id;
  var $shippable;
  var $ship_price;
  var $ship_currency;

  var $tax_currency;
  var $tax_amount;

  var $coupon_arr = array();
  var $giftcert_arr = array();
  function GoogleResult($address_id) {
    $this->address_id = $address_id;
  }

  function SetShippingDetails($name, $price, $money = "USD",
    $shippable = "true"
  ) {
    $this->shipping_name = $name;
    $this->ship_price    = $price;
    $this->ship_currency = $money;
    $this->shippable     = $shippable;
  }

  function SetTaxDetails($amount, $currency = "USD") {
    $this->tax_amount = $amount;
    $this->tax_currency = $currency;
  }

  function AddCoupons($coupon) {
    $this->coupon_arr[] = $coupon;
  }

  function AddGiftCertificates($gift) {
    $this->giftcert_arr[] = $gift;
  }
}

/* This is a class used to return the results of coupons
  * that the buyer entered code for on the place order page
  */
class GoogleCoupons {
  var $coupon_valid;
  var $coupon_code;
  var $coupon_currency;
  var $coupon_amount;
  var $coupon_message;
  function googlecoupons($valid, $code, $amount, $currency, $message) {
    $this->coupon_valid = $valid;
    $this->coupon_code = $code;
    $this->coupon_currency = $currency;
    $this->coupon_amount = $amount;
    $this->coupon_message = $message;
  }
}

/* This is a class used to return the results of gift certificates
  * that the buyer entered code for on the place order page
  */
class GoogleGiftcerts {
  var $gift_valid;
  var $gift_code;
  var $gift_currency;
  var $gift_amount;
  var $gift_message;
  function googlegiftcerts($valid, $code, $amount, $currency, $message) {
    $this->gift_valid    = $valid;
    $this->gift_code     = $code;
    $this->gift_currency = $currency;
    $this->gift_amount   = $amount;
    $this->gift_message  = $message;
  }
}

