{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{* handle common enable/disable actions *}
{literal}
<script type="text/javascript">
  cj(function($) {
    var $row, entity, id, enabled, snippet;

    function refresh() {
      snippet.crmSnippet('refresh');
      CRM.alert('', enabled ? {/literal}'{ts escape="js"}Record Disabled{/ts}' : '{ts escape="js"}Record Enabled{/ts}'{literal}, 'success');
    }

    function save() {
      snippet = $row.closest('.crm-ajax-container');
      if (snippet.length && snippet.crmSnippet('option', 'block')) {
        snippet.block();
      }
      CRM.api(entity, 'create', {id: id, is_active: enabled ? 0 : 1}, {success: refresh});
    }

    function confirmation() {
      var dialog = $(this);
      $.getJSON(CRM.url('civicrm/ajax/statusmsg', {entity: entity, id: id}), function(response) {
        dialog.html(response.content);
      });
    }

    function enableDisable() {
      var $a = $(this);
      $row = $a.closest('.crm-entity');
      // FIXME: abstract and reuse code from $.crmEditable for fetching entity/id instead of reinventing it here
      entity = $row.data('entity');
      id = $row.data('id');
      if (!entity || !id) {
        entity = $row[0].id.split('-')[0];
        id = $row[0].id.split('-')[1];
      }
      enabled = !$row.hasClass('disabled');
      if (enabled) {
        CRM.confirm(save, {{/literal}
          title: '{ts escape="js"}Disable Record{/ts}',
          message: '<div class="crm-loading-element">{ts escape="js"}Loading{/ts}...</div>',
          width: 300,
          open: confirmation
        {literal}});
      } else {
        save();
      }
      return false;
    }

    // Because this is an inline script it may get added to the document more than once, so remove handler before adding
    $('body')
      .off('click', '.action-item.crm-enable-disable')
      .on('click', '.action-item.crm-enable-disable', enableDisable);
  });
</script>
{/literal}
