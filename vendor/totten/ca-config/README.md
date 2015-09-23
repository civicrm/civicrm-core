CA_Config is a small PHP library for determining a default
certificate-authority configuration for use by PHP's HTTP/SSL clients.

### Examples

```php
<?php

// For CURL
$caConfig = CA_Config_Curl::singleton();
if ($caConfig->isEnableSSL()) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, );
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt_array($ch, $caConfig->toCurlOptions());
  $response = curl_exec($ch);
} else {
  printf("This system does not support SSL.");
} 


// For PHP Streams
$caConfig = CA_Config_Stream::singleton();
if ($caConfig->isEnableSSL()) {
  $context = stream_context_create(array(
    'ssl' => $caConfig->toStreamOptions(),
  ));
  $data = file_get_contents('https://example.com/', 0, $context);
} else {
  printf("This system does not support SSL.");
}
```

### Helpers

When requesting an instance, one can use either singleton() or probe(). 
singleton() is intended for modest apps that don't have a service container. 
singleton() is just a wrapper for probe() which reads extra configuration
options from a global variable and returns a single instance.

### Testing

This has not been tested on a broad range of configurations, and the
underlying problem is that CA configurations are not well-standardized in
different PHP environments.  To determine if this produces a valid
configuration in your environment, run the phpunit test suite.

If you encounter problems, feel free to submit a patch or to report the
problem.
