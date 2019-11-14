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
  <div id="{$cd_edit.name}" class="crm-accordion-wrapper {if $cd_edit.collapse_display neq 0}collapsed{/if}">
    <div class="crm-accordion-header">
      {$cd_edit.title}
    </div>
    <div class="crm-accordion-body">
      {foreach from=$cd_edit.fields item=element key=field_id}
        <table class="crm-info-panel">
          <tr>
            {if $element.options_per_line != 0}
              <td class="label">{$element.field_title}</td>
              <td class="html-adjust">
              {* sort by fails for option per line. Added a variable to iterate through the element array*}
                {foreach from=$element.field_value item=val}
                  {$val}<br/>
                {/foreach}
              </td>
              {else}
                <td class="label">{$element.field_title}</td>
                <td class="html-adjust">{$element.field_value}</td>
            {/if}
          </tr>
        </table>
      {/foreach}
      <div>
        {crmButton p="civicrm/case/cd/edit" q="cgcount=1&action=update&reset=1&type=Case&entityID=$caseID&groupID=$customGroupId&cid=$contactID&subType=$caseTypeID" icon="pencil"}{ts}Edit{/ts}{/crmButton}
      </div>
      <br/>
      <div class="clear"></div>
    </div>
  </div>

  {/foreach}
{/foreach}
<div id="case_custom_edit"></div>
