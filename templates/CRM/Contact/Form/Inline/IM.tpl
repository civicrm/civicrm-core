{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the template for inline editing of ims *}
<table class="crm-inline-edit-form">
    <tr>
      <td colspan="5">
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl" location=''}
        </div>
      </td>
    </tr>
    <tr>
      <td>{ts}Instant Messenger{/ts}&nbsp;
      {if $actualBlockCount lt 5}
        <span id="add-more-im" title="{ts escape='htmlattribute'}click to add more{/ts}"><a class="crm-hover-button action-item add-more-inline" href="#">{ts}add{/ts}</a></span>
      {/if}
      </td>
      <td>{ts}IM Location{/ts}</td>
      <td>{ts}IM Type{/ts}</td>
      <td>{ts}Primary?{/ts}</td>
      <td>&nbsp;</td>
    </tr>
    {section name='i' start=1 loop=$totalBlocks}
    {assign var='blockId' value=$smarty.section.i.index}
    <tr data-entity='im' data-block-number={$blockId} id="IM_Block_{$blockId}" {if $blockId gt $actualBlockCount}class="hiddenElement"{/if}>
        <td>{$form.im.$blockId.name.html}&nbsp;</td>
        <td>{$form.im.$blockId.location_type_id.html}</td>
        <td>{$form.im.$blockId.provider_id.html}</td>
        <td align="center" class="crm-im-is_primary">{$form.im.$blockId.is_primary.1.html}</td>
        <td>
          {if $blockId gt 1}
            <a class="crm-delete-inline crm-hover-button" href="#" title="{ts escape='htmlattribute'}Delete IM{/ts}"><span class="icon delete-icon"></span></a>
          {/if}
        </td>
      {include file="CRM/Contact/Form/Inline/BlockCustomData.tpl" entity=im customFields=$custom_fields_im blockId=$blockId actualBlockCount=$actualBlockCount}
    </tr>
    {/section}
</table>

{literal}
<script type="text/javascript">
    CRM.$(function($) {
      // check first primary radio
      $('#IM_1_IsPrimary').prop('checked', true );
    });
</script>
{/literal}
