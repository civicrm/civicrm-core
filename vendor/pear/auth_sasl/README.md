# Auth_SASL - Abstraction of various SASL mechanism responses

[![Build Status](https://travis-ci.org/pear/Auth_SASL.svg?branch=master)](https://travis-ci.org/pear/Auth_SASL)
    

Provides code to generate responses to common SASL mechanisms, including:
- Digest-MD5
- Cram-MD5
- Plain
- Anonymous
- Login (Pseudo mechanism)
- SCRAM	

[Homepage](http://pear.php.net/package/Auth_SASL/)


## Installation
For a PEAR installation that downloads from the PEAR channel:

`$ pear install pear/auth_sasl`

For a PEAR installation from a previously downloaded tarball:

`$ pear install Auth_SASL-*.tgz`

For a PEAR installation from a code clone:

`$ pear install package.xml`

For a local composer installation:

`$ composer install`

To add as a dependency to your composer-managed application:

`$composer require pear/auth_sasl`


## Tests
Run  the tests from a local composer installation:

`$ ./vendor/bin/phpunit`


## License
BSD license
