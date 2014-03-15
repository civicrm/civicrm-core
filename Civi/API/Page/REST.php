<?php
namespace Civi\API\Page;
use Civi\API\Annotation\Permission;
use Civi\API\AuthorizationCheck;
use Civi\Core\Container;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\Criteria;

class REST extends \CRM_Core_Page {
  /**
   * @var \Civi\API\Registry
   */
  private $apiRegistry;

  /**
   * @var \Civi\API\Security
   */
  private $apiSecurity;

  /**
   * @var \Hateoas\Hateoas
   */
  private $hateoas;

  /**
   * @var array (string $mimeType => string ['xml' or 'json'])
   */
  private $mimeTypes;

  function __construct($title = NULL, $mode = NULL, $apiRegistry = NULL, $apiSecurity = NULL, $hateoas = NULL) {
    parent::__construct($title, $mode);

    // TODO proper dependency injection
    $this->apiRegistry = $apiRegistry ? $apiRegistry : Container::singleton()->get('civi_api_registry');
    $this->apiSecurity = $apiSecurity ? $apiSecurity : Container::singleton()->get('civi_api_security');
    $this->hateoas = $hateoas ? $hateoas : Container::singleton()->get('hateoas');
    $this->mimeTypes = array(
      'application/json' => 'json',
      'application/xml' => 'xml',
    );
  }


  function run() {
    try {
      if (count($this->urlPath) == 4) {
        list ($civicrm, $rest, $entity, $id) = $this->urlPath;
      }
      elseif (count($this->urlPath) == 3) {
        list ($civicrm, $rest, $entity) = $this->urlPath;
        $id = NULL;
      }
      else {
        return $this->createError(400, 'Wrong number of items in path');
      }

      if ($civicrm != 'civicrm' || $rest != 'rest') {
        return $this->createError(400, 'Invalid path prefix');
      }

      $entityClass = $this->apiRegistry->getClassBySlug($entity);
      if ($entityClass === NULL) {
        return $this->createError(404, 'Invalid entity');
      }

      switch ($this->request->getMethod()) {
        case 'GET':
          if ($id) {
            return $this->getItem($entityClass, $id);
          }
          else {
            return $this->getCollection($entityClass);
          }
          break;
        case 'POST':
          if (!$id) {
            return $this->createItem($entityClass);
          }
          break;
        case 'DELETE':
          if ($id) {
            return $this->deleteItem($entityClass, $id);
          }
          break;
        default:
      }
      return $this->createError(405);
    } catch (\Exception $e) {
      \CRM_Core_Error::debug_log_message(\CRM_Core_Error::formatTextException($e));
      return $this->createError(500, $e->getMessage());
    }
  }

  /**
   * @param string $entityClass
   * @param mixed $id
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getItem($entityClass, $id) {
    $em = \CRM_DB_EntityManager::singleton();
    $item = $em->find($entityClass, $id);
    if (!$item) {
      return $this->createResponse(404, array());
    }
    if (!$this->apiSecurity->check(new AuthorizationCheck($entityClass, Permission::GET, array($item)))) {
      return $this->createError(403);
    }
    return $this->createResponse(200, array($item));
  }

  /**
   * @param string $entityClass
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getCollection($entityClass) {
    /** @var array $paramBlacklist list of parameters to ignore */
    $paramBlacklist = array('q');

    $em = \CRM_DB_EntityManager::singleton();
    $qb = $em->createQueryBuilder()
      ->from($entityClass, 'e')
      ->select('e');
    foreach ($this->request->query->getIterator() as $key => $value) {
      if ($key{0} != '_' && preg_match('/^[a-zA-Z0-9]+$/', $key) && FALSE === array_search($key, $paramBlacklist)) {
        $qb->andWhere($qb->expr()->eq("e.$key", ":$key"));
        $qb->setParameter("$key", $value);
      }
    }
    $query = $qb->getQuery();

    $results = $query->getResult();
    if (!$this->apiSecurity->check(new AuthorizationCheck($entityClass, Permission::GET, $results))) {
      return $this->createError(403);
    }
    return $this->createResponse(200, $results);
  }

  /**
   * @param string $entityClass
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function createItem($entityClass) {
    $em = \CRM_DB_EntityManager::singleton();
    $obj = $this->hateoas->deserialize($this->request->getContent(), $entityClass, $this->getRequestFormat());
    if (!$this->apiSecurity->check(new AuthorizationCheck($entityClass, Permission::CREATE, array($obj)))) {
      return $this->createError(403);
    }
    $em->persist($obj);
    $em->flush($obj);
    return $this->createResponse(200, array($obj));
  }

  /**
   * @param string $entityClass
   * @param mixed $id
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function deleteItem($entityClass, $id) {
    $em = \CRM_DB_EntityManager::singleton();
    $item = $em->find($entityClass, $id);
    if ($item) {
      if (!$this->apiSecurity->check(new AuthorizationCheck($entityClass, Permission::DELETE, array($item)))) {
        return $this->createError(403);
      }
      $em->remove($item);
    }
    // Return success as long as post-condition is OK ("$id does not exist")
    return $this->createResponse(200, array());
  }

  /**
   * Generate a response by serializing a list of objects
   *
   * @param int $code
   * @param mixed $objects
   */
  public function createResponse($code, $objects) {
    $responseFormat = $this->getResponseFormat();
    $mimeType = array_search($responseFormat, $this->mimeTypes);
    $content = $this->hateoas->serialize($objects, $responseFormat);
    return new Response($content, $code, array(
      'Content-type' => $mimeType,
    ));
  }

  /**
   * Generate a response based on an error code
   *
   * @param int $code HTTP error code
   * @param string|null $message optional suffix for the error message
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function createError($code, $message = NULL) {
    $text = Response::$statusTexts[$code];
    if ($message !== NULL) {
      $text .= ': ' . $message;
    }
    return $this->createResponse($code, array('error' => $text));
  }

  /**
   * @return string eg "json" or "xml"
   */
  public function getResponseFormat() {
    // Giving _format higher priority than Accept: because it makes manual testing easier
    if ($this->request->get('_format')) {
      return $this->request->get('_format');
    }
    if ($this->request->headers->has('Accept')) {
      $accepts = explode(';', $this->request->headers->get('Accept'));
      foreach ($accepts as $accept) {
        $parts = explode(',', $accept);
        foreach ($parts as $part) {
          $part = trim($part);
          if (isset($this->mimeTypes[$part])) {
            return $this->mimeTypes[$part];
          }
        }
      }
    }
    return 'json';
  }

  /**
   * @return string eg "json" or "xml"
   */
  public function getRequestFormat() {
    // Giving _format higher priority than Content-type: because it makes manual testing easier
    if ($this->request->get('_format')) {
      return $this->request->get('_format');
    }
    if ($this->request->headers->has('Content-Type')) {
      if (isset($this->mimeTypes[$this->request->headers->get('Content-Type')])) {
        return $this->mimeTypes[$this->request->headers->get('Content-Type')];
      }
    }
    return 'json';
  }
}
