The `SendBatchEvent` (`EVENT_SEND`) takes a batch of recipients and messages, and it delivers the messages.  For example, suppose you wanted to
replace the built-in delivery mechanism with a batch-oriented web-service:

```php
<?php
function example_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    array(\Civi\FlexMailer\FlexMailer::EVENT_SEND, '_example_send_batch')
  );
}

function _example_send_batch(\Civi\FlexMailer\Event\SendBatchEvent $event) {
  // Disable standard delivery
  $event->stopPropagation();

  $context = stream_context_create(array(
    'http' => array(
      'method'  => 'POST',
      'header'  => 'Content-type: application/vnd.php.serialize',
      'content' => serialize($event->getTasks()),
    ),
  ));
  return file_get_contents('https://example.org/batch-delivery', FALSE, $context);
}

```
