{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if empty($printOnly)} {* NO print section starts *}

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
                {if !empty($instanceUrl)}
                  <td>&nbsp;&nbsp;<i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> <a href="{$instanceUrl}">{ts}Existing report(s) from this template{/ts}</a></td>
                {/if}
              </tr>
            </table>
          </td>
          <td>
            <table class="form-layout-compressed" align="right">
              {if !empty($chartSupported)}
                <tr>
                  <td>{$form.charts.html|crmAddClass:big}</td>
                  <td align="right">{$form.$chart.html}</td>
                </tr>
              {/if}
              {if !empty($form.groups)}
                <tr>
                  <td>
                    {$form.groups.html}{$form.$group.html}
                    <script type="text/javascript">
                      {literal}
                      (function($) {
                        $('#groups').val('').change(function() {
                          CRM.confirm({
                            message: ts({/literal}'{ts escape='js' 1='<em>%1</em>'}Add all contacts to %1 group?{/ts}'{literal}, {1: CRM._.escape($('option:selected', '#groups').text())})
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
          flashChartType = '{/literal}{if !empty($chartType)}{$chartType}{else}{/if}{literal}';
        $('#_qf_Summary_submit_pdf, #_qf_Summary_submit_print').prop('disabled', (viewType && flashChartType != viewType));
      });
    });
  </script>
{/literal}
{/if} {* NO print section ends *}
