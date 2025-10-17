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
  {ts}These settings define the CMS variables that are used with CiviCRM.{/ts}
</div>
{include file='CRM/Admin/Form/Generic.tpl'}

{if $viewsIntegration}
  <div class="crm-block crm-form-block crm-uf-form-block">
    <div class="form-item">
      <h3>{ts}Views integration settings{/ts}</h3>
      <div>{$viewsIntegration}</div>
    </div>
  </div>
{/if}
