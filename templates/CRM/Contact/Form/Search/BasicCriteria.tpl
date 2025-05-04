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
{if $context EQ 'smog'}
    {capture assign=editTitle}{ts}Find Contacts within this Group{/ts}{/capture}
{elseif $context EQ 'amtg' AND !$rows}
    {capture assign=editTitle}{ts}Find Contacts to Add to this Group{/ts}{/capture}
{else}
    {capture assign=editTitle}{ts}Edit Search Criteria{/ts}{/capture}
{/if}

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
            {if $context EQ 'smog'}
              {$form.group_contact_status.label}
            {else}
              {$form.group.label}
            {/if}
          </div>
          <div class="content">
            {if $context EQ 'smog'}
              {$form.group_contact_status.html}
            {else}
              {$form.group.html|crmAddClass:big}
            {/if}
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
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div>
    </details>
</div><!-- /.crm-form-block -->
{/strip}
