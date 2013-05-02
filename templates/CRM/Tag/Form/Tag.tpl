{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* this template is used for adding/editing tags  *}
<style>
  .hit {ldelim}padding-left:10px;{rdelim}
  .tree li {ldelim}padding-left:10px;{rdelim}
  #Tag .tree .collapsable .hit {ldelim}background:url('{$config->resourceBase}i/menu-expanded.png') no-repeat left 8px;padding-left: 9px;cursor:pointer{rdelim}
  #Tag .tree .expandable .hit {ldelim}background:url('{$config->resourceBase}i/menu-collapsed.png') no-repeat left 6px;padding-left: 9px;cursor:pointer{rdelim}
  #Tag #tagtree .highlighted {ldelim}background-color:lightgrey;{rdelim}
</style>
<script type="text/javascript">
  (function(){ldelim}
    var entityID={$entityID};
    var entityTable='{$entityTable}';
    {literal}
    cj(function(){initTagTree()});

    function initTagTree() {
      //unobsctructive elements are there to provide the function to those not having javascript, no need for the others
      cj(".unobstructive").hide();

      cj("#tagtree ul input:checked").each (function(){
        cj(this).parents("li").children(".jstree-icon").addClass('highlighted');
      });

      cj("#tagtree input").change(function(){
        tagid = this.id.replace("check_", "");
        //get current tags from Summary and convert to array
        var tagLabels = cj.trim( cj("#tags").text( ) );
        if ( tagLabels ) {
          var tagsArray = tagLabels.split(',');
        }
        else{
          var tagsArray = new Array();
        }

        //get current tag label
        var currentTagLabel = cj("#tagLabel_" + tagid ).text( );
        if (this.checked) {
          CRM.api('entity_tag','create',{entity_table:entityTable,entity_id:entityID,tag_id:tagid});
          // add check to tab label array
          tagsArray.push( currentTagLabel );
        }
        else {
          CRM.api('entity_tag','delete',{entity_table:entityTable,entity_id:entityID,tag_id:tagid});
          // build array of tag labels
          tagsArray = cj.map(tagsArray, function (a) {
            if ( cj.trim( a ) != currentTagLabel ) {
              return cj.trim( a );
            }
          });
        }

        //showing count of tags in summary tab
        var existingTagsInTagset = cj('.token-input-delete-token-facebook').length;
        var tagCount = cj("#tagtree input:checkbox:checked").length + existingTagsInTagset;
        cj( '.ui-tabs-nav #tab_tag a' ).html( 'Tags <em>' + tagCount + '</em>');

        //update summary tab
        tagLabels = tagsArray.join(', ');
        cj("#tags").html( tagLabels );
      });

      //load js tree.
      cj("#tagtree").jstree({
        "plugins" : ["themes", "html_data"],
        "themes": {"url": CRM.config.resourceBase + 'packages/jquery/plugins/jstree/themes/default/style.css'}
      });

      {/literal}
      {if $permission neq 'edit'}
        {literal}
          cj("#tagtree input").attr('disabled', true);
        {/literal}
      {/if}
      {literal}
    };
  })();
  {/literal}
</script>
<div id="Tag" class="view-content">
  <h3>{if !$hideContext}{ts}Tags{/ts}{/if}</h3>
  <p>
  {if $action eq 16}
    {if $permission EQ 'edit'}
      {capture assign=crmURL}{crmURL p='civicrm/contact/view/tag' q='action=update'}{/capture}
      <span class="unobstructive">{ts 1=$displayName 2=$crmURL}Current tags for <strong>%1</strong> are highlighted. You can add or remove tags from <a href='%2'>Edit Tags</a>.{/ts}</span>
      {else}
      {ts}Current tags are highlighted.{/ts}
    {/if}
    {else}
    {if !$hideContext}
      {ts}Mark or unmark the checkboxes, <span class="unobstructive">and click 'Update Tags' to modify tags.<span>{/ts}
    {/if}
  {/if}
  </p>
  <div id="tagtree">
    <ul class="tree">
    {foreach from=$tree item="node" key="id"}
      <li id="tag_{$id}">
        {if ! $node.children}<input name="tagList[{$id}]" id="check_{$id}" type="checkbox" {if $tagged[$id]}checked="checked"{/if}/>{/if}
        {if $node.children}<input name="tagList[{$id}]" id="check_{$id}" type="checkbox" {if $tagged[$id]}checked="checked"{/if}/>{/if}
        {if $node.children} <span class="hit"></span> {/if} <label for="check_{$id}" id="tagLabel_{$id}">{$node.name}</label>
        {if $node.children}
          <ul>
            {foreach from=$node.children item="subnode" key="subid"}
              <li id="tag_{$subid}">
                <input id="check_{$subid}" name="tagList[{$subid}]" type="checkbox" {if $tagged[$subid]}checked="checked"{/if}/>
                {if $subnode.children} <span class="hit"></span> {/if} <label for="check_{$subid}" id="tagLabel_{$subid}">{$subnode.name}</label>
                {if $subnode.children}
                  <ul>
                    {foreach from=$subnode.children item="subsubnode" key="subsubid"}
                      <li id="tag_{$subsubid}">
                        <input id="check_{$subsubid}" name="tagList[{$subsubid}]" type="checkbox" {if $tagged[$subsubid]}checked="checked"{/if}/>
                        <label for="check_{$subsubid}" id="tagLabel_{$subsubid}">{$subsubnode.name}</label>
                      </li>
                    {/foreach}
                  </ul>
                {/if}
              </li>
            {/foreach}
          </ul>
        {/if}
      </li>
    {/foreach}
    </ul>
  </div>

{* Show Edit Tags link if in View mode *}
{if $permission EQ 'edit' AND $action eq 16}
  </fieldset>
  <div class="action-link unobstructive">
    <a accesskey="N" href="{crmURL p='civicrm/contact/view/tag' q='action=update'}" class="button"><span><div class="icon edit-icon"></div>{ts}Edit Tags{/ts}</span></a>
  </div>
  {else}
  <div class="form-item unobstructive">{$form.buttons.html}</div>
  </fieldset>
{/if}
  <br />
{include file="CRM/common/Tag.tpl" context="contactTab"}
</div>

{if $action eq 1 or $action eq 2 }
<script type="text/javascript">
  {* this function is called to change the color of selected row(s) *}
  on_load_init_check("{$form.formName}");
</script>
{/if}
