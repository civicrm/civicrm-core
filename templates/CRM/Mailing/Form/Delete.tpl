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
          {ts}WARNING: {/ts}{ts}This action cannot be undone.{/ts} {ts 1=$title}Do you want to delete '%1' mailing continue?{/ts}
    </div>

<div class="form-item">
    {include file="CRM/common/formButtons.tpl" location=''}
</div>
