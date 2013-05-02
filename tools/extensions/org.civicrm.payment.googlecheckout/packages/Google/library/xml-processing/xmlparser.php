<?php
/*
  Copyright (C) 2006 Google Inc.

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/



/* This uses SAX parser to convert XML data into PHP associative arrays
 * When invoking the constructor with the input data, strip out the first XML line 
 * 
 * Member field Description:
 * $params: This stores the XML data. The attributes and contents of XML tags 
 * can be accessed as follows
 * 
 * <addresses>
 *  <anonymous-address id="123"> <test>data 1 </test>
 *  </anonymous-address>
 *  <anonymous-address id="456"> <test>data 2 </test>
 *  </anonymous-address>
 * </addresses>
 * 
 * print_r($this->params) will return 
 Array
(
    [addresses] => Array
        (
            [anonymous-address] => Array
                (
                    [0] => Array
                        (
                            [id] => 123
                            [test] => Array
                                (
                                    [VALUE] => data 1
                                )

                        )

                    [1] => Array
                        (
                            [id] => 456
                            [test] => Array
                                (
                                    [VALUE] => data 2
                                )

                        )

                )

        )

)
  * XmlParser returns an empty params array if it encounters 
  * any error during parsing 
  */
class XmlParser {

  // Stores the object representation of XML data
  var $params = array();
  var $root;
  var $global_index = -1;

  /* Constructor for the class
    * Takes in XML data as input( do not include the <xml> tag
    */
  function XmlParser($input) {
    $xmlp = xml_parser_create();
    xml_parse_into_struct($xmlp, $input, $vals, $index);
    xml_parser_free($xmlp);
    $this->root = strtolower($vals[0]['tag']);
    $this->params = $this->UpdateRecursive($vals);
  }

  /* Returns true if a given variable represents an associative array */
  function is_associative_array($var) {
    return is_array($var) && !is_numeric(implode('', array_keys($var)));
  }

  /* Converts the output of SAX parser into a PHP associative array similar to the 
    * DOM parser output
    */
  function UpdateRecursive($vals) {
    $this->global_index++;
    //Reached end of array
    if ($this->global_index >= count($vals)) {
      return;
    }

    $tag   = strtolower($vals[$this->global_index]['tag']);
    $value = trim($vals[$this->global_index]['value']);
    $type  = $vals[$this->global_index]['type'];

    //Add attributes
    if (isset($vals[$this->global_index]['attributes'])) {
      foreach ($vals[$this->global_index]['attributes'] as $key => $val) {
        $key = strtolower($key);
        $params[$tag][$key] = $val;
      }
    }

    if ($type == 'open') {
      $new_arr = array();

      //Read all elements at the next levels and add to an array
      while ($vals[$this->global_index]['type'] != 'close' &&
        $this->global_index < count($vals)
      ) {
        $arr = $this->UpdateRecursive($vals);
        if (count($arr) > 0) {
          $new_arr[] = $arr;
        }
      }
      $this->global_index++;
      foreach ($new_arr as $arr) {
        foreach ($arr as $key => $val) {
          if (isset($params[$tag][$key])) {
            //If this key already exists
            if ($this->is_associative_array($params[$tag][$key])) {
              //If this is an associative array and not an indexed array
              // remove exisiting value and convert to an indexed array
              $val_key = $params[$tag][$key];
              array_splice($params[$tag][$key], 0);
              $params[$tag][$key][0] = $val_key;
              $params[$tag][$key][] = $val;
            }
            else {
              $params[$tag][$key][] = $val;
            }
          }
          else {
            $params[$tag][$key] = $val;
          }
        }
      }
    }
    elseif ($type == 'complete') {
      if ($value != '') {
        $params[$tag]['VALUE'] = $value;
      }
    }
    else $params = array();
    return $params;
  }

  /* Returns the root of the XML data */
  function GetRoot() {
    return $this->root;
  }

  /* Returns the array representing the XML data */
  function GetData() {
    return $this->params;
  }
}

