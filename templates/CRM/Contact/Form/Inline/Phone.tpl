{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the template for inline editing of phones *}
<table class="crm-inline-edit-form">
    <tr>
      <td colspan="5">
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl" location=''}
        </div>
      </td>
    </tr>
    <tr>
      <td>{ts}Phone{/ts}&nbsp;
      {if $actualBlockCount lt 5}
        <span id="add-more-phone" title="{ts escape='htmlattribute'}click to add more{/ts}"><a class="crm-hover-button action-item add-more-inline" href="#">{ts}add{/ts}</a></span>
      {/if}
      </td>
      <td>{ts}Phone Location{/ts}</td>
      <td>{ts}Phone Type{/ts}</td>
      <td>{ts}Primary?{/ts}</td>
      <td>&nbsp;</td>
    </tr>
    {section name='i' start=1 loop=$totalBlocks}
    {assign var='blockId' value=$smarty.section.i.index}
    <tr id="Phone_Block_{$blockId}" {if $blockId gt $actualBlockCount}class="hiddenElement"{/if}>
        <td>{$form.phone.$blockId.phone.html}<span class="crm-phone-ext"> {ts context="phone_ext"}ext.{/ts}&nbsp;{$form.phone.$blockId.phone_ext.html|crmAddClass:four}&nbsp;</span></td>
        <td>{$form.phone.$blockId.location_type_id.html}</td>
        <td>{$form.phone.$blockId.phone_type_id.html}</td>
        <td align="center" class="crm-phone-is_primary">{$form.phone.$blockId.is_primary.1.html}</td>
        <td>
          {if $blockId gt 1}
            <a class="crm-delete-inline crm-hover-button" href="#" title="{ts escape='htmlattribute'}Delete phone{/ts}"><span class="icon delete-icon"></span></a>
          {/if}
        </td>
    </tr>
    {/section}
</table>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    // check first primary radio
    $('#Phone_1_IsPrimary').prop('checked', true );
  });
</script>
{/literal}
