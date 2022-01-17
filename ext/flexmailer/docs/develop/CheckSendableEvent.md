
The `CheckSendableEvent` (`EVENT_CHECK_SENDABLE`) determines whether a draft mailing is fully specified for delivery.

For example, some jurisdictions require that email blasts provide contact
information for the organization (eg street address) and an opt-out link.
Traditionally, the check-sendable event will verify that this information is
provided through a CiviMail token (eg `{action.unsubscribeUrl}`).

But what happens if you implement a new template language (e.g. Mustache) with
a different mail-merge notation? The validation will need to be different.
In this example, we verify the presence of a Mustache-style token, `{{unsubscribeUrl}}`.

```php
<?php
function mustache_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    array(\Civi\FlexMailer\Validator::EVENT_CHECK_SENDABLE, '_mustache_check_sendable')
  );
}

function _mustache_check_sendable(\Civi\FlexMailer\Event\CheckSendableEvent $e) {
  if ($e->getMailing()->template_type !== 'mustache') {
    return;
  }

  if (strpos('{{unsubscribeUrl}}', $e->getMailing()->body_html) === FALSE) {
    $e->setError('body_html:unsubscribeUrl', E::ts('Please include the token {{unsubscribeUrl}}'));
  }
}

```
