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

/* This class is used to add tax tables to the shopping cart
 * There are two types of tax tables
 * 1. default (there can be only a single default tax table)
 * 2. alternate 
 *
 * Invoke a separate instance of this class for each tax table required
 * Required field is the type
 * Multiple default/alternate tax rules can be added using the AddTaxRulesMethod
 */
class GoogleTaxTable {

  var $type;
  var $name;
  var $tax_rules_arr;
  var $standalone;
  function GoogleTaxTable($type, $name = "", $standalone = "false") {
    if (($type == "default") || ($type == "alternate" && $name != "")) {
      $this->name          = $name;
      $this->type          = strtolower($type);
      $this->tax_rules_arr = array();
      $this->standalone    = $standalone;
    }
  }

  function AddTaxRules($rules) {
    $this->tax_rules_arr[] = $rules;
  }
}

