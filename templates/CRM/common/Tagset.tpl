{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{if empty($tagsetType)}
  {assign var="tagsetType" value="contact"}
{/if}
{foreach from=$tagsetInfo.$tagsetType item=tagset}
  {assign var="elemName" value=$tagset.tagsetElementName}
  {if empty($tagsetElementName) or $tagsetElementName eq $elemName}
    {assign var="parID" value=$tagset.parentID}
    {assign var="skipEntityAction" value=$tagset.skipEntityAction}
    {if $tableLayout}
      <td class="label">
        {$form.$elemName.$parID.label}
      </td>
      <td class="{$tagsetType}-tagset {$tagsetType}-tagset-{$tagset.parentID}-section">
          {$form.$elemName.$parID.html}
      </td>
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

{if !$skipEntityAction and empty($form.frozen)}
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
