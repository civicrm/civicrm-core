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

/* This class is used to add the shipping options for the cart
 * There are 3 types of shipping supported
 * 1. Flat (Type:flat-rate)
 * 2. Pickup (Type:pickup)
 * 3. Merchant calculated (Type:merchant-calculated)
 *
 * Invoke a separate instance of this class for each type of shipping 
 * to be included
 * Required fields are shipping name, shipping  type and price 
 * Allowed and excluded country areas can be specified as part of constructor
 * arguments or using individual Set methods. Possible values here are
 * 1. CONTINENTAL_48
 * 2. FULL_50_STATES 
 * 3. ALL
 * State and zip patterns must be exclusively updated using their individual Set methods
 */
class GoogleShipping {

  var $type;
  var $price;
  var $currency;
  var $name;

  var $allowed_state_areas_arr;
  var $allowed_zip_patterns_arr;
  var $excluded_state_areas_arr;
  var $excluded_zip_patterns_arr;
  var $allowed_country_area;
  var $excluded_country_area;
  var $allowed_restrictions = FALSE;
  var $excluded_restrictions = FALSE;
  function GoogleShipping($name, $type, $price, $money = "USD",
    $allowed_country_area = "",
    $excluded_country_area = ""
  ) {
    $this->price    = $price;
    $this->name     = $name;
    $this->type     = strtolower($type);
    $this->currency = $money;

    if ($allowed_country_area != "") {

      $this->SetAllowedCountryArea($allowed_country_area);
    }

    if ($excluded_country_area != "") {

      $this->SetExcludedCountryArea($excluded_country_area);
    }

    $this->allowed_state_areas_arr = array();
    $this->allowed_zip_patterns_arr = array();
    $this->excluded_state_areas_arr = array();
    $this->excluded_zip_patterns_arr = array();
  }

  function SetAllowedStateAreas($areas) {
    $this->allowed_restrictions = TRUE;
    $this->allowed_state_areas_arr = $areas;
  }

  function SetAllowedZipPattens($zips) {
    $this->allowed_restrictions = TRUE;
    $this->allowed_zip_patterns_arr = $zips;
  }

  function SetExcludedStateAreas($areas) {
    $this->excluded_restrictions = TRUE;
    $this->excluded_state_areas_arr = $areas;
  }

  function SetExcludedZipPatternsStateAreas($zips) {
    $this->excluded_restrictions = TRUE;
    $this->excluded_zip_patterns_arr = $zips;
  }

  function SetAllowedCountryArea($country_area) {
    if ($country_area == "CONTINENTAL_48" ||
      $country_area == "FULL_50_STATES" ||
      $country_area = "ALL"
    ) {
      $this->allowed_country_area = $country_area;
      $this->allowed_restrictions = TRUE;
    }
    else $this->allowed_country_area = "";
  }

  function SetExcludedCountryArea($country_area) {
    if ($country_area == "CONTINENTAL_48" ||
      $country_area == "FULL_50_STATES" ||
      $country_area = "ALL"
    ) {
      $this->excluded_country_area = $country_area;
      $this->excluded_restrictions = TRUE;
    }
    else $this->excluded_country_area = "";
  }
}

