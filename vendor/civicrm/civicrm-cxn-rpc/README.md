Civi\Cxn\Rpc
------------

Civi\Cxn\Rpc implements an RPC mechanism based on X.509 and JSON.
Generally, it is based on an asymmetric business relationship between three
parties:

 * "Sites" are online properties owned by end-user organizations. They
   represent an organization's canonical data-store.  There are many sites.
   In the tests and comments, we will refer to an example site
   called `SaveTheWhales.org`.
 * "Applications" are online properties with value-added services. They
   supplement the sites.  There are only a few applications, and they must
   certified to go live.  In the tests and comments, we will refer to an
   example service called `AddressCleanup.com`.
 * An arbiter ("Directory Service" and "Certificate Authority") which
   publishes and certifies a list of available applications. In the
   comments, we will refer to a service called `cxn.civicrm.org`.

There is no pre-existing trust between sites and applications, and no
data-exchange can be established until a site opts-in by registering with an
application.  The arbiter facilitates registration (by advertising and
certifying the application's public-key) and revocation (by revoking the
application's public-key) but cannot participate in any other
data-exchanges.

Protocol v0.2
-------------

There are three substantive messages which may be exchanged:

 * [AppMetasMessage](src/Message/AppMetasMessage.php) (`cxn.civicrm.org` => `SaveTheWhales.org`)
   * Use case: A CiviCRM site connects to `cxn.civicrm.org` and requests a list of available applications.
   * Payload: The list of applications includes the title, description, registration URL, and X.509 certificate for each.
   * Crypto: The payload and ttl are signed by `cxn.civicrm.org` (RSA, 2048-bit key) and transferred in plaintext.
 * [RegistrationMessage](src/Message/RegistrationMessage.php) (`SaveTheWhales.org` => `AddressCleanup.com`)
   * Use case: A CiviCRM site registers with an application.
   * Payload: The registration includes a unique identifer for the connection, a shared secret, and a callback URL. (More discussion below.)
   * Crypto: A temporary secret is generated and encrypted with the application's public key (RSA-2048). The payload is encrypted (AES-CBC), dated (ttl), and signed (HMAC-SHA256) using the secret. (See also: [AesHelper](src/AesHelper.php), StdMessage)
   * Note: The registration *request* uses RegistrationMessage, but the *acknowledgement* uses StdMessage.
 * [StdMessage](src/Message/StdMessage.php) (`AddressCleanup.com` => `SaveTheWhales.org`)
   * Use case: Any other secure message exchange between site and application.
   * Use case (typical): An application sends an API call to a site. The site returns a response.
   * Payload (typical): An entity+action+params tuple (as in Civi APIv3).
   * Crypto: The shared-secret is used to generate an AES encryption key and HMAC signing key. The payload and ttl are encrypted with AES-CBC (256-bit), and the ciphertext is signed with HMAC-SHA256. (See also: [AesHelper](src/AesHelper.php)) The same scheme is used for requests and responses.
      * For *requests*, the application's latest cert is transmitted and validated to ensure that the application is still trusted by the arbiter.

Additionally, there are two non-substantive message types. They should *not* be used for major activity but may assist in advisory error-reports:

 * [InsecureMessage](src/Message/InsecureMessage.php)
   * Use case: A server (RegistrationServer or ApiServer) receives an incoming message but cannot authenticate or decrypt it. The server responds with a NACK using InsecureMessage.
   * Payload: An error message.
   * Crypto: Unencrypted and unsigned.
 * [GarbledMessage](src/Message/GarbledMessage.php)
   * Use case: A client (RegistrationClient or ApiClient) receives a response but cannot decode it. (Ex: The server was buggy or badly configured, and PHP error messages were dumped into the ciphertext.)
   * Payload: Unknown
   * Crypto: Unknown

Some considerations:

 * Messages can be passed over HTTP, HTTPS, or any other medium. Passing messages over HTTPS is preferrable (because HTTPS supports more sophisticated cryptography), but even with HTTP all interctions will be encrypted.
 * Application certificates are validated using the CiviCRM CA. This seems better than trusting a hundred random CA's around the world -- there's one point of failure [rather than a hundred points of failure](http://googleonlinesecurity.blogspot.com/2015/03/maintaining-digital-certificate-security.html).
 * If the CA were compromised and if an attacker could execute man-in-the-middle attacks against sites or applications, then it could compromise new connections. However, it cannot compromise existing connections because the CA lacks knowledge or means to manipulate the shared-secret.
 * Sites do not need certificates. Only applications need certificates, and the number of applications is relatively small. Therefore, we don't need automated certificate enrollment. This significantly simplifies the technology and riskness of operating the CA.

Protocol v0.2: RegistrationMessage
----------------------------------

The RegistrationMessage format is used whenever the site (`SaveTheWhales.org`) sends a message to the application (`AddressCleanup.com`). The most common case is to send a `Cxn.register` request.

The message data includes the following keys:

 * `entity`: string. Currently, only "Cxn" is used.
 * `action`: string.
 * `cxn`: array. See `Cxn.php`.
 * `params`: array. Varies depending on entity/action.

The following entity/actions are supported:

 * `Cxn`.`register` (mandatory): Establish a new connection or update an existing connection.
   * `cxn`: For updates, both `cxnId` and `secret` must match the previous registration.
   * `params`: none
   * Note: `RegistrationServer` provides a standard implementation.
 * `Cxn`.`unregister` (mandatory): Destroy an existing connection.
   * `cxn`: Both `cxnId` and `secret` must match the previous registration.
   * `params`: none
   * Note: `RegistrationServer` provides a standard implementation.
 * `Cxn`.`getlink` (optional): Compose a link for an authenticated service.
   * `cxn`: Both `cxnId` and `secret` must match the previous registration.
   * `params`:
     * `page`: string. The name of the page to load (e.g. "settings").
   * Note: Applications have discretion to define links in AppMeta. *If* there are links in `AppMeta`, they will be resolved using `getlink`. To support this, one may extend `RegistrationServer` and define `function onCxnGetlink(...)`.

Protocol v0.1
-------------

Never published or completed. Broadly, the v0.1 protocol relied on certificates for both client and server, and used RSA to encrypt all messages. v0.1 had a few issues:

 * If the certificate authority were compromised, then the high trust placed in the CA could be abused to imitate both client and server, enabling man-in-the-middle attacks against existing connections.
 * Using RSA for everything meant that the crypto was slower for typical API calls.
 * RSA seems to be best when combined with session-key negotiaion (e.g DH) but I don't remember the details of DH well enough to use/adapt it (without delaying the schedule).

Base Classes
------------

When creating a new agent, one should use one of these four helper classes:

 * RegistrationClient: A site uses this to establish a connection to a
   an application.
 * RegistrationServer: An application uses this to accept registrations
   from sites.
 * ApiClient: An application uses this to send API calls to the site.
 * ApiServer: A site uses this to accept API calls from an application.

Policy Recommendations
----------------------

 * Applications should accept new registrations. If a registration is
   attempted with an existing cxnId, then use the shared-secret as
   client authentication -- if the shared-secret matches, then
   accept the updated registration; otherwise, reject it.
 * Applications should periodically validate their connections --
   i.e. issue an API call for "System.get" to ensure that the
   connection corresponds to an active Civi installation running
   a compatible version.
 * Applications should be deployed on HTTPS to provide additional
   security (e.g. forward-secrecy). However, this could impact
   compatibility/reach, and the protocol encrypts all messages
   regardless, so HTTP may still be used.
