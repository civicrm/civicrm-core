# Languages and Services

 * Unix-like environment (Linux, OS X, or a virtual machine)
 * [PHP v5.3+](http://php.net/)
 * [MySQL v5.1+](http://mysql.com/)
 * [NodeJS](https://nodejs.org/)
 * [Git](https://git-scm.com/)
 * Recommended: Apache HTTPD v2.2+
 * Recommended: Ruby/Rake

# Command Line

There are many ways to install MySQL, PHP, and other dependencies -- for
example, `apt-get` and `yum` can download packages automatically; `php.net`
and `mysql.com` provide standalone installers; and MAMP/XAMPP provide
bundled installers.

Civi development should work with most packages -- but there's one proviso:
***the command-line must support standard commands*** (`php`, `mysql`,
`node`, `git`, `bash`, etc).

Some packages are configured properly out-of-the-box. (Linux distributions
do a pretty good job of this.) Other packages require extra configuration
steps (e.g.  [Setup Command Line
PHP](http://wiki.civicrm.org/confluence/display/CRMDOC/Setup+Command-Line+PHP)
for MAMP).

In subsequent steps, the download script will attempt to identify
misconfigurations and display an appropriate message.

# Buildkit

The developer docs reference a large number of developer tools, such as
`drush` (the Drupal command line), `civix` (the CiviCRM code-generator), and
`karma` (the Javascript tester).

Many of these tools are commonly used by web developers, so you may have
already installed a few.  You could install all the tools individually --
but that takes a lot of work.

[civicrm-buildkit](https://github.com/civicrm/civicrm-buildkit) provides
a script which downloads the full collection.

### - Option #1: Full Stack Ubuntu (Opinionated)

If you have a new installation of Ubuntu 12.04 or 14.04, then you can download everything -- buildkit and the system
requirements (`git`, `php`, `apache`, `mysql`, etc) -- with one command.  This command will install buildkit to `~/buildkit`:

```bash
curl -Ls https://civicrm.org/get-buildkit.sh | bash -s -- --full --dir ~/buildkit
```

Note:

 * When executing the above command, you must ***NOT*** run as `root`. (Doing so will produce incorrect permissions.)
   Instead, you must have `sudo` permissions.
 * The `--full` option is opinionated; it specifically installs `php`, `apache`, and `mysql` (rather than `hvm`, `nginx`, `lighttpd`, or `percona`).
   If you try to mix `--full` with alternative systems, then expect conflicts.


### - Option #2: Other Systems

If you already installed the requirements (`git`, `php`, etc), then you can download buildkit to `~/buildkit` with these commands:

```bash
git clone https://github.com/civicrm/civicrm-buildkit.git buildkit
cd buildkit/bin
./civi-download-tools
export PATH="$PWD:$PATH"
```

### - Option #3: Upgrade

If you have previously downloaded buildkit and want to update it, run:

```bash
cd buildkit
git pull
./bin/civi-download-tools
```
