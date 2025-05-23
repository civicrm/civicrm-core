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
<details class="crm-accordion-bold crm-tagGroup-accordion">
  <summary>{$title}</summary>
  <div class="crm-accordion-body" id="tagGroup">
{/if}
    <table class="form-layout-compressed{if $context EQ 'profile'} crm-profile-tagsandgroups{/if}">
      <tr>
        <td>
          {if $form.tag}
            <div class="crm-section tag-section">
              {if !empty($title)}{$form.tag.label}<br>{/if}
              {$form.tag.html}
            </div>
          {/if}
          {if $context NEQ 'profile'}
            {include file="CRM/common/Tagset.tpl"}
          {/if}
        </td>

        {if $form.group}
          <td>
            {if $groupElementType eq 'select'}
              <div class="crm-section group-section">
              {if $title}{$form.group.label}<br>{/if}
              {$form.group.html}
            </div>
            {else}
              {foreach key=key item=item from=$tagGroup.group}
                <div class="group-wrapper">
                  {$form.group.$key.html}
                    {if $item.description}
                    <div class="description">{$item.description}</div>
                  {/if}
                </div>
              {/foreach}
            {/if}
          </td>
        {/if}
      </tr>
    </table>
{if $title}
  </div>
</details>
{/if}
