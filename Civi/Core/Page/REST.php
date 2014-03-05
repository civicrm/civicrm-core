<?php
namespace Civi\Core\Page;
use Symfony\Component\HttpFoundation\Response;

class REST extends \CRM_Core_Page {
  function run() {
    $em = \CRM_DB_EntityManager::singleton();
    $query = $em->createQuery("SELECT c FROM \Civi\Contact\Contact c")
      ->setFirstResult(0)
      ->setMaxResults(15);
    $hateoas = \Civi\Core\Container::singleton()->get('hateoas');

    $content = json_encode(json_decode($hateoas->serialize($query->getResult(), 'json')), JSON_PRETTY_PRINT);
    return new Response($content, 200, array(
      'Content-type' => 'text/javascript',
    ));
  }
}
