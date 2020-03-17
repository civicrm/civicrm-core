{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{strip}
  <div class="action-link">
    {if $templateUrl}
      <a href="{$templateUrl}" class="button"><span><i class="crm-i fa-plus-circle"></i> {$newButton}</span></a>
    {/if}
    {if $reportUrl}
      <a href="{$reportUrl}" class="button"><span>{ts}View All Reports{/ts}</span></a>
    {/if}
  </div>
  {if $list}
    <div class="crm-block crm-form-block crm-report-instanceList-form-block">
      {counter start=0 skip=1 print=false}
      {foreach from=$list item=rows key=report}
        <div class="crm-accordion-wrapper crm-accordion_{$report}-accordion ">
          <div class="crm-accordion-header">
            {if $title}{$title}{elseif $report EQ 'Contribute'}{ts}Contribution Reports{/ts}{else}{ts}{$report} Reports{/ts}{/if}</a>
          </div><!-- /.crm-accordion-header -->
          <div class="crm-accordion-body">
            <div id="{$report}" class="boxBlock">
              <table class="report-layout">
                {foreach from=$rows item=row}
                  <tr id="row_{counter}" class="crm-report-instanceList">
                    <td class="crm-report-instanceList-title" style="width:35%"><a href="{$row.url}" title="{ts}Run this report{/ts}">&raquo; <strong>{$row.title}</strong></a></td>
                    <td class="crm-report-instanceList-description">{$row.description}</td>
                    <td>
                    <a href="{$row.viewUrl}" class="action-item crm-hover-button">{ts}View Results{/ts}</a>
                    <span class="btn-slide crm-hover-button">more
                      <ul class="panel">
                        {foreach from=$row.actions item=action key=action_name}
                          <li><a href="{$action.url}" class="{$action_name} action-item crm-hover-button small-popup"
                          {if $action.confirm_message}onclick="return window.confirm({$action.confirm_message|json_encode|htmlspecialchars})"{/if}
                          title="{$action.label|escape}">{$action.label}</a></li>
                        {/foreach}
                      </ul>
                    </span>
                    </td>
                  </tr>
                {/foreach}
              </table>
            </div>
          </div>
        </div>
      {/foreach}
    </div>

    <div class="action-link">
      {if $templateUrl}
        <a href="{$templateUrl}" class="button"><span><i class="crm-i fa-plus-circle"></i> {$newButton}</span></a>
      {/if}
      {if $reportUrl}
        <a href="{$reportUrl}" class="button"><span>{ts}View All Reports{/ts}</span></a>
      {/if}
    </div>

  {else}
    <div class="crm-content-block">
      <div class="messages status no-popup">
        <div class="icon inform-icon"></div>&nbsp;
        {if $myReports}
          {ts}You do not have any private reports. To add a report to this section, edit the Report Settings for a report and set 'Add to My Reports' to Yes.{/ts} &nbsp;
        {else}
          {ts 1=$compName}No %1 reports have been created.{/ts} &nbsp;
          {if $templateUrl}
            {ts 1=$templateUrl}You can create reports by selecting from the <a href="%1">list of report templates here.</a>{/ts}
          {else}
            {ts}Contact your site administrator for help creating reports.{/ts}
          {/if}
        {/if}
      </div>
    </div>
  {/if}
{/strip}
