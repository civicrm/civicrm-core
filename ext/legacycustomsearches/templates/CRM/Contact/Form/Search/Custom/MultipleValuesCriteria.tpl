{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Search criteria form elements - Find Contacts *}

{* Set title for search criteria accordion *}
{capture assign=editTitle}{ts}Edit Search Criteria{/ts}{/capture}

{strip}
<div class="crm-block crm-form-block crm-basic-criteria-form-block">
    <details class="crm-accordion-light crm-case_search-accordion" {if $rows}{else}open{/if}>
     <summary>
        {$editTitle}
    </summary>
    <div class="crm-accordion-body">
        <div class="crm-section sort_name-section">
          <div class="label">
            {$form.sort_name.label}
          </div>
          <div class="content">
            {$form.sort_name.html}
          </div>
          <div class="clear"></div>
        </div>

        {if !empty($form.contact_type)}
          <div class="crm-section contact_type-section">
            <div class="label">
              {$form.contact_type.label}
            </div>
              <div class="content">
                {$form.contact_type.html}
              </div>
              <div class="clear"></div>
          </div>
        {/if}

        {if !empty($form.group)}
        <div class="crm-section group_selection-section">
          <div class="label">
              {$form.group.label}
                </div>
          <div class="content">
                    {$form.group.html|crmAddClass:big}
                </div>
          <div class="clear"></div>
        </div>
        {/if}

        {if !empty($form.tag)}
            <div class="crm-section tag-section">
              <div class="label">
                {$form.tag.label}
              </div>
              <div class="content">
                {$form.tag.html|crmAddClass:medium}
              </div>
              <div class="clear"></div>
            </div>
        {/if}

          {* Choose regular or 'tall' listing-box class for Group select box based on # of groups. *}
            {if $form.custom_group|@count GT 8}
                {assign var="boxClass" value="listing-box-tall"}
            {else}
                {assign var="boxClass" value="listing-box"}
            {/if}
            <div class="crm-section crm-contact-custom-search-multipleValues-form-block-custom_group">
                <div class="label">
                  {ts}Custom Group(s){/ts}
                </div>
                <div class="content">
                    <div class="{$boxClass}">
                        {foreach from=$form.custom_group item="group_val"}
                            <div class="{cycle values="even-row,odd-row"}">
                                {$group_val.html}
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>

        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
    </div>
    </details>
</div><!-- /.crm-form-block -->
{/strip}
