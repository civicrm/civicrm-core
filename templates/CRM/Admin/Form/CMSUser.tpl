{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is for synchronizing CMS user*}
<div class="crm-block crm-form-block crm-cms-user-form-block">
<div class="help">
    <p>{ts}Synchronize Users{/ts}</p>
</div>
<div class="messages status no-popup">
      <img src="{$config->resourceBase}i/Inform.gif" alt="{ts escape='htmlattribute'}status{/ts}"/>
           <span class="label">{ts}Synchronize Users to Contacts:{/ts}</span> {ts}CiviCRM will check each user record for a contact record. A new contact record will be created for each user where one does not already exist.{/ts} {ts}Do you want to continue?{/ts}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

