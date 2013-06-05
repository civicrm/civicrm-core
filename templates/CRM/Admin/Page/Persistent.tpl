{foreach from=$rows item=template_row key=type}
{if $type eq 'configTemplates'}
    <table class="report-layout">
      <div class="action-link">
        <a href="{crmURL p='civicrm/admin/tplstrings/add' q='reset=1&action=add'}"  class="button">
          <span>
             <div class="icon add-icon"></div>{ts}Add New String{/ts}
          </span>
        </a>
      </div>
      {if !empty( $template_row) }
      <tr>
         <th>Context</th>
         <th>Name</th>
         <th>Data</th>
         {if $editClass}
           <th style="width: 10%"></th>
         {/if}
      </tr>
   {foreach from=$template_row item=values}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{$values.context}</td>
        <td>{$values.name}</td>
        <td>{$values.data}</td>
        {if $editClass}
          <td>{$values.action}</td>
        {/if}
      </tr>
    {/foreach}
    {/if}
   </table>
{/if}
{if $type eq 'customizeTemplates'}
   <table class="report-layout">
      <head>
        <h1>Config String</h1>
      </head>
      <br/>
      <div class="action-link">
         <a href="{crmURL p='civicrm/admin/tplstrings/add' q='reset=1&action=add&config=1'}"  class="button">
           <span>
              <div class="icon add-icon"></div>{ts}Add New Config{/ts}
           </span>
         </a>
      </div>
      <br/>
      {if !empty( $template_row) }
      <tr>
        <th>Context</th>
        <th>Name</th>
        <th>Data</th>
        {if $editClass}
          <th style="width: 10%"></th>
        {/if}
      </tr>
  {foreach from=$template_row item=values}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{$values.context}</td>
        <td>{$values.name}</td>
        <td>{$values.data}</td>
        {if $editClass}
          <td>{$values.action}</td>
        {/if}
      </tr>
  {/foreach}
  {/if}
  </table>
{/if}
{/foreach}
