{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
  <div id="report-tab-group-by-elements" class="civireport-criteria">
    {assign  var="count" value=0}
    <table class="report-layout">
      <tr class="crm-report crm-report-criteria-groupby">
        {foreach from=$groupByElements item=gbElem key=dnc}
        {assign var="count" value=$count+1}
        <td width="25%">
          {$form.group_bys[$gbElem].html}
          {if $form.group_bys_freq && array_key_exists($gbElem, $form.group_bys_freq)}:<br>
            &nbsp;&nbsp;{$form.group_bys_freq[$gbElem].label}&nbsp;{$form.group_bys_freq[$gbElem].html}
          {/if}
        </td>
        {if $count is div by 4}
      </tr><tr class="crm-report crm-report-criteria-groupby">
        {/if}
        {/foreach}
        {if $count is not div by 4}
          <td colspan="4 - ($count % 4)"></td>
        {/if}
      </tr>
    </table>
  </div>
