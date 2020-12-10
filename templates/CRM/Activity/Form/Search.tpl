{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Search form and results for Activities *}
<div class="crm-form-block crm-search-form-block">
  <div class="crm-accordion-wrapper crm-advanced_search_form-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Edit Search Criteria{/ts}
    </div>
    <!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div id="searchForm" class="form-item">
        {strip}
          <table class="form-layout">
            <tr>
              <td class="font-size12pt" colspan="2">
                {$form.sort_name.label}&nbsp;&nbsp;{$form.sort_name.html|crmAddClass:'twenty'}
                <div>
                  <div class="description font-italic">{ts}Complete OR Partial Name{/ts}
                    <span class="contact-name-option option-1">{ts} of the Source Contact{/ts}</span>
                    <span class="contact-name-option option-2">{ts} of the Assignee Contact{/ts}</span>
                    <span class="contact-name-option option-3">{ts} of the Target Contact{/ts}</span>
                  </div>
                </div>
              </td>
              <td>{include file="CRM/common/formButtons.tpl" location="top"}</td>
            </tr>

            {include file="CRM/Activity/Form/Search/Common.tpl"}

            <tr>
              <td colspan="3">{include file="CRM/common/formButtons.tpl" location="botton"}</td>
            </tr>
          </table>
        {/strip}
      </div>
    </div>
  </div>
</div>

{if $rowsEmpty || $rows }
  <div class="crm-content-block">
    {if $rowsEmpty}
      <div class="crm-results-block crm-results-block-empty">
        {include file="CRM/Activity/Form/Search/EmptyResults.tpl"}
      </div>
    {/if}

    {if $rows}
      <div class="crm-results-block">
        {* Search request has returned 1 or more matching rows. *}

        {* This section handles form elements for action task select and submit *}
        <div class="crm-search-tasks">
          {include file="CRM/common/searchResultTasks.tpl"}
        </div>
        {* This section displays the rows along and includes the paging controls *}
        <div class="crm-search-results">
          {include file="CRM/Activity/Form/Selector.tpl" context="Search"}
        </div>
        {* END Actions/Results section *}
      </div>
    {/if}
  </div>
{/if}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      var $form = $('form.{/literal}{$form.formClass}{literal}'),
        roleId = $('input[name=activity_role]:checked', $form).val();
      if (roleId) {
        $('.description .option-' + roleId).show();
      }

      $('[name=activity_role]:input').change(function() {
        $('.description .contact-name-option').hide();
        if ($(this).is(':checked')) {
          $('.description .option-' + $(this).val()).show();
        }
      }).change();
    });


  </script>
{/literal}
