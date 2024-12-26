{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

  <div id="report-tab-col-groups" class="civireport-criteria">
    {foreach from=$colGroups item=grpFields key=dnc}
      {assign  var="count" value=0}
      {* Wrap custom field sets in collapsed accordion pane. *}
      {if !empty($grpFields.use_accordian_for_field_selection)}
        <details class="crm-accordion-bold crm-accordion">
        <summary>
          {$grpFields.group_title}
        </summary>
        <div class="crm-accordion-body">
      {/if}
      <table class="criteria-group">
        <tr class="crm-report crm-report-criteria-field crm-report-criteria-field-{$dnc}">
          {foreach from=$grpFields.fields item=title key=field}
          {assign var="count" value=$count+1}
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
      {if !empty($grpFields.use_accordian_for_field_selection)}
        </div>
        </details>
      {/if}
    {/foreach}
  </div>
