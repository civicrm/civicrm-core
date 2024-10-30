{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if empty($tagsetType)}
  {assign var="tagsetType" value="contact"}
{/if}
{if !empty($tableLayout)}
  <td colspan="2" class="crm-content-block">
    <table>
{/if}
   {if !empty($tagsetInfo)}
    {foreach from=$tagsetInfo.$tagsetType item=tagset}
      {assign var="elemName" value=$tagset.tagsetElementName}
      {if empty($tagsetElementName) or $tagsetElementName eq $elemName}
        {assign var="parID" value=$tagset.parentID}
        {assign var="skipEntityAction" value=$tagset.skipEntityAction}
        {if !empty($tableLayout)}
          <tr>
            <td class="label">
              {$form.$elemName.$parID.label}
            </td>
            <td class="{$tagsetType}-tagset {$tagsetType}-tagset-{$tagset.parentID}-section">
              {$form.$elemName.$parID.html}
            </td>
          </tr>
        {else}
          <div class="crm-section tag-section {$tagsetType}-tagset {$tagsetType}-tagset-{$tagset.parentID}-section">
            <div class="crm-clearfix">
              {$form.$elemName.$parID.label}
              {$form.$elemName.$parID.html}
            </div>
          </div>
        {/if}
      {/if}
    {/foreach}
    {/if}
{if !empty($tableLayout)}
    </table>
  </td>
{/if}

{if !empty($tagsetInfo) and empty($skipEntityAction) and empty($form.frozen)}
  <script type="text/javascript">
    {* Add/remove entity tags via ajax api *}
    {literal}
    (function($, _) {
      var $el = $('.{/literal}{$tagsetType}-tagset{literal} input.crm-form-entityref');
      // select2 provides "added" and "removed" properties in the event
      $el.on('change', function(e) {
        var tags,
          data = _.pick($(this).data(), 'entity_id', 'entity_table'),
          apiCall = [];
        if (e.added) {
          tags = $.isArray(e.added) ? e.added : [e.added];
          _.each(tags, function(tag) {
            if (tag.id && tag.id != '0') {
              apiCall.push(['entity_tag', 'create', $.extend({tag_id: tag.id}, data)]);
            }
          });
        }
        if (e.removed) {
          tags = $.isArray(e.removed) ? e.removed : [e.removed];
          _.each(tags, function(tag) {
            if (tag.id && tag.id != '0') {
              apiCall.push(['entity_tag', 'delete', $.extend({tag_id: tag.id}, data)]);
            }
          });
        }
        if (apiCall.length) {
          CRM.api3(apiCall, true);
        }
      });
    }(CRM.$, CRM._));
    {/literal}
  </script>
{/if}
