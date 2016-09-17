<?php
/**
 * @file
 * A readme for the packages directory and the package versions.
 *
 * Table of Contents
 * =================
 *  + Introduction
 *  + License Abbreviations
 *  + How-To: Manual upgrade of a forked package
 *  + Package List: PEAR
 *  + Package List: Manually installed
 *  + Package List: Payment processors
 *  + Package List: Unknown status
 *
 *
 * Introduction
 * ============
 *
 * The "packages" directory (aka civicrm-packages.git) is a collection of
 * third-party libraries required by CiviCRM.  Some of the libraries have are
 * downloaded and added by hand; other libraries are added using tools.  This
 * file provides all the notes about how/where to download/upgrade third-party
 * library.
 *
 * Note that the packages directory is generally deprecated for
 * managing dependencies. Instead, one should update a config file
 * in the main civicrm-core project:
 *
 *  - For PHP dependencies, use composer.json
 *  - For client-side CSS/JS resources, use bower.json
 *  - For CLI JS tools, use package.json
 *
 * License Abbreviations
 * =====================
 *
 *   Apache 2  Apache License 2.0
 *   BSD 2-cl. two-clause BSD license
 *   BSD 3-cl. three-clause BSD license
 *   GPL 2     GNU General Public License 2
 *   GPL 2+    GNU General Public License 2 or later
 *   GPL 3     GNU General Public License 3
 *   LGPL 2.1  GNU Lesser General Public License 2.1
 *   LGPL 2.1+ GNU Lesser General Public License 2.1 or later
 *   LGPL 3    GNU Lesser General Public License 3
 *   LGPL 3+   GNU Lesser General Public License 3 or later
 *   PUBDOM    Public domain
 *   PHP 2     PHP License 2.*
 *   PHP 3     PHP License 3.*
 *   X11       X11 (a.k.a. MIT) license
 *
 *
 * How-To: Manual upgrade of a forked package
 * ==========================================
 *
 * 1. download old version of upstream and overwrite packages with it (pear install Archive_Tar-1.3.3)
 * 2. if there are differences, it means we patched the package – do a *reverse* diff and save to a patch file (git diff -R > /tmp/Archive_Tar.diff)
 * 3. download current version and overwrite
 * 4. if there were differences, copy any files that we patched in the old version to packages.orig
 * 5. if there were differences, apply the patch from 2. (patch -p1 < /tmp/Archive_Tar.diff)
 * 6. update this file and commit
 *
 *
 * Package List: PEAR
 * ==================
 * Auth_SASL                     1.0.3      BSD 3-cl.
 * Contact_Vcard_Build           1.1.2      PHP 3          local changes
 * Contact_Vcard_Parse           1.32.0     PHP 3.0
 * Date                          1.4.7      BSD 3-cl.
 * DB                            1.7.13     PHP 3.0
 * DB_DataObject                 1.8.12     PHP 3          local changes
 * DB_Table                      1.5.6      BSD 3-cl.
 * HTML_Common                   1.2.5      PHP 3
 * HTML_QuickForm                3.2.11     PHP 3          local changes, hierselect.php from a very old version (PHP 2)
 * HTML_QuickForm_advmultiselect 1.5.1      BSD 3-cl.      local changes
 * HTML_QuickForm_Controller     1.0.9      PHP 3          local changes
 * HTML_Template_IT              1.2.1      BSD 3-cl.
 * HTTP_Request                  1.4.4      BSD 3-cl.
 * Log                           1.11.5     X11
 * Mail                          1.2.0      PHP 2          local changes
 * Mail_Mime                     1.8.0      BSD 3-cl.      local changes
 * Mail_mimeDecode               1.5.1      BSD 3-cl.
 * Net_Curl                      1.2.5      BSD 3-cl.
 * Net_DIME                      1.0.1      BSD 3-cl.
 * Net_SMTP                      1.6.1      PHP 2          local changes
 * Net_Socket                    1.0.9      PHP 2
 * Net_URL                       1.0.15     BSD 3-cl.
 * Net_UserAgent_Detect          2.5.1      PHP 2
 * Pager                         2.4.8      BSD 3-cl.
 * PEAR                          1.9.0      PHP 3.0
 * PHP_Beautifier                0.1.14     PHP 3.0
 * Services_Twilio               3.10.0     MIT
 * Structures_Graph              1.0.2      LGPL 2.1+
 * System_Command                1.0.6      PHP 2
 * Validate                      0.8.2      BSD 3-cl.
 * Validate_Finance              0.5.4      BSD 3-cl.
 * Validate_Finance_CreditCard   0.5.3      BSD 3-cl.      local changes
 * XML_RPC                       1.5.3      PHP 3
 * XML_Util                      1.2.1      BSD 3-cl.
 *
 *
 * Package List: Manually installed
 * ================================
 * PHP gettext    1.0.7      GPL 2+      http://savannah.nongnu.org/projects/php-gettext/
 * PHPIDS         0.7        LGPL 3+     http://phpids.org/
 * Smarty         2.6.27     LGPL 2.1+   http://smarty.php.net/                                        local changes ( use only lib )
 * Smarty Gettext 1.0b1      LGPL 2.1+   http://smarty.incutio.com/?page=SmartyGettext
 * TCPDF          6.0.020    GPL 3+      http://www.tcpdf.org/                                         doc, examples, images and most of fonts removed
 * eZ Components  2009.1.2   BSD 3-cl.   http://ezcomponents.org/                                      local changes
 * html2text      0.9.1      GPL 3+      http://roundcube.net/download             copied from program/lib/Roundcube/rcube_html2text.php
 * reCAPTCHA      1.10       X11         http://recaptcha.net/
 * OpenFlashChart 2.0        LGPL        http://teethgrinder.co.uk/open-flash-chart-2/
 * Snappy         ??         X11         https://github.com/knplabs/snappy
 * Backbone       0.9.9      X11/MIT     http://backbonejs.org/
 * Backone Forms  c6920b3c89 X11/MIT     https://github.com/powmedia/backbone-forms
 * Backbon.Collectionsubset d3de0d6804 X11/MIT https://github.com/anthonyshort/backbone.collectionsubset
 * Backbone.ModelBinder 448472f X11/MIT  https://github.com/theironcook/Backbone.ModelBinder
 * git-footnote   2013-03-27 LGPL 3      https://github.com/totten/git-footnote
 * json2          2012-10-08 PUBDOM      https://github.com/douglascrockford/JSON-js
 * Marionette     1.0.0-rc2  X11/MIT     http://marionettejs.com/
 * Moment.js      2.5..0     X11/MIT     http://momentjs.com/
 * Simple HTML DOM 1.5       X11/MIT     http://simplehtmldom.sourceforge.net/
 *
 *
 * Package List: Payment processors
 * ================================
 * PayJunction      AGPL 3   by Phase2 Technology
 * PaymentExpress   AGPL 3   by Lucas Baker
 * eWAY             AGPL 3   by Dolphin Software
 *
 *
 * Package List: Unknown status
 * ============================
 * Facebook      BSD 2-cl.
 * Google        Apache 2/GPL 2+
 */
