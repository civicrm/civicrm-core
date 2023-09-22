The `WalkBatchesEvent` examines the recipient list and pulls out a subset for whom you want to send email.  This is useful if you need strategies for
chunking-out deliveries.

The basic formula for defining your own batch logic is:

```php
<?php
function example_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    array(\Civi\FlexMailer\FlexMailer::EVENT_WALK, '_example_walk_batches')
  );
}

function _example_walk_batches(\Civi\FlexMailer\Event\WalkBatchesEvent $event) {
  // Disable standard delivery
  $event->stopPropagation();

  while (...) {
    $tasks = array();
    $task[] = new FlexMailerTask(...);
    $task[] = new FlexMailerTask(...);
    $task[] = new FlexMailerTask(...);
    $event->visit($tasks);
  }
}

```
