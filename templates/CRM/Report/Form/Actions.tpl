{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

      {*top buttons*}
      {assign var=save value="_qf_"|cat:$form.formName|cat:"_submit_save"}
      {assign var=next value="_qf_"|cat:$form.formName|cat:"_submit_next"}
      <div class="crm-submit-buttons-top">
        {$form.buttons.html}&nbsp;&nbsp;&nbsp;&nbsp;
        {$form.$save.html}
        {if $mode neq 'template' && $form.$next}
          {$form.$next.html}
        {/if}
      </div>

        {assign var=print value="_qf_"|cat:$form.formName|cat:"_submit_print"}
        {assign var=pdf   value="_qf_"|cat:$form.formName|cat:"_submit_pdf"}
        {assign var=csv   value="_qf_"|cat:$form.formName|cat:"_submit_csv"}
        {assign var=group value="_qf_"|cat:$form.formName|cat:"_submit_group"}
        {assign var=chart value="_qf_"|cat:$form.formName|cat:"_submit_chart"}
      <div class="crm-buttons-actions">
        <div class="left">
        {$form.$csv.html}

                            {if $instanceUrl}
          <a href="{$instanceUrl}">{ts}Existing report(s) from this template{/ts}</a>
                            {/if}
        </div>

        <div class="right">
                        {if $chartSupported}
          {$form.charts.html|crmReplace:class:big}
          {$form.$chart.html}
                        {/if}
                        {if $form.groups}
          {$form.groups.html|crmReplace:class:big}
          {$form.$group.html}
        {/if}
        </div>
      </div>
      <div class="clear"></div>
    </div>
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
  {else}
    <div class="crm-tasks">
      {*top buttons*}
      {assign var=save value="_qf_"|cat:$form.formName|cat:"_submit_save"}
      {assign var=next value="_qf_"|cat:$form.formName|cat:"_submit_next"}
      <div class="crm-submit-buttons-top">
        {$form.buttons.html}&nbsp;&nbsp;&nbsp;&nbsp;
        {$form.$save.html}
        {if $mode neq 'template' && $form.$next}
          {$form.$next.html}
                        {/if}
      </div>
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
