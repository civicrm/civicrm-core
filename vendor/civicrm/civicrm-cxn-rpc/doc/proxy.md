# Firewalls and Proxies

CiviConnect is designed to connect two parties: *CiviCRM sites* (online properties owned
by end-users) and *CiviConnect applications* (value-added services).

A *CiviConnect application* **must** be accessible on the public Internet.

A *CiviCRM site* **should** be accessible on the public Internet. This ensures that
constituents can access the site (to make donations, sign up for newsletters, etc),
and it enables applications to send updates to the site.

However, there are a few circumstances where a CiviCRM site is not be
accessible on the public Internet, such as:

 1. *Localhost*: A developer or designer works on a local test site before
    making changes to the real site.
 2. *Organizational firewall*: The CiviCRM site is *only* used for internal
    communications. As a preventive security measure, it is restricted to
    a private network or VPN.

### Should I setup a proxy?

If you have to ask, then probably not. When systems are isolated, this is often
*intentional* and *beneficial*, e.g.

 * On localhost, you might setup a *copy* of your site and do crazy, experimental, buggy,
   misconfigured, accidental things. This is great if your site is isolated. But if
   you use CiviConnect on this site, then those crazy things could propagate to the real
   world.
 * Organizations with a firewall or VPN are often very *security conscious*. They
   may not trust third-party providers generally, or they may wish to setup any
   connections manually as a way to audit them.

However, if you are specifically testing CiviConnect and using dummy sites
with dummy data, then you may want to setup a proxy to make a more realistic test.

### Setup

Determine your network addresses:

 * Identify your site's webserver. For example, this might be `127.0.0.1:80`.
 * Pick a proxy server. It won't need any special software, but it does need
   a public IP and SSH. For example, this might be `proxy.example.com`.
 * Pick a proxy port. This can be anything over `1024`. For example, `6000`.

Next, configure SSH to support proxying:

 * Login to `proxy.example.com`
 * Edit `/etc/ssh/sshd_config` and set the option `GatewayPorts yes`
 * Restart `sshd` (eg `service ssh restart`)

Start the proxy by running this command on `localhost`:

```bash
ssh -N -R 6000:127.0.0.1:80 -v proxy.example.com
```

Finally, configure your CiviCRM site to use the proxy by adding this to `civicrm.settings.php`:

```php
define('CIVICRM_CXN_VIA', 'proxy.example.com:6000');
```

At this point, you can use the CiviConnect like any public site.

### Tip: Re-register

If you have previously connected your site to an application without using
a proxy (or using a different proxy), then it still has old information. To
fix this, note the application's GUID and re-register:

```bash
drush cvapi cxn.register app_guid=app:org.civicrm.profile
```

Alternatively, if you don't care about preserving data, then use the admin UI
to click "Disconnect" and "Connect".

### Tip: Virtual hosts

If you have several localhost sites running on `127.0.0.1:80`, they can all use
the same proxy server. Simply update `civicrm.settings.php` for each.

If you have several localhost sites running on *different* IPs or ports, they you
must setup multiple proxies.

### Tip: Other proxy techniques

This document uses `ssh` because `ssh` is widely available
and widely compatible. However, there is nothing special about
`ssh`. CiviConnect should work with these techniques:

 * Port forwarding (eg `autossh`, `rstunnel`, `iptables`, and many home routers)
 * HTTP reverse-proxying (e.g. `apache`, `nginx`, `varnish`)

However, those are left as an exercise for the reader (undocumented/unsupported).