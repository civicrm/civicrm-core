{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the template for inline editing of emails *}
<table class="crm-inline-edit-form">
  <tr>
    <td colspan="5">
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location=''}
      </div>
    </td>
  </tr>
  <tr>
    <td>{ts}Email{/ts}&nbsp;
      {if $actualBlockCount lt 5}
        <span id="add-more-email" title="{ts}click to add more{/ts}">
          <a class="crm-hover-button action-item add-more-inline" href="#">{ts}add{/ts}</a>
        </span>
      {/if}
    </td>
    <td>{ts}On Hold?{/ts}</td>
    <td>{ts}Bulk Mailings?{/ts}</td>
    <td>{ts}Primary?{/ts}</td>
    <td>&nbsp;</td>
  </tr>
  {section name='i' start=1 loop=$totalBlocks}
    {assign var='blockId' value=$smarty.section.i.index}
    <tr data-entity='email' data-block-number={$blockId} id="Email_Block_{$blockId}" {if $blockId gt $actualBlockCount}class="hiddenElement"{/if}>
      <td>{$form.email.$blockId.email.html|crmAddClass:email}&nbsp;{$form.email.$blockId.location_type_id.html}</td>
      <td align="center">{$form.email.$blockId.on_hold.html}</td>
      <td align="center" {if !$multipleBulk}class="crm-email-bulkmail"{/if}>{$form.email.$blockId.is_bulkmail.html}</td>
      <td align="center" class="crm-email-is_primary">{$form.email.$blockId.is_primary.1.html}</td>
      <td><a title="{ts}Delete Email{/ts}" class="crm-delete-inline crm-hover-button" href="#"><span class="icon delete-icon"></span></a></td>
    </tr>
    {include file="CRM/Contact/Form/Inline/BlockCustomData.tpl" entity=email customFields=$custom_fields_email blockId=$blockId actualBlockCount=$actualBlockCount}

  {/section}
</table>

{literal}
  <script type="text/javascript">
    CRM.$(function ($) {
      // check first primary radio
      $('#Email_1_IsPrimary').prop('checked', true);
    });
  </script>
{/literal}
