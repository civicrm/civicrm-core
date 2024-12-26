{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="help">
  {ts}Create reports for your users from any of the report templates listed below. Click on a template title to get started. Click Existing Report(s) to see any reports that have already been created from that template.{/ts}
</div>

<div class="crm-block crm-form-block crm-report-templateList-form-block">
  {strip}
    {if $list}
      {counter start=0 skip=1 print=false}
      {foreach from=$list item=rows key=report}
        <details class="crm-accordion-bold crm-accordion_{$report}-accordion " open>
          <summary>
            {if $report}{if $report EQ 'Contribute'}{ts}Contribution{/ts}{else}{$report}{/if}{else}{ts}Contact{/ts}{/if} Report Templates
          </summary>
          <div class="crm-accordion-body">
            <div id="{$report}" class="boxBlock">
              <table class="report-layout">
                {foreach from=$rows item=row}
                  <tr id="row_{counter}" class="crm-report-templateList">
                    <td class="crm-report-templateList-title" style="width:35%;">
                      <a href="{$row.url}" title="{ts}Create report from this template{/ts}"><i class="crm-i fa-chevron-right" aria-hidden="true"></i> <strong>{$row.title}</strong></a>
                      {if !empty($row.instanceUrl)}
                        <div style="font-size:10px;text-align:right;margin-top:3px;">
                          <a href="{$row.instanceUrl}">{ts}Existing Report(s){/ts}</a>
                        </div>
                      {/if}
                    </td>
                    <td style="cursor:help;" class="crm-report-templateList-description">
                      {$row.description}
                    </td>
                  </tr>
                {/foreach}
              </table>
            </div>
          </div>
        </details>
      {/foreach}
    {else}
      <div class="messages status no-popup">
        {icon icon="fa-info-circle"}{/icon} {ts}There are currently no Report Templates.{/ts}
      </div>
    {/if}
  {/strip}

</div>
