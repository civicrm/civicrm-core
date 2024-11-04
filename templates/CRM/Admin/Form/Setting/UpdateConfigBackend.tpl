{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  <p>
    {ts}On this page you can clear caches or reset paths.{/ts}
  </p>
  <p>
   <b>{ts}Clear Caches{/ts}</b>
  </p>
  <p>
    {ts}This will manually clear site caches. This can be useful if you an upgrade or extension didn't complete cleanly, leaving mismatches in the cache.{/ts}
  </p>
  <p>
    <b>{ts}Reset paths{/ts}</b>
  </p>
  <p>
    {ts}This will reset any path or URL settings in your database to the default values, and then clear caches.{/ts}
  </p>
  <p>
    {ts}This can be useful if you have incorrect or out-of-date custom settings, for example following a site migrating a site to a new server.{/ts}
  </p>
  <p>
    {capture assign="pathsURL"}{crmURL p="civicrm/admin/setting/path" q="reset=1"}{/capture}
    {capture assign="urlsURL"}{crmURL p="civicrm/admin/setting/url" q="reset=1"}{/capture}
    {ts 1=$pathsURL 2=$urlsURL}If you need further customizations, then you can update the <a href="%1">Directories</a> and <a href="%2">Resource URLs</a>.{/ts}
  </p>
</div>
<div class="crm-block crm-form-block crm-config-backend-form-block">
  <div>{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
