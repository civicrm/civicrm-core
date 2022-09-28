{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-case-filter-{$list}">
  <div class="crm-accordion-wrapper crm-search_filters-accordion">
    <div class="crm-accordion-header">
    {ts}Filter by Case{/ts}</a>
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <table class="no-border form-layout-compressed case-search-options-{$list}">
        <tr>
          <td class="crm-contact-form-block-case_type_id crm-inline-edit-field">
            {$form.case_type_id.label}<br /> {$form.case_type_id.html}
          </td>
          <td class="crm-contact-form-block-case_status_id crm-inline-edit-field">
            {$form.case_status_id.label}<br /> {$form.case_status_id.html}
          </td>
          {if $accessAllCases && $form.upcoming}
            <td class="crm-case-dashboard-switch-view-buttons">
              <br/>
              {$form.upcoming.html}&nbsp;{$form.upcoming.label}
            </td>
          {/if}
        </tr>
      </table>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
</div>
<div class="spacer"></div>
