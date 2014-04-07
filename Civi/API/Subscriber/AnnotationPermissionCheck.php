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

namespace Civi\API\Subscriber;
use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * For any API requests that correspond to a Doctrine entity ($apiRequest['doctrineClass']), check
 * permissions specified in Civi\API\Annotation\Permission.
 */
class AnnotationPermissionCheck implements EventSubscriberInterface {
  public static function getSubscribedEvents() {
    return array(
      Events::AUTHORIZE => array(
        array('onApiAuthorize', Events::W_MIDDLE),
      ),
    );
  }

  /**
   * @var \Doctrine\Common\Annotations\Reader
   */
  private $annotationReader;

  /**
   * @var array (string $className => array(string $action => string $permission))
   */
  private $cache;

  /**
   * @var callable
   */
  private $permChecker;

  /**
   * @param \Doctrine\Common\Annotations\Reader $annotationReader
   * @param callable $permChecker a function which returns TRUE if the current user has a given permission
   */
  public function __construct($annotationReader, $permChecker = array('CRM_Core_Permission', 'check')) {
    $this->annotationReader = $annotationReader;
    $this->permChecker = $permChecker;
    $this->cache = array();
  }

  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (!isset($apiRequest['doctrineClass'])) {
      return;
    }

    $perm = $this
      ->getAnnotation($apiRequest['doctrineClass'])
      ->getPermission($apiRequest['action']);
    if (call_user_func($this->permChecker, $perm)) {
      $event->authorize();
      $event->stopPropagation();
    }
  }

  /**
   * @param string $class
   * @return \Civi\API\Annotation\Permission|NULL
   */
  public function getAnnotation($class) {
    if (!array_key_exists($class, $this->cache)) {
      $this->cache[$class] = $this->annotationReader->getClassAnnotation(new \ReflectionClass($class), 'Civi\API\Annotation\Permission');
    }
    else {
      $this->cache[$class] = new \Civi\API\Annotation\Permission(array());
    }
    return $this->cache[$class];
  }
}