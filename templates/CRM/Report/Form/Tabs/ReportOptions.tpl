{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $otherOptions}
  <div id="report-tab-other-options" role="tabpanel" class="civireport-criteria">
    <table class="report-layout">
      {assign var="optionCount" value=0}
      <tr class="crm-report crm-report-criteria-field">
        {foreach from=$otherOptions item=optionField key=optionName}
        {if array_key_exists($optionName, $form)}
          {assign var="optionCount" value=$optionCount+1}
          <td>{if $form.$optionName.label}{$form.$optionName.label}&nbsp;{/if}{$form.$optionName.html}</td>
          {if $optionCount is div by 2}
        </tr><tr class="crm-report crm-report-criteria-field">
          {/if}
        {/if}
        {/foreach}
        {if $optionCount is not div by 2}
          <td colspan="2 - ($count % 2)"></td>
        {/if}
      </tr>
    </table>
  </div>
{/if}
