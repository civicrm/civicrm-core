The `ComposeBatchEvent` builds the email messages.  Each message is represented as a `FlexMailerTask` with a list of `MailParams`.

Some listeners are "under the hood" -- they define less visible parts of the message, e.g.

 * `BasicHeaders`  defines `Message-Id`, `Precedence`, `From`, `Reply-To`, and others.
 * `BounceTracker` defines various headers for bounce-tracking.
 * `OpenTracker` appends an HTML tracking code to any HTML messages.

The heavy-lifting of composing the message content is also handled by a listener, such as
`DefaultComposer`. `DefaultComposer` replicates traditional CiviMail functionality:

 * Reads email content from `$mailing->body_text` and `$mailing->body_html`.
 * Interprets tokens like `{contact.display_name}` and `{mailing.viewUrl}`.
 * Loads data in batches.
 * Post-processes the message with Smarty (if `CIVICRM_SMARTY` is enabled).

The traditional CiviMail semantics have some problems -- e.g.  the Smarty post-processing is incompatible with Smarty's
template cache, and it is difficult to securely post-process the message with Smarty.  However, changing the behavior
would break existing templates.

A major goal of FlexMailer is to facilitate a migration toward different template semantics.  For example, an
extension might (naively) implement support for Mustache templates using:

```php
<?php
function mustache_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    array(\Civi\FlexMailer\FlexMailer::EVENT_COMPOSE, '_mustache_compose_batch')
  );
}

function _mustache_compose_batch(\Civi\FlexMailer\Event\ComposeBatchEvent $event) {
  if ($event->getMailing()->template_type !== 'mustache') {
    return;
  }

  $m = new Mustache_Engine();
  foreach ($event->getTasks() as $task) {
    if ($task->hasContent()) {
      continue;
    }
    $contact = civicrm_api3('Contact', 'getsingle', array(
      'id' => $task->getContactId(),
    ));
    $task->setMailParam('text', $m->render($event->getMailing()->body_text, $contact));
    $task->setMailParam('html', $m->render($event->getMailing()->body_html, $contact));
  }
}

```

This implementation is naive in a few ways -- it performs separate SQL queries for each recipient; it doesn't optimize
the template compilation; it has a very limited range of tokens; and it doesn't handle click-through tracking.  For
more ideas about these issues, review `DefaultComposer`.

> FIXME: Core's `TokenProcessor` is useful for batch-loading token data.
> However, you currently have to use `addMessage()` and `render()` to kick it
> off -- but those are based on CiviMail template notation.  We should provide
> another function that doesn't depend on the template notation -- so that
> other templates can leverage our token library.

> **Tip**: When you register a listener for `EVENT_COMPOSE`, note the weight.
> The default weight puts your listener in the middle of pipeline -- right
> before the `DefaultComposer`.  However, you might want to position
> relative to other places -- e.g.  `WEIGHT_PREPARE`, `WEIGHT_MAIN`,
> `WEIGHT_ALTER`, or `WEIGHT_END`.
