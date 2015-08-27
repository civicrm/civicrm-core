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

//Point to the correct directory
chdir("..");
//Include all the required files
require_once ('library/googlecart.php');
require_once ('library/googleitem.php');
require_once ('library/googleshipping.php');
require_once ('library/googletaxrule.php');
require_once ('library/googletaxtable.php');

//Invoke any of the provided use cases

UseCase1();
//UseCase2();
//UseCase3();
function UseCase1() {
  //Create a new shopping cart object
  // Your Merchant ID
  $merchant_id = "";
  // Your Merchant Key
  $merchant_key = "";
  $server_type  = "sandbox";
  $cart         = new GoogleCart($merchant_id, $merchant_key, $server_type);

  //Add items to the cart
  $item1 = new GoogleItem("MegaSound 2GB MP3 Player",
    "Portable MP3 player - stores 500 songs", 1, 178
  );
  $item2 = new GoogleItem("AA Rechargeable Battery Pack",
    "Battery pack containing four AA rechargeable batteries", 1, 12
  );
  $cart->AddItem($item1);
  $cart->AddItem($item2);

  //Add shipping options
  $ship = new GoogleShipping("Ground", "flat-rate", 5);
  $ship->SetAllowedCountryArea("CONTINENTAL_48");
  $cart->AddShipping($ship);

  $ship = new GoogleShipping("2nd Day", "flat-rate", 10);
  $ship->SetAllowedCountryArea("FULL_50_STATES");
  $cart->AddShipping($ship);

  //Add tax options
  $tax_rule = new GoogleTaxRule("default", 0.08);
  $tax_rule->SetStateAreas("CA");
  $tax_table = new GoogleTaxTable("default");
  $tax_table->AddTaxRules($tax_rule);
  $cart->AddTaxTables($tax_table);

  //Display Google Checkout button
  echo $cart->CheckoutButtonCode("large");
}

function UseCase2() {
  //Create a new shopping cart object
  // Your Merchant ID
  $merchant_id = "";
  // Your Merchant Key
  $merchant_key = "";
  $server_type  = "sandbox";
  $cart         = new GoogleCart($merchant_id, $merchant_key, $server_type);

  //Add items to the cart
  $item1 = new GoogleItem("Dry Food Pack AA1453",
    " pack of highly nutritious dried food for emergency", 1, 35
  );
  $item2 = new GoogleItem("MegaSound 2GB MP3 Player",
    "Portable MP3 player - stores 500 songs", 1, 178
  );
  $item3 = new GoogleItem("AA Rechargeable Battery Pack",
    "Battery pack containing four AA rechargeable batteries", 1, 12
  );
  $cart->AddItem($item1);
  $cart->AddItem($item2);
  $cart->AddItem($item3);

  //Add shipping options
  $ship = new GoogleShipping("flat", "flat-rate", 5);
  $ship->SetAllowedStateAreas(array("NY", "CA"));
  $cart->AddShipping($ship);

  $ship = new GoogleShipping("pickup", "pickup", 10);
  $cart->AddShipping($ship);

  //Add tax options
  $tax_rule = new GoogleTaxRule("default", 0.02, "FULL_50_STATES");
  $tax_rule->SetStateAreas(array("CA", "NY"));
  $tax_table = new GoogleTaxTable("default");
  $tax_table->AddTaxRules($tax_rule);
  $cart->AddTaxTables($tax_table);

  $tax_rule = new GoogleTaxRule("alternate", 0.05);
  $tax_rule->SetZipPatterns(array("54305", "10027"));
  $tax_rule->SetStateAreas("CA");
  $tax_table = new GoogleTaxTable("alternate", "test");
  $tax_table->AddTaxRules($tax_rule);

  $tax_rule = new GoogleTaxRule("alternate", 0.1);
  $tax_rule->SetStateAreas(array("CO", "FL"));
  $tax_table->AddTaxRules($tax_rule);

  $cart->AddTaxTables($tax_table);

  //Display XML data
  echo htmlentities($cart->GetXML());

  //Display a medium button with a transparent background
  echo $cart->CheckoutButtonCode("medium", "trans");
}

function UseCase3() {
  //Create a new shopping cart object
  // Your Merchant ID
  $merchant_id = "";
  // Your Merchant Key
  $merchant_key = "";
  $server_type  = "sandbox";
  $cart         = new GoogleCart($merchant_id, $merchant_key, $server_type);

  //Add items to the cart
  $item1 = new GoogleItem("Dry Food Pack AA1453",
    " pack of highly nutritious dried food for emergency", 1, 35
  );
  $item2 = new GoogleItem("MegaSound 2GB MP3 Player",
    "Portable MP3 player - stores 500 songs", 1, 178
  );
  $item3 = new GoogleItem("AA Rechargeable Battery Pack",
    "Battery pack containing four AA rechargeable batteries", 1, 12
  );
  $cart->AddItem($item1);
  $cart->AddItem($item2);
  $cart->AddItem($item3);

  //Set request buyer phone
  $cart->SetRequestBuyerPhone("true");

  //Add merchant calculations options
  $cart->SetMerchantCalculations(
    "https://www.example.com/shopping/merchantCalc", "true", "true", "true"
  );
  $ship = new GoogleShipping("merchant-calc",
    "merchant-calculated", 5, "USD", "ALL"
  );
  $ship->SetAllowedStateAreas(array("NY", "CA"));
  $cart->AddShipping($ship);

  $tax_rule = new GoogleTaxRule("default", 0.2);
  $tax_rule->SetStateAreas(array("CA", "NY"));

  $tax_table = new GoogleTaxTable("default");
  $tax_table->AddTaxRules($tax_rule);
  $cart->AddTaxTables($tax_table);

  //Display XML data
  echo htmlentities($cart->GetXML());

  //Display a disabled, small button with a white background
  echo $cart->CheckoutButtonCode("small", "white", "disabled");
}

