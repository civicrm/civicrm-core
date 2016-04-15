{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-report-templateList-form-block">
  <div class="help">
    {ts}Create reports for your users from any of the report templates listed below. Click on a template title to get started. Click Existing Report(s) to see any reports that have already been created from that template.{/ts}
  </div>
  {strip}
    {if $list}
      {counter start=0 skip=1 print=false}
      {foreach from=$list item=rows key=report}
        <div class="crm-accordion-wrapper crm-accordion_{$report}-accordion ">
          <div class="crm-accordion-header">
            {if $report}{if $report EQ 'Contribute'}{ts}Contribution{/ts}{else}{$report}{/if}{else}Contact{/if} Report Templates
          </div><!-- /.crm-accordion-header -->
          <div class="crm-accordion-body">
            <div id="{$report}" class="boxBlock">
              <table class="report-layout">
                {foreach from=$rows item=row}
                  <tr id="row_{counter}" class="crm-report-templateList">
                    <td class="crm-report-templateList-title" style="width:35%;">
                      <a href="{$row.url}" title="{ts}Create report from this template{/ts}">&raquo; <strong>{$row.title}</strong></a>
                      {if $row.instanceUrl}
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
          </div><!-- /.crm-accordion-body -->
        </div><!-- /.crm-accordion-wrapper -->
      {/foreach}
    {else}
      <div class="messages status no-popup">
        <div class="icon inform-icon"></div>&nbsp; {ts}There are currently no Report Templates.{/ts}
      </div>
    {/if}
  {/strip}

</div>
