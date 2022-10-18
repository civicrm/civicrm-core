# CORS

This extension adds CORS headers to responses sent to requests for CiviCRM URLs.

The [MDN CORS documentation](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS) has a great overview of CORS. This documentation assumes that you have read that documentation.

Note: this extension does minimal validation of the actual headers that you are adding and assumes that you 'know what you are doing'.

Most configuration in this extension happens via a `cors_rules` setting, which accepts a JSON array of objects with the following keys:

- **"pattern"** - a CiviCRM URL to match against. The first matching pattern in the list will be used to add CORS headers. If no pattern matches, no CORS headers will be added. The pattern is matched using [fnmatch()](https://www.php.net/manual/en/function.fnmatch.php). Wildcards can be used, for example `civicrm/ajax/api4/*` will match any API4 rest endpoint.
- **"origin"** - A comma seperated list of origins that are permitted to access this resource or an "\*" to allow any origin. Adds an `Access-Control-Allow-Origin` header to the response. Also adds a `Vary: Origin` header to the response when multiple origins are defined.
- **"headers"** _optional_ - A comma seperated list of headers that are permitted for this resource or an "\*". Adds an `Access-Control-Allow-Headers` header to the response.
- **"methods"** _optional_ - A comma seperated list of origins that are permitted to access this resource or an "\*". Adds an `Access-Control-Allow-Methods` header to the response.

A separate `cors_max_age` setting allows you to set a max age for all requests that match a pattern defined above via the `Access-Control-Max_Age` header. It can be left blank.

## Configuration

A CORS settings page can be found at **Administer > System Settings > CORS**. (/civicrm/admin/setting/cors) that exposes the above two settings.

## Example

The following `cors_rule` will allow access from `https://app.example.org` to any APIv4 REST endpoint using the `GET` and `POST` methods and will accept the `X-Civi-Auth` header.

```json
[
  {
    "pattern": "civicrm/ajax/api4/*",
    "origins": "https://app.example.org",
    "headers": "X-Civi-Auth",
    "methods": "GET, POST"
  }
]
```
