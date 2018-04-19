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
