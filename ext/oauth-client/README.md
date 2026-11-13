# oauth-client

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Installation

This extension is shipped with CiviCRM core and will appear in the list of extensions.

## OpenID Connect

Use the `open-id` provider with a client option `discoveryUrl`, for example
`https://id.example.org/realms/example/.well-known/openid-configuration`.
Alternatively, set `discovery` to an object containing `issuer`,
`authorization_endpoint`, `token_endpoint`, and `jwks_uri`.
Set `issuer` explicitly when it cannot be inferred from the discovery URL.

Endpoints must use HTTPS. This initial implementation accepts RS256 ID tokens.
The authorization-code flow stores a random nonce in its existing transaction
state and validates the ID token before storing the token record or invoking
the token hook. Verified claims are available in `resource_owner`.
Group mapping and account provisioning remain the responsibility of the caller.
