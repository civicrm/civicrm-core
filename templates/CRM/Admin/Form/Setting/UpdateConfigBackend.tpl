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
    {ts}When migrating a site to a new server, the paths and URLs of your CiviCRM installation may change. {/ts}
  </p>
  <p>
    {capture assign="pathsURL"}{crmURL p="civicrm/admin/setting/path" q="reset=1"}{/capture}
    {capture assign="urlsURL"}{crmURL p="civicrm/admin/setting/url" q="reset=1"}{/capture}
    {ts 1=$pathsURL 2=$urlsURL}The old paths and URLs may be retained in some database records. Use this form to clear caches or to reset paths to their defaults. If you need further customizations, then update the <a href="%1">Directories</a> and <a href="%2">Resource URLs</a>.{/ts}
  </p>
</div>
<div class="crm-block crm-form-block crm-config-backend-form-block">
  <div>{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
