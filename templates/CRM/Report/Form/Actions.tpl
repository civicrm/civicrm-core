{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
    {if $rows}
        <div class="crm-tasks">
        {assign var=print value="_qf_"|cat:$form.formName|cat:"_submit_print"}
        {assign var=pdf   value="_qf_"|cat:$form.formName|cat:"_submit_pdf"}
        {assign var=csv   value="_qf_"|cat:$form.formName|cat:"_submit_csv"}
        {assign var=group value="_qf_"|cat:$form.formName|cat:"_submit_group"}
        {assign var=chart value="_qf_"|cat:$form.formName|cat:"_submit_chart"}
        <table style="border:0;">
            <tr>
                <td>
                    <table class="form-layout-compressed">
                        <tr>
                            <td>{$form.$print.html}&nbsp;&nbsp;</td>
                            <td>{$form.$pdf.html}&nbsp;&nbsp;</td>
                            <td>{$form.$csv.html}&nbsp;&nbsp;</td>
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
                                <td>{$form.groups.html|crmAddClass:big}</td>
                                <td align="right">{$form.$group.html}</td>
                            </tr>
                        {/if}
                    </table>
                </td>
            </tr>
        </table>
        </div>
    {/if}

    {literal}
    <script type="text/javascript">
    var flashChartType = {/literal}{if $chartType}'{$chartType}'{else}''{/if}{literal};
    function disablePrintPDFButtons( viewtype ) {
      if (viewtype && flashChartType != viewtype) {
        cj('#_qf_Summary_submit_pdf').attr('disabled', true).addClass('button-disabled');
  cj('#_qf_Summary_submit_print').attr('disabled', true).addClass('button-disabled');
      } else {
        cj('#_qf_Summary_submit_pdf').removeAttr('disabled').removeClass('button-disabled');
  cj('#_qf_Summary_submit_print').removeAttr('disabled').removeClass('button-disabled');
      }
    }
    </script>
    {/literal}
{/if} {* NO print section ends *}
