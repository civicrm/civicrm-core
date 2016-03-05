{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

{* template for a single row *}
{if $soInstance}
  <tr class="string-override-row {if $soInstance % 2}odd{else}even{/if}-row" data-row="{$soInstance}"
      xmlns="http://www.w3.org/1999/html">
    <td>{$form.enabled.$soInstance.html}</td>
    <td>{$form.old.$soInstance.html}</td>
    <td>{$form.new.$soInstance.html}</td>
    <td>{$form.cb.$soInstance.html}</td>
  </tr>

{else}
  {* this template is used for adding/editing string overrides  *}
  <div class="crm-form crm-form-block crm-string_override-form-block">
    <div class="help">
      {ts}Use <strong>Word Replacements</strong> to change all occurrences of a word or phrase in CiviCRM screens (e.g. replace all occurrences of 'Contribution' with 'Donation').{/ts} {help id="id-word_replace"}
    </div>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location='top'}
    </div>
    <table class="form-layout-compressed">
      <tr>
        <td>
          <table class="string-override-table row-highlight">
            <thead>
              <tr class="columnheader">
                <th>{ts}Enabled{/ts}</th>
                <th>{ts}Original{/ts}</th>
                <th>{ts}Replacement{/ts}</th>
                <th>{ts}Exact Match{/ts}</th>
              </tr>
            </thead>
            <tbody>
              {section name="numStrings" start=1 step=1 loop=$numStrings+1}
                {include file="CRM/Admin/Form/WordReplacements.tpl" soInstance=$smarty.section.numStrings.index}
              {/section}
            </tbody>
          </table>
          &nbsp;&nbsp;&nbsp;<a class="action-item crm-hover-button buildStringOverrideRow" href="#"><i class="crm-i fa-plus-circle"></i> {ts}Add row{/ts}</a>
        </td>
      </tr>
    </table>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location='bottom'}
    </div>

  </div>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      {/literal}
      {if $stringOverrideInstances}
        {* Rebuild necessary rows in case of form error *}
        {foreach from=$stringOverrideInstances key="index" item="instance"}
          buildStringOverrideRow( {$instance} );
        {/foreach}
      {/if}
      {literal}

      function buildStringOverrideRow( curInstance ) {
        var newRowNum;

        if (curInstance) {
          // Don't fetch if already present
          if ($('tr.string-override-row[data-row=' + curInstance + ']').length) {
            return;
          }
          newRowNum = curInstance;
        } else {
          newRowNum = 1 + $('tr.string-override-row:last').data('row');
        }

        var dataUrl = {/literal}"{crmURL q='snippet=4' h=0}"{literal};
        dataUrl += "&instance="+newRowNum;

        $.ajax({
          url: dataUrl,
          async: false,
          success: function(html) {
            $('.string-override-table tbody').append(html);
            $('tr.string-override-row:last').trigger('crmLoad');
          }
        });
      }

      $('.buildStringOverrideRow').click(function(e) {
        buildStringOverrideRow(false);
        e.preventDefault();
      });

      // Auto-check new items
      $('.string-override-table').on('keyup', 'textarea', function() {
        if (!$(this).data('crm-initial-value')) {
          var otherValue = $(this).closest('tr').find('textarea').not(this).val();
          if ($(this).val() && otherValue) {
            $(this).closest('tr').find('input[type=checkbox]').first().prop('checked', true);
          }
        }
      });

    });
  </script>
{/literal}
{/if}
