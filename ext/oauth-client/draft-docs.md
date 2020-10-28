# OAuth2 Client Administration (Generic Guide)

OAuth2 defines a protocol for connecting to web-services.  With the `oauth-client` extension, CiviCRM can act as a
client that reads and writes data on remote OAuth2 services (such as Google, Microsoft, Twitter, or Facebook).

Ideally, documentation might be tuned to the the particular service. But we don't have that right now, so
let's consider the general formula.

## Enable and identify the CiviCRM "Redirect URL"

???+ howto "Enable and identify via web UI"

    * Login to CiviCRM and enable the `oauth-client` extension
    * Navigate to "Administer => System Settings => OAuth2" (`civicrm/admin/oauth`)
    * At the bottom of this page, it will report a "Redirect URL" (e.g. `https://example.com/civicrm/oauth-client/return`).

    Make a note of the final redirect URL - you will need it later.

??? howto "Enable and identify via CLI"

    ```
    $ cv en oauth_client
    Enabling extension "oauth_client"

    $ cv url 'civicrm/oauth-client/return'
    "https://example.com/civicrm/oauth-client/return"
    ```

    Make a note of the final redirect URL - you will need it later.

??? question "What happens if my Redirect URL is private/local/internal?"

    The "Redirect URL" is primarily used by your local web-browser. Consequently, it does not need to be available on the public Internet.

    However, the remote web-service may have restrictions -- OAuth2 services generally requires secure "HTTPS" URL.  For local development
    on insecure "HTTP" URLs, it only supports `http://localhost:NNN` and `http://127.0.0.1:NNN`.  If you use a different hostname or IP
    address, then you will need a workaround, e.g.

    * Change the local development environment to support HTTPS or to use the literal URL `http://localhost` or `http://127.0.0.1`.
    * Run the bundled script `bin/local-redir.sh`. This runs a dummy HTTP service `http://127.0.0.1:3000` which you can use as the "Redirect URL".
      Let it run while doing development.
    * Find a public HTTPS server. Add a redirect rule or stub script which will redirect to your preferred URL
      (`https://PUBLIC.EXAMPLE.COM/FIXME` => `http://PRIVATE.EXAMPLE.COM/civicrm/oauth-client/return`). Inform CiviCRM of this URL:

      ```
      $ cv api setting.create oauthClientRedirectUrl='https://PUBLIC.EXAMPLE.COM/FIXME'
      ```

      You may be able to use public services like https://tinyurl.com, but this will depend on the specific restrictions / policies / payloads involved.

## Register the client application with your web-service provider

The registration steps will vary depending on the web-service that CiviCRM is integrating with.
Below, we include some generic instructions. Over time, this may be expanded to include more
specific examples.

???+ howto "Register a client (generic)"

    * Login to the developer or system administration console for your web-service.
    * Locate the section for registering applications.
    * Create a new application:
        * The application will require a "Client ID" and "Client Secret" (together, these may be called "Client Credentials").
          These are usually generated automatically and consituted by a long, random string. Make note of these values - you will need them later.
        * The application will require a "Redirect URL". Copy and past the URL that you identified earlier.

## Register the client application within CiviCRM

???+ howto "Register the client via web UI"

    * Login to CiviCRM and enable the `oauth-client` extension
    * Navigate to "Administer => System Settings => OAuth2" (`civicrm/admin/oauth`)
    * Choose "New Client".
        * Select the "Provider" which matches the remote web-service.
        * Copy in the "Client ID (GUID)" and "Client Secret" from earlier.

??? howto "Register the client via CLI"

    First, we need to identify the type of "Provider" that you will connect to. The `OAuthProvider.get` will list available options

    ```
    $ cv api4 OAuthProvider.get -T +s name,title
    +-------------+----------------------------+
    | name        | title                      |
    +-------------+----------------------------+
    | ms-exchange | Microsoft: Exchange Online |
    | demo        | Local Demo                 |
    +-------------+----------------------------+
    ```

    Note the provider's name and recall the "Client ID" and "Client Secret" from earlier. Pass these to `OAuthClient.create`:

    ```
    $ cv api4 OAuthClient.create +v provider="NAME" +v guid="CLIENT_ID" +v secret="CLIENT_SECRET"
    ```

??? question "What if the provider I want does not appear?"

    This likely indicates that there is no integration. Of course, if you are doing development or experimentation, then you
    should see the development section.

## Grant access

OAuth2 defines a few ways to grant access. Support for these will:

* 

# OAuth2 Client Development


## Define a provider

Signficance of class, league/oauth2-client, options, and mailSettingsTemplate

??? howto "Define a provider via PHP hook"
??? howto "Define a provider via JSON file"

## Create a client

```php
$client = civicrm_api4('OAuthClient', 'create', [
  'provider' => 'NAME',
  'guid' => 'CLIENT_ID',
  'secret' => 'CLIENT_SECRET',
])->single();
```


??? question "What is the difference between client "ID" and "GUID"?"

    In OAuth2's specification, the client "ID" refers to a long, public identifier (often random alphanumerics) registered with the remote
    web-service.  In CiviCRM's data management system, an "ID" is an internal, incrementing integer.  Each `OAuthClient` will have both
    identifiers, stored in two fields:

    * The `id` is the local/internal identifier required by the data-management layer. It is an incrementing integer.
    * The `guid` is the public/external identifier. It is the value presented in web-service requests.

    Depending on context, the word "ID" may describe either.  But in technical examples referencing the Civi API, the symbols `id` and
    `guid` should be interpreted as `id` (local/internal) and `guid` (public/external).

## Grant access

The OAuth2 protocol defines several mechanisms for authenticating to a web-service. There are several common elements -- in each case, one must:

* Identify the client (e.g. using the public ID and the secret).
* Request a set of permissions ("scopes").
* Perform some kind of authentication "flow". (There are ~3 common variations.).
* Receive and store an access token.

The `OAuthClient` entity provides separate methods for each authentication flow.  The choice of method will depend on your use-case and
the policies of the remote web-service -- some require a specific flow, and some are flexible.

Once you've determined the appropriate authentication flow, you can use one these examples to run it:

??? howto "Grant access via client credentials"

   This is the simplest form of authentication - it does not require any extra information.

   ```php
   $token = civicrm_api4('OAuthClient', 'clientCredentials', [
     'where' => [['id', '=', 123]],
     'scopes' => ['first', 'second'],
   ])->single();
   ```

   If successful, the resulting token is stored for usage.

??? howto "Grant access via username/password"

   This form is also fairly simple:

   ```php
   $token = civicrm_api4('OAuthClient', 'userPassword', [
     'where' => [['id', '=', 123]],
     'scopes' => ['first', 'second'],
     'username' => 'johndoe',
     'password' => 'abcd1234',
   ])->single();
   ```

??? howto "Grant access via authorization code"

   In the most well-known OAuth2 flow, one directs the user's browser to visit the remote web-service.
   They will confirm that they wish to grant permission - and then redirect back to CiviCRM.

   ```php
   $start = civicrm_api4('OAuthClient', 'authorizationCode', [
     'where' => [['id', '=', 123]],
     'scopes' => ['first', 'second'],
     'landingUrl' => '...',
   ])->single();
   CRM_Utils_System::redirect($start['url']);
   ```

   Note that this method cannot complete the process. Instead, it returns `$start['url']` -- you
   need to redirect the user to this URL.

??? question "How are tokens stored?"


## Working with access tokens



## Working with access tokens: league/oauth2-client

## Working with access tokens: Guzzle

## Working with access tokens: HTTP Headers

## Working with access tokens: Others



```php
$client = civicrm_api4('OAuthClient', 'get', [
  'where' => [['id', '=', $clientId]],
])->single();
$provider = \Civi::service('oauth2.league')->createProvider($client);
```

## Hooks

```php
function hook_civicrm_oauthReturn($tokenRecord, &$nextUrl);
function hook_civicrm_oauthReturnError($error, $description = NULL, $uri = NULL);
```

# Core Update

## hook_civicrm_mailSetupActions

```php
// https://github.com/civicrm/civicrm-core/pull/18885

function mymod_civicrm_mailSetupActions($setupActions) {
  $setupActions['supermail'] = [
    'title' => ts('Super Mail'),
    'callback' => '_mymod_setup',
  ];
}

function _mymod_setup($setupAction) {
  return [
    'url' => '...the-next-setup-page...',
  ];
}
```


## hook_civicrm_alterMailStore 

https://github.com/civicrm/civicrm-core/pull/18902#

To add a new protocol FIZZBUZZ, you could:

* Register a value in the OptionGroup mail_protocol.
* Create a driver class (eg CRM_Mailing_MailStore_FizzBuzz extends CRM_Mailing_MailStore)
* Use the hook to activate the class:

```php
function hook_civicrm_alterMailStore(&$mailSettings) {
  if ($mailSettings['protocol'] === 'FIZZBUZZ') {
    $mailSettings['factory'] = function ($mailSettings) {
      return new CRM_Mailing_MailStore_FizzBuzz(...);
    };
  }
}
```
