{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for confirmation of delete for a group  *}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
          {if $relatedContributions}
           {ts 1=$title}You cannot delete this Contribution Page because it has already been used to submit a contribution or membership payment. It is recommended that your disable the page instead of deleting it, to preserve the integrity of your contribution records. If you do want to completely delete this contribution page, you first need to search for and delete all of the contribution transactions associated with this page in CiviContribute.{/ts}
          {else}
           {ts}WARNING: Are you sure you want to Delete the selected Contribution Page? A Delete operation cannot be undone. Do you want to continue?{/ts}
        {/if}
      </div>
<div class="form-item">
    {include file="CRM/common/formButtons.tpl" location=''}
</div>
