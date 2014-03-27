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
class DoctrineCrudProvider implements EventSubscriberInterface, ProviderInterface {
  public static function getSubscribedEvents() {
    return array(
      Events::RESOLVE => array(
        array('onApiResolve', Events::W_MIDDLE),
      ),
      Events::RESPOND => array(
        array('onApiRespond', Events::W_MIDDLE),
      ),
    );
  }

  /**
   * @var \Civi\API\Registry
   */
  private $apiRegistry;

  /**
   * @var array<string>
   */
  private $supportedActions;

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  private $serializer;

  /**
   * @param \Civi\API\Registry $apiRegistry
   */
  public function __construct($apiRegistry) {
    $this->apiRegistry = $apiRegistry;
    $this->supportedActions = array('create', 'get', 'delete');
    $this->serializer = new \Symfony\Component\Serializer\Serializer(
      array(new \Civi\API\GetSetMethodNormalizer()),
      array(new \Symfony\Component\Serializer\Encoder\JsonEncoder())
    );
  }

  public function onApiResolve(\Civi\API\Event\ResolveEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] != 4) {
      return;
    }

    $entityClass = $this->apiRegistry->getClassByName($apiRequest['entity']);
    if ($entityClass && in_array($apiRequest['action'], $this->supportedActions)) {
      $em = $this->getEntityManager();
      $identifier = $em->getClassMetadata($entityClass)->getIdentifierFieldNames();
      if ($identifier !== array('id')) {
        throw new \API_Exception("DoctrineCrudProvider only supports identifier column 'id'");
      }

      $apiRequest['doctrineClass'] = $entityClass;
      $event->setApiRequest($apiRequest);
      $event->setApiProvider($this);
      $event->stopPropagation();
    }
  }

  public function onApiRespond(\Civi\API\Event\RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] == 4) {
      $this->getEntityManager()->flush();
    }
  }

  public function invoke($apiRequest) {
    switch ($apiRequest['action']) {
      case 'create':
        return $this->createItem($apiRequest);
      case 'delete':
        return $this->deleteItem($apiRequest);
      case 'get':
        return $this->getItems($apiRequest);
      default:
        // We shouldn't get here because onApiResolve() checks $this->supportedActions
        throw new \API_Exception("Unsupported action (" . $apiRequest['action'] . ']');
    }
  }

  public function createItem($apiRequest) {
    $em = $this->getEntityManager();

    if (empty($apiRequest['data']['id'])) {
      $obj = $this->serializer->denormalize($apiRequest['data'], $apiRequest['doctrineClass']);
      $em->persist($obj);
    }
    else {
      $obj = $em->find($apiRequest['doctrineClass'], $apiRequest['data']['id']);
      if (!$obj) {
        throw new \API_Exception("Requested item does not exist", "not-found");
      }
      $context = array('target' => $obj);
      $obj = $this->serializer->denormalize($apiRequest['data'], $apiRequest['doctrineClass'], NULL, $context);
    }

    return civicrm_api3_create_success(
      array(array('id' => $obj->getId()))
    );
  }

  public function getItems($apiRequest) {
    $em = $this->getEntityManager();
    $qb = $em->createQueryBuilder()
      ->from($apiRequest['doctrineClass'], 'e')
      ->select('e');
    foreach ($apiRequest['data'] as $key => $value) {
      if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $key)) {
        $qb->andWhere($qb->expr()->eq("e.$key", ":$key"));
        $qb->setParameter("$key", $value);
      }
      else {
        throw new \API_Exception("Malformed data key [$key]");
      }
    }
    $query = $qb->getQuery();

    $values = array();

    foreach ($query->getResult() as $itemObj) {
      $itemArray = $this->serializer->normalize($itemObj);
      $values[$itemArray['id']] = $itemArray;
    }

    return civicrm_api3_create_success($values);
  }

  /**
   * @return \Doctrine\ORM\EntityManager
   */
  public function getEntityManager() {
    return \CRM_DB_EntityManager::singleton();
  }

  public function deleteItem($apiRequest) {
    if (array_keys($apiRequest['data']->getArray()) == 'id') {
      throw new \API_Exception("Deletion supports only id. Received unexpected keys.", array(
        'keys' => array_keys($apiRequest['data']->getArray())
      ));
    }

    $em = $this->getEntityManager();
    $item = $em->find($apiRequest['doctrineClass'], $apiRequest['data']['id']);
    if ($item) {
      $em->remove($item);
    }

    // Return success as long as post-condition is OK ("$id does not exist")
    return civicrm_api3_create_success(array());
  }

  /**
   * @param \Civi\API\Registry $apiRegistry
   */
  public function setApiRegistry($apiRegistry) {
    $this->apiRegistry = $apiRegistry;
  }

  /**
   * @return \Civi\API\Registry
   */
  public function getApiRegistry() {
    return $this->apiRegistry;
  }
}