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
{if !$printOnly} {* NO print section starts *}

  {* build the print pdf buttons *}
    <div class="crm-tasks">
      {assign var=group value="_qf_"|cat:$form.formName|cat:"_submit_group"}
      {assign var=chart value="_qf_"|cat:$form.formName|cat:"_submit_chart"}
      <table style="border:0;">
        <tr>
          <td>
            <table class="form-layout-compressed">
              <tr>
                {include file="CRM/common/tasks.tpl" location="botton"}
                {if $instanceUrl}
                  <td>&nbsp;&nbsp;&raquo;&nbsp;<a href="{$instanceUrl}">{ts}Existing report(s) from this template{/ts}</a></td>
                {/if}
              </tr>
            </table>
          </td>
          <td>
            <table class="form-layout-compressed" align="right">
              {if $chartSupported}
                <tr>
                  <td>{$form.charts.html|crmAddClass:big}</td>
                  <td align="right">{$form.$chart.html}</td>
                </tr>
              {/if}
              {if $form.groups}
                <tr>
                  <td>
                    {$form.groups.html}{$form.$group.html}
                    <script type="text/javascript">
                      {literal}
                      (function($) {
                        $('#groups').val('').change(function() {
                          CRM.confirm({
                            message: ts({/literal}'{ts escape='js' 1='<em>%1</em>'}Add all contacts to %1 group?{/ts}'{literal}, {1: $('option:selected', '#groups').text()})
                          })
                            .on({
                              'crmConfirm:yes': function() {
                                $('#groups').siblings(':submit').click();
                              },
                              'crmConfirm:no dialogclose': function() {
                                $('#groups').select2('val', '');
                              }
                            });
                        });
                      })(CRM.$);
                      {/literal}
                    </script>
                  </td>
                </tr>
              {/if}
            </table>
          </td>
        </tr>
      </table>
    </div>

{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      // Disable print/pdf output of charts
      $('select[name=charts]', 'form.crm-report-form').change(function() {
        var viewType = $(this).val(),
          flashChartType = '{/literal}{if $chartType}{$chartType}{else}{/if}{literal}';
        $('#_qf_Summary_submit_pdf, #_qf_Summary_submit_print').prop('disabled', (viewType && flashChartType != viewType));
      });
    });
  </script>
{/literal}
{/if} {* NO print section ends *}
