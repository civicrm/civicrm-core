{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the template for inline editing of websites *}
{$form.oplock_ts.html}
<table class="crm-inline-edit-form">
    <tr>
      <td colspan="5">
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl"}
        </div>
      </td>
    </tr>

    <tr>
      <td>{ts}Website{/ts}
        {help id="id-website" file="CRM/Contact/Form/Contact.hlp"}
        {if $actualBlockCount lt 5 }
          &nbsp;&nbsp;<span id="add-more-website" title="{ts}click to add more{/ts}"><a class="crm-hover-button action-item add-more-inline" href="#">{ts}add{/ts}</a></span>
        {/if}
      </td>
      <td>{ts}Website Type{/ts}</td>
      <td>&nbsp;</td>
    </tr>

    {section name='i' start=1 loop=$totalBlocks}
    {assign var='blockId' value=$smarty.section.i.index}
    <tr id="Website_Block_{$blockId}" {if $blockId gt $actualBlockCount}class="hiddenElement"{/if}>
      <td>{$form.website.$blockId.url.html|crmAddClass:url}&nbsp;</td>
      <td>{$form.website.$blockId.website_type_id.html}</td>
      {if $blockId gt 1}
        <td><a class="crm-delete-inline crm-hover-button action-item" href="#" title="{ts}Delete Website{/ts}"><span class="icon delete-icon"></span></a></td>
      {/if}
    </tr>
    {/section}
</table>

{literal}
<script type="text/javascript">
    CRM.$(function($) {
      // error handling / show hideen elements duing form validation
      $('tr[id^="Website_Block_"]' ).each( function() {
          if( $(this).find('td:first span').length > 0 ) {
            $(this).removeClass('hiddenElement');
          }
      });
    });
</script>
{/literal}
