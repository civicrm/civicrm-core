<?php
namespace Civi\API\Page;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\Criteria;

class REST extends \CRM_Core_Page {
  /**
   * @var \Civi\API\Registry
   */
  private $apiRegistry;

  /**
   * @var \Hateoas\Hateoas
   */
  private $hateoas;

  function __construct($title = NULL, $mode = NULL) {
    parent::__construct($title, $mode);

    // TODO proper dependency injection
    $this->apiRegistry = \Civi\Core\Container::singleton()->get('civi_api_registry');
    $this->hateoas = \Civi\Core\Container::singleton()->get('hateoas');
  }


  function run() {
    if (count($this->urlPath) == 4) {
      list ($civicrm, $rest, $entity, $id) = $this->urlPath;
    }
    elseif (count($this->urlPath == 3)) {
      list ($civicrm, $rest, $entity) = $this->urlPath;
      $id = NULL;
    }

    if ($civicrm != 'civicrm' || $rest != 'rest') {
      throw new \CRM_Core_Exception("Bad REST URL");
    }

    $entityClass = $this->apiRegistry->getClassBySlug($entity);
    if ($entityClass === NULL) {
      return new Response(Response::$statusTexts[404], 404, array(
        'Content-type' => 'text/javascript',
      ));
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
      default:
    }
    return new Response(Response::$statusTexts[405], 405, array(
      'Content-type' => 'text/javascript',
    ));
  }

  /**
   * @param string $entityClass
   * @param mixed $id
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getItem($entityClass, $id) {
    $em = \CRM_DB_EntityManager::singleton();
    $qb = $em->createQueryBuilder();
    $qb
      ->from($entityClass, 'e')
      ->select('e')
      ->setParameter('id', $id)
      ->where($qb->expr()->eq('e.id', ':id'));
    $query = $qb->getQuery();

    $content = json_encode(json_decode($this->hateoas->serialize($query->getResult(), 'json')), JSON_PRETTY_PRINT);

    return new Response($content, 200, array(
      'Content-type' => 'text/javascript',
    ));
  }

  /**
   * @param string $entityClass
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getCollection($entityClass) {
    $em = \CRM_DB_EntityManager::singleton();
    $queryBuilder = $em->createQueryBuilder()
      ->from($entityClass, 'e')
      ->select('e');
    $query = $em->createQuery($queryBuilder->getDQL());

    $this->hateoas = \Civi\Core\Container::singleton()->get('hateoas');
    $content = json_encode(json_decode($this->hateoas->serialize($query->getResult(), 'json')), JSON_PRETTY_PRINT);

    return new Response($content, 200, array(
      'Content-type' => 'text/javascript',
    ));
  }

  /**
   * @param string $entityClass
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function createItem($entityClass) {
    return new Response('create ' . $entityClass, 200, array(
      'Content-type' => 'text/javascript',
    ));
  }

  /**
   * @param string $entityClass
   * @param mixed $id
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function deleteItem($entityClass, $id) {
    return new Response('delete ' . $entityClass, 200, array(
      'Content-type' => 'text/javascript',
    ));
  }
}
