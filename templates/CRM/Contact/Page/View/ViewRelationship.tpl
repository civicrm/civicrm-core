<div class="crm-block crm-content-block crm-relationship-view-block">
  <table class="crm-info-panel">
      {foreach from=$viewRelationship item="row"}
        <tr>
          <td class="label">{$row.relation}</td>
          <td><a class="no-popup" href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.cid`"}">{$row.name}</a></td>
        </tr>
          {if $isCurrentEmployer}
            <tr><td class="label">{ts}Current Employee?{/ts}</td><td>{ts}Yes{/ts}</td></tr>
          {/if}
          {if $row.start_date}
            <tr><td class="label">{ts}Start Date{/ts}</td><td>{$row.start_date|crmDate}</td></tr>
          {/if}
          {if $row.end_date}
            <tr><td class="label">{ts}End Date{/ts}</td><td>{$row.end_date|crmDate}</td></tr>
          {/if}
          {if $row.description}
            <tr><td class="label">{ts}Description{/ts}</td><td>{$row.description}</td></tr>
          {/if}
          {foreach from=$viewNote item="rec"}
              {if $rec}
                <tr><td class="label">{ts}Note{/ts}</td><td>{$rec}</td></tr>
              {/if}
          {/foreach}
        <tr>
          <td class="label"><label>{ts}Permissions{/ts}</label></td>
          <td>
              {if $row.is_permission_a_b or $row.is_permission_b_a}
                  {if $row.is_permission_a_b}
                    <div>
                        {if $row.rtype EQ 'a_b' AND $is_contact_id_a}
                            {include file="CRM/Contact/Page/View/RelationshipPerm.tpl" permType=$row.is_permission_a_b permDisplayName=$displayName otherDisplayName=$row.display_name displayText=true}
                        {else}
                            {include file="CRM/Contact/Page/View/RelationshipPerm.tpl" permType=$row.is_permission_a_b otherDisplayName=$displayName permDisplayName=$row.display_name displayText=true}
                        {/if}
                    </div>
                  {/if}
                  {if $row.is_permission_b_a}
                    <div>
                        {if $row.rtype EQ 'a_b' AND $is_contact_id_a}
                            {include file="CRM/Contact/Page/View/RelationshipPerm.tpl" permType=$row.is_permission_b_a otherDisplayName=$displayName permDisplayName=$row.display_name displayText=true}
                        {else}
                            {include file="CRM/Contact/Page/View/RelationshipPerm.tpl" permType=$row.is_permission_b_a permDisplayName=$displayName otherDisplayName=$row.display_name displayText=true}
                        {/if}
                    </div>
                  {/if}
              {else}
                  {ts}None{/ts}
              {/if}
          </td>
        </tr>
        <tr><td class="label">{ts}Status{/ts}</td><td>{if $row.is_active}{ts}Enabled{/ts}{else}{ts}Disabled{/ts}{/if}</td></tr>
        <tr><td class="label">{ts}Created Date{/ts}</td><td>{$row.created_date|crmDate}</td></tr>
        <tr><td class="label">{ts}Modified Date{/ts}</td><td>{$row.modified_date|crmDate}</td></tr>

      {/foreach}
  </table>
    {include file="CRM/Custom/Page/CustomDataView.tpl"}
</div>

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
