{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $title}
<div class="crm-accordion-wrapper crm-tagGroup-accordion collapsed">
  <div class="crm-accordion-header">{$title}</div>
  <div class="crm-accordion-body" id="tagGroup">
{/if}
    <table class="form-layout-compressed{if isset($context) && $context EQ 'profile'} crm-profile-tagsandgroups{/if}">
      <tr>
        {if empty($type) || $type eq 'tag'}
          <td>
            <div class="crm-section tag-section">
              {if !empty($title)}{$form.tag.label}<br>{/if}
              {$form.tag.html}
            </div>
            {if !isset($context) || $context NEQ 'profile'}
              {include file="CRM/common/Tagset.tpl"}
            {/if}
          </td>
        {/if}
        {if empty($type) || $type eq 'group'}
          <td>
            {if isset($groupElementType) && $groupElementType eq 'select'}
              <div class="crm-section group-section">
              {if !empty($title)}{$form.group.label}<br>{/if}
              {$form.group.html}
            </div>
            {else}
              {if isset($form.group)} 
                {foreach key=key item=item from=$tagGroup.group}
                  <div class="group-wrapper">
                    {$form.group.$key.html}
                      {if $item.description}
                      <div class="description">{$item.description}</div>
                    {/if}
                  </div>
                {/foreach}
              {/if}
            {/if}
          </td>
        {/if}
      </tr>
    </table>
{if $title}
  </div>
</div><!-- /.crm-accordion-wrapper -->
{/if}
