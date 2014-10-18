<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * This file returns permissioned data required by dojo hierselect widget.
 *
 */

/**
 * call to invoke function
 *
 */

invoke();
exit();

/**
 * Invoke function that redirects to respective functions
 */
function invoke() {
  if (!isset($_GET['return'])) {
    return;
  }

  // intialize the system
  require_once '../civicrm.config.php';
  require_once 'CRM/Core/Config.php';
  $config = &CRM_Core_Config::singleton();

  switch ($_GET['return']) {
    case 'states':
      return states($config);

    case 'countries':
      return countries($config);

    default:
      return;
  }
}

/**
 * Test Function used for new hs-widget.
 */
function states(&$config) {
  $elements = array();
  if (isset($_GET['node1'])) {
    require_once 'CRM/Utils/Type.php';
    $countryId = CRM_Utils_Type::escape($_GET['node1'], 'String');

    if ($countryId) {
      $stateName = NULL;
      if (isset($_GET['name'])) {
        $stateName = trim(CRM_Utils_Type::escape($_GET['name'], 'String'));
        $stateName = str_replace('*', '%', $stateName);
      }

      $default = NULL;
      if (isset($_GET['default'])) {
        $default = trim(CRM_Utils_Type::escape($_GET['default'], 'Boolean'));
      }

      $stateId = NULL;
      if (isset($_GET['id'])) {
        $stateId = CRM_Utils_Type::escape($_GET['id'], 'Positive', FALSE);
      }

      $query = "
SELECT civicrm_state_province.name name, civicrm_state_province.id id
FROM civicrm_state_province
WHERE civicrm_state_province.country_id={$countryId} 
      AND civicrm_state_province.name LIKE LOWER('$stateName%')
ORDER BY name";

      $nullArray = array();
      $dao = CRM_Core_DAO::executeQuery($query, $nullArray);

      if ($default) {
        while ($dao->fetch()) {
          $elements[] = array('name' => ts($dao->name),
            'value' => $dao->id,
          );
        }
      }
      elseif ($stateId) {
        while ($dao->fetch()) {
          if ($dao->id == $stateId) {
            $elements[] = array('name' => ts($dao->name),
              'value' => $dao->id,
            );
          }
        }
      }
      else {
        $count = 0;
        while ($dao->fetch() && $count < 5) {
          $elements[] = array('name' => ts($dao->name),
            'value' => $dao->id,
          );
          $count++;
        }
      }

      if (empty($elements)) {
        if ($stateName != '- type first letter(s) -') {
          $label = '- state n/a -';
        }
        else {
          $label = '- type first letter(s) -';
        }
        $elements[] = array('name' => $label,
          'value' => '',
        );
      }
      elseif (!$default && !$stateId && (!$stateName || $stateName == '- type first letter(s) -')) {
        $elements = array();
        $elements[] = array('name' => '- type first letter(s) -',
          'value' => '',
        );
      }
    }
    else {
      $elements[] = array('name' => '- state n/a -',
        'value' => '',
      );
    }
  }

  require_once "CRM/Utils/JSON.php";
  echo CRM_Utils_JSON::encode($elements, 'value');
}

/**
 * Test Function used for new hs-widget.
 */
function countries(&$config) {
  //get the country limit and restrict the combo select options
  $limitCodes = $config->countryLimit();
  if (!is_array($limitCodes)) {
    $limitCodes = array($config->countryLimit => 1);
  }

  $limitCodes = array_intersect(CRM_Core_PseudoConstant::countryIsoCode(), $limitCodes);
  // added for testing purpose
  //$limitCodes['1101'] = 'IN';
  if (count($limitCodes)) {
    $whereClause = " iso_code IN ('" . implode("', '", $limitCodes) . "')";
  }
  else {
    $whereClause = " 1";
  }

  $elements = array();
  require_once 'CRM/Utils/Type.php';

  $name = NULL;
  if (isset($_GET['name'])) {
    $name = CRM_Utils_Type::escape($_GET['name'], 'String');
  }

  $countryId = NULL;
  if (isset($_GET['id'])) {
    $countryId = CRM_Utils_Type::escape($_GET['id'], 'Positive', FALSE);
  }

  //temporary fix to handle locales other than default US,
  // CRM-2653
  if (!$countryId && $name && $config->lcMessages != 'en_US') {
    $countries = CRM_Core_PseudoConstant::country();

    // get the country name in en_US, since db has this locale
    $countryName = array_search($name, $countries);

    if ($countryName) {
      $countryId = $countryName;
    }
  }

  $validValue = TRUE;
  if (!$name && !$countryId) {
    $validValue = FALSE;
  }

  if ($validValue) {
    if (!$countryId) {
      $name = str_replace('*', '%', $name);
      $countryClause = " civicrm_country.name LIKE LOWER('$name%') ";
    }
    else {
      $countryClause = " civicrm_country.id = {$countryId} ";
    }

    $query = "
SELECT id, name
  FROM civicrm_country
 WHERE {$countryClause}
   AND {$whereClause} 
ORDER BY name";

    $nullArray = array();
    $dao = CRM_Core_DAO::executeQuery($query, $nullArray);

    $count = 0;
    while ($dao->fetch() && $count < 5) {
      $elements[] = array('name' => ts($dao->name),
        'value' => $dao->id,
      );
      $count++;
    }
  }

  if (empty($elements)) {
    if (isset($_GET['id'])) {
      $name = $_GET['id'];
    }

    $elements[] = array('name' => trim($name, "%"),
      'value' => trim($name, "%"),
    );
  }

  require_once "CRM/Utils/JSON.php";
  echo CRM_Utils_JSON::encode($elements, 'value');
}

