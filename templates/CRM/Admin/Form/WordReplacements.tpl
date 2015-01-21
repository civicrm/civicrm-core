{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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

{* template for a single row *}
{if $soInstance}
  <tr class="string-override-row row-{$soInstance} {if $soInstance % 2}odd{else}even{/if}-row">
    <td>{$form.enabled.$soInstance.html}</td>
    <td>{$form.old.$soInstance.html}</td>
    <td>{$form.new.$soInstance.html}</td>
    <td>{$form.cb.$soInstance.html}</td>
  </tr>

{else}
  {* this template is used for adding/editing string overrides  *}
  <div class="crm-form crm-form-block crm-string_override-form-block">
    <div id="help">
      {ts}Use <strong>Word Replacements</strong> to change all occurrences of a word or phrase in CiviCRM screens (e.g. replace all occurences of 'Contribution' with 'Donation').{/ts} {help id="id-word_replace"}
    </div>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location='top'}
    </div>
    <table class="form-layout-compressed">
      <tr>
        <td>
          <table>
            <tr class="columnheader">
              <td>{ts}Enabled{/ts}</td>
              <td>{ts}Original{/ts}</td>
              <td>{ts}Replacement{/ts}</td>
              <td>{ts}Exact Match{/ts}</td>
            </tr>

            {section name="numStrings" start=1 step=1 loop=$numStrings+1}
              {include file="CRM/Admin/Form/WordReplacements.tpl" soInstance=$smarty.section.numStrings.index}
            {/section}
          </table>
          &nbsp;&nbsp;&nbsp;<a class="action-item crm-hover-button buildStringOverrideRow" href="#"><span class="icon ui-icon-circle-plus"></span> {ts}Add row{/ts}</a>
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
      {foreach from=$stringOverrideInstances key="index" item="instance"}
      buildStringOverrideRow( {$instance} );
      {/foreach}
      {/if}
      {literal}

      function buildStringOverrideRow( curInstance ) {
        var rowId = 'string_override_row_';

        if (curInstance) {
          if (curInstance <= 10) return;
          currentInstance = curInstance;
          previousInstance = currentInstance - 1;
        } else {
          var previousInstance = $('[id^="'+ rowId +'"]:last').attr('id').slice(rowId.length);
          var currentInstance = parseInt(previousInstance) + 1;
        }

        var dataUrl = {/literal}"{crmURL q='snippet=4' h=0}"{literal};
        dataUrl += "&instance="+currentInstance;

        var prevInstRowId = '#string_override_row_' + previousInstance;

        $.ajax({
          url: dataUrl,
          async: false,
          success: function(html) {
            $(prevInstRowId).after(html);
            $('#old_'+currentInstance).TextAreaResizer();
            $('#new_'+currentInstance).TextAreaResizer();
          }
        });
      }

      $('.buildStringOverrideRow').click(function(e) {
        buildStringOverrideRow(false);
        e.preventDefault();
      });
    });
  </script>
{/literal}
{/if}
