<?php
namespace Civi\Afform;

use Civi\Afform\Event\AfformAuthorizeEvent;

/**
 * Class DefaultAuthorization
 * @package Civi\Afform
 */
class DefaultAuthorization {

  /**
   * If one disables permission checks on `Afform.submit` or 'Afform.prefill',
   * then it also disables permission checks on nested calls.
   *
   * @param \Civi\Afform\Event\AfformAuthorizeEvent $event
   */
  public static function checkParentAuth(AfformAuthorizeEvent $event) {
    if (!$event->getApiRequest()->getCheckPermissions()) {
      $event->authorize()->setCheckNestedPermission(FALSE);
    }
  }

  /**
   * Determine if $afform['permission'] and the specific inputs are valid together.
   *
   * @param \Civi\Afform\Event\AfformAuthorizeEvent $event
   */
  public static function onAuthorize(AfformAuthorizeEvent $event) {
    $action = $event->getApiRequest()->getActionName();
    if ($action !== 'prefill' && $action !== 'submit') {
      // Not our wheelhouse.
      return;
    }

    // This is the same as CRM_Core_Permission::check("@afform:<name>") but without any reloads.
    if (!\CRM_Core_Permission::check($event->afform['permission'])) {
      // We didn't meet bare minimum.
      $event->prohibit();
      return;
    }

    // Some permissions may depend on other factors...
    switch ($event->afform['permission']) {
      case '@afformGeneric:public':
        // FIXME: assert that $event->apiRequest is only adding new records
        // Is there some better way to determine if there are edits/updates?
        if ($action === 'submit') {
          foreach ($event->apiRequest->getValues() as $entityName => $entityContent) {
            foreach ($entityContent as $row) {
              if (isset($row['fields']['id'])) {
                $event->prohibit();
                return;
              }
            }
          }
        }
        $event->authorize()->setCheckNestedPermission(FALSE);
        return;

      case '@afformGeneric:backend':
        // One could be clever about pre-authorizing based on relevant entities,
        // although that'll get enforced regardless, so KISS for now.
        $event->authorize()->setCheckNestedPermission(TRUE);
        return;

      // case '@afformGeneric:self service':
      //   If you were doing a generic self-service permission, then you might
      //   assert that the contactId matches current user -- and that no other entities are changed.

      default:
        // This is more historically consistent, though maybe it'd be more useful
        // to flip the other way.
        $event->authorize()->setCheckNestedPermission(TRUE);
        return;
    }
  }

}
