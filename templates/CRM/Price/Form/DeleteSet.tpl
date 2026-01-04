{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for confirmation of delete for a price set  *}
    <div class="messages status no-popup">
     <img src="{$config->resourceBase}i/Inform.gif" alt="{ts escape='htmlattribute'}status{/ts}"/>
          {ts 1=$title}WARNING: Deleting this price set will result in the loss of all '%1' data.{/ts} {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}

    </div>

<div class="form-item">
    {include file="CRM/common/formButtons.tpl" location=''}
</div>
