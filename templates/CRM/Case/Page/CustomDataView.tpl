{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Custom Data view mode*}
{foreach from=$viewCustomData item=customValues key=customGroupId}
  {foreach from=$customValues item=cd_edit key=cvID}
    {assign var='index' value=$groupId|cat:"_$cvID"}
  <details id="{$cd_edit.name}" class="crm-accordion-bold" {if $cd_edit.collapse_display neq 0}{else}open{/if}>
    <summary>
      {$cd_edit.title}
    </summary>
    <div class="crm-accordion-body">
      {if !empty($cd_edit.fields)}
        <table class="crm-info-panel">
          {foreach from=$cd_edit.fields item=element key=field_id}
            <tr>
              <td class="label">{$element.field_title}</td>
              <td class="html-adjust">
                {if $element.options_per_line != 0}
                  {* sort by fails for option per line. Added a variable to iterate through the element array*}
                  {foreach from=$element.field_value item=val}
                    {$val}<br/>
                  {/foreach}
                {elseif $element.field_data_type == 'Memo'}
                  {$element.field_value|nl2br}
                {elseif $element.field_data_type == 'Money' && $element.field_type == 'Text'}
                  {$element.data|crmMoney}
                {elseif $element.field_data_type == 'ContactReference' && $element.contact_ref_links}
                  {$element.contact_ref_links|join:', '}
                {else}
                  {$element.field_value}
                {/if}
              </td>
            </tr>
          {/foreach}
        </table>
      {/if}
      <div>
        {crmButton p="civicrm/case/cd/edit" q="cgcount=1&action=update&reset=1&type=Case&entityID=$caseID&groupID=$customGroupId&cid=$contactID&subType=$caseTypeID" icon="pencil"}{ts}Edit{/ts}{/crmButton}
      </div>
      <br/>
      <div class="clear"></div>
    </div>
  </details>

  {/foreach}
{/foreach}
<div id="case_custom_edit"></div>

