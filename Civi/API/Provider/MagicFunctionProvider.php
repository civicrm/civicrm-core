<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

namespace Civi\API\Provider;
use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class manages the loading of API's using strict file+function naming
 * conventions.
 */
class MagicFunctionProvider implements EventSubscriberInterface, ProviderInterface {
  public static function getSubscribedEvents() {
    return array(
      Events::RESOLVE => array(
        array('onApiResolve', Events::W_MIDDLE),
      ),
    );
  }

  public function onApiResolve(\Civi\API\Event\ResolveEvent $event) {
    $apiRequest = $event->getApiRequest();
    $resolved = _civicrm_api_resolve($apiRequest);
    if ($resolved['function']) {
      $apiRequest += $resolved;
      $event->setApiRequest($apiRequest);
      $event->setApiProvider($this);
      $event->stopPropagation();
    }
  }

  public function invoke($apiRequest) {
    $function = $apiRequest['function'];
    if ($apiRequest['function'] && $apiRequest['is_generic']) {
      // Unlike normal API implementations, generic implementations require explicit
      // knowledge of the entity and action (as well as $params). Bundle up these bits
      // into a convenient data structure.
      $result = $function($apiRequest);
    }
    elseif ($apiRequest['function'] && !$apiRequest['is_generic']) {
      $result = isset($extra) ? $function($apiRequest['params'], $extra) : $function($apiRequest['params']);
    }
    return $result;
  }

}