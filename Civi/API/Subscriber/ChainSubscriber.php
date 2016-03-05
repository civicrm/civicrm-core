<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

namespace Civi\API\Subscriber;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The ChainSubscriber looks for API parameters which specify a nested or
 * chained API call. For example:
 *
 * @code
 * $result = civicrm_api('Contact', 'create', array(
 *   'version' => 3,
 *   'first_name' => 'Amy',
 *   'api.Email.create' => array(
 *     'email' => 'amy@example.com',
 *     'location_type_id' => 123,
 *   ),
 * ));
 * @endcode
 *
 * The ChainSubscriber looks for any parameters of the form "api.Email.create";
 * if found, it issues the nested API call (and passes some extra context --
 * eg Amy's contact_id).
 */
class ChainSubscriber implements EventSubscriberInterface {
  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return array(
      Events::RESPOND => array('onApiRespond', Events::W_EARLY),
    );
  }

  /**
   * @param \Civi\API\Event\RespondEvent $event
   *   API response event.
   *
   * @throws \Exception
   */
  public function onApiRespond(\Civi\API\Event\RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    $result = $event->getResponse();
    if (\CRM_Utils_Array::value('is_error', $result, 0) == 0) {
      $this->callNestedApi($event->getApiKernel(), $apiRequest['params'], $result, $apiRequest['action'], $apiRequest['entity'], $apiRequest['version']);
      $event->setResponse($result);
    }
  }

  /**
   * Call any nested api calls.
   *
   * TODO: We don't really need this to be a separate function.
   * @param \Civi\API\Kernel $apiKernel
   * @param $params
   * @param $result
   * @param $action
   * @param $entity
   * @param $version
   * @throws \Exception
   */
  protected function callNestedApi($apiKernel, &$params, &$result, $action, $entity, $version) {
    $lowercase_entity = _civicrm_api_get_entity_name_from_camel($entity);

    // We don't need to worry about nested api in the getfields/getoptions
    // actions, so just return immediately.
    if (in_array($action, array('getfields', 'getfield', 'getoptions'))) {
      return;
    }

    if ($action == 'getsingle') {
      // I don't understand the protocol here, but we don't want
      // $result to be a recursive array
      // $result['values'][0] = $result;
      $oldResult = $result;
      $result = array('values' => array(0 => $oldResult));
    }
    foreach ($params as $field => $newparams) {
      if ((is_array($newparams) || $newparams === 1) && $field <> 'api.has_parent' && substr($field, 0, 3) == 'api') {

        // 'api.participant.delete' => 1 is a valid options - handle 1
        // instead of an array
        if ($newparams === 1) {
          $newparams = array('version' => $version);
        }
        // can be api_ or api.
        $separator = $field[3];
        if (!($separator == '.' || $separator == '_')) {
          continue;
        }
        $subAPI = explode($separator, $field);

        $subaction = empty($subAPI[2]) ? $action : $subAPI[2];
        $subParams = array(
          'debug' => \CRM_Utils_Array::value('debug', $params),
        );
        $subEntity = _civicrm_api_get_entity_name_from_camel($subAPI[1]);

        foreach ($result['values'] as $idIndex => $parentAPIValues) {

          if ($subEntity != 'contact') {
            //contact spits the dummy at activity_id so what else won't it like?
            //set entity_id & entity table based on the parent's id & entity.
            //e.g for something like note if the parent call is contact
            //'entity_table' will be set to 'contact' & 'id' to the contact id
            //from the parent call. in this case 'contact_id' will also be
            //set to the parent's id
            if (!($subEntity == 'line_item' && $lowercase_entity == 'contribution' && $action != 'create')) {
              $subParams["entity_id"] = $parentAPIValues['id'];
              $subParams['entity_table'] = 'civicrm_' . $lowercase_entity;
            }

            $crm16084 = FALSE;
            if ($subEntity == 'relationship' && $lowercase_entity == 'contact') {
              // if a relationship call is chained to a contact call, we need
              // to check whether contact_id_a or contact_id_b for the
              // relationship is given. If so, don't add an extra subParam
              // "contact_id" => parent_id.
              // See CRM-16084.
              foreach (array_keys($newparams) as $key) {
                if (substr($key, 0, 11) == 'contact_id_') {
                  $crm16084 = TRUE;
                  break;
                }
              }
            }
            if (!$crm16084) {
              $subParams[$lowercase_entity . "_id"] = $parentAPIValues['id'];
            }
          }
          if ($entity != 'Contact' && \CRM_Utils_Array::value(strtolower($subEntity . "_id"), $parentAPIValues)) {
            //e.g. if event_id is in the values returned & subentity is event
            //then pass in event_id as 'id' don't do this for contact as it
            //does some weird things like returning primary email &
            //thus limiting the ability to chain email
            //TODO - this might need the camel treatment
            $subParams['id'] = $parentAPIValues[$subEntity . "_id"];
          }

          if (\CRM_Utils_Array::value('entity_table', $result['values'][$idIndex]) == $subEntity) {
            $subParams['id'] = $result['values'][$idIndex]['entity_id'];
          }
          // if we are dealing with the same entity pass 'id' through
          // (useful for get + delete for example)
          if ($lowercase_entity == $subEntity) {
            $subParams['id'] = $result['values'][$idIndex]['id'];
          }

          $subParams['version'] = $version;
          if (!empty($params['check_permissions'])) {
            $subParams['check_permissions'] = $params['check_permissions'];
          }
          $subParams['sequential'] = 1;
          $subParams['api.has_parent'] = 1;
          if (array_key_exists(0, $newparams)) {
            $genericParams = $subParams;
            // it is a numerically indexed array - ie. multiple creates
            foreach ($newparams as $entityparams) {
              $subParams = array_merge($genericParams, $entityparams);
              _civicrm_api_replace_variables($subParams, $result['values'][$idIndex], $separator);
              $result['values'][$idIndex][$field][] = $apiKernel->run($subEntity, $subaction, $subParams);
              if ($result['is_error'] === 1) {
                throw new \Exception($subEntity . ' ' . $subaction . 'call failed with' . $result['error_message']);
              }
            }
          }
          else {

            $subParams = array_merge($subParams, $newparams);
            _civicrm_api_replace_variables($subParams, $result['values'][$idIndex], $separator);
            $result['values'][$idIndex][$field] = $apiKernel->run($subEntity, $subaction, $subParams);
            if (!empty($result['is_error'])) {
              throw new \Exception($subEntity . ' ' . $subaction . 'call failed with' . $result['error_message']);
            }
          }
        }
      }
    }
    if ($action == 'getsingle') {
      $result = $result['values'][0];
    }
  }

}
