<?php

class CRM_Event_Cart_PageCallback {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public static function run($event) {
    switch ($event->page->getVar('_name')) {
      case 'CRM_Event_Page_EventInfo':
        self::alterEventInfo($event);
        break;

      case 'CRM_Event_Page_List':
        self::alterEventList($event);
    }
  }

  public static function alterEventInfo($event) {
    $eventID = $event->page->getVar('_id');
    $link = CRM_Event_Cart_BAO_EventInCart::get_registration_link($eventID);
    $registerText = $link['label'];

    $action = CRM_Utils_Request::retrieve('action', 'String', $event->page, FALSE);
    $action_query = ($action === CRM_Core_Action::PREVIEW) ? "&action=$action" : '';

    $url = CRM_Utils_System::url($link['path'], $link['query'] . $action_query, FALSE, NULL, TRUE, TRUE);

    $event->page->assign('registerText', $registerText);
    $event->page->assign('registerURL', $url);
  }

  public static function alterEventList($event) {
    CRM_Core_Region::instance('crm-event-list-pre')
      ->add(['template' => 'CRM/Event/Cart/eventlistpre.tpl']);

  }

}
