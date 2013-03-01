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
/* This class is used to create Tax rules to be added to the tax tables 
 * in the shopping cart
 * Ther are two types of tax rules
 * 1. default (should be added to a default tax table)
 * 2. alternate (should be added to an alternate tax table)
 *
 * Invoke a separate instance of this class for each tax rule to be 
 * added to the cart  
 * Required fields are the rule type and the tax rate
 * Country area can be specified as part of constructor arguments or 
 * using individual Set methods. Possible values here are
 * 1. CONTINENTAL_48
 * 2. FULL_50_STATES 
 * 3. ALL
 * State and zip patterns must be exclusively updated using their 
 * individual Set methods
 */
class GoogleTaxRule {

  var $shipping_taxed;
  var $tax_rule_type;
  var $tax_rate;

  var $state_areas_arr;
  var $zip_patterns_arr;
  var $country_area;
  function GoogleTaxRule($type, $tax_rate, $country_area = "",
    $shipping_taxed = "false"
  ) {
    $this->rax_rule_type = strtolower($type);
    $this->shipping_taxed = $shipping_taxed;
    $this->tax_rate = $tax_rate;

    if ($country_area != "") {

      $this->SetCountryArea($country_area);
    }

    $this->state_areas_arr = array();
    $this->zip_patterns_arr = array();
  }

  function SetStateAreas($areas) {
    if (is_array($areas)) {
      $this->state_areas_arr = $areas;
    }
    else $this->state_areas_arr = array($areas);
  }

  function SetZipPatterns($zips) {
    if (is_array($zips)) {
      $this->zip_patterns_arr = $zips;
    }
    else $this->zip_patterns_arr = array($zips);
  }

  function SetCountryArea($country_area) {
    if ($country_area == "CONTINENTAL_48" || $country_area == "FULL_50_STATES"
      || $country_area = "ALL"
    ) $this->country_area = $country_area;
    else $this->country_area = "";
  }
}

