{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for confirmation of convert for a group from smart to regular *}
<div class="crm-block crm-form-block crm-group-convert-form-block">

<h3>{ts}Convert Group{/ts}</h3>
    <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
    {ts 1=$title}Are you sure you want to convert the group %1?{/ts}<br /><br />

    {ts}Converting this group will NOT delete the member contact records, the group memberships or the group itself. However, this group will be converted to a static group containing all present members of the smart group. This action cannot be undone.{/ts}
    </div>

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>