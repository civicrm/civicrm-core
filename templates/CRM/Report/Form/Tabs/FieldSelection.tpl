{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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

  <div id="report-tab-col-groups" class="civireport-criteria">
    {foreach from=$colGroups item=grpFields key=dnc}
      {assign  var="count" value="0"}
      {* Wrap custom field sets in collapsed accordion pane. *}
      {if $grpFields.use_accordian_for_field_selection}
        <div class="crm-accordion-wrapper crm-accordion collapsed">
        <div class="crm-accordion-header">
          {$grpFields.group_title}
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">
      {/if}
      <table class="criteria-group">
        <tr class="crm-report crm-report-criteria-field crm-report-criteria-field-{$dnc}">
          {foreach from=$grpFields.fields item=title key=field}
          {assign var="count" value=`$count+1`}
          <td width="25%">{$form.fields.$field.html}</td>
          {if $count is div by 4}
        </tr><tr class="crm-report crm-report-criteria-field crm-report-criteria-field_{$dnc}">
          {/if}
          {/foreach}
          {if $count is not div by 4}
            <td colspan="4 - ($count % 4)"></td>
          {/if}
        </tr>
      </table>
      {if $grpFields.use_accordian_for_field_selection}
        </div><!-- /.crm-accordion-body -->
        </div><!-- /.crm-accordion-wrapper -->
      {/if}
    {/foreach}
  </div>
