 <td>
    <span id="icon_{$targetRowID}_show" title="{ts}Show payments{/ts}">
      <a href="#" data-entity_id='{$rowEntityID}' data-base_url="{$baseUrl}"
         data-contact_id='{$row.contact_id}' data-entity='{$rowEntity}' data-target_row='{$targetRowID}'
         onclick="subDetails(this);
          showSubDetails(this);
          return false;">
        <img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}"/>
      </a>
    </span>
    <span id="icon_{$targetRowID}_hide" class="hiddenElement">
        <a data-entity_id='{$rowEntityID}' href="#"  data-contact_id='{$row.contact_id}' data-entity='{$rowEntity}' data-target_row='{$targetRowID}'
           href="#" onclick="hideSubDetails(this);
          return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}open section{/ts}"/>
        </a>
    </span>
  </td>

  {literal}
  <script type="text/javascript">
      function subDetails(element) {
        var entityId = cj(element).data('entity_id');
        var targetRow = cj(element).data('target_row');
        var rowElement = cj('#' + targetRow);
        var contactId = cj(element).data('contact_id');
        var entity = cj(element).data('entity');
        var baseURL = 'civicrm/' + cj(element).data('base_url');
        var dataUrl = CRM.url(baseURL, {
          'view': 'transaction',
          'component': entity,
          'action': 'browse',
          'cid': contactId,
          'id': entityId,
          'selector' : 1,
        });
        CRM.loadPage(dataUrl, {'target': rowElement});
      }

      function showSubDetails(element) {
        var targetRow = cj(element).data('target_row');
        cj('#' + targetRow + '_row').show();
        cj('#icon_' + targetRow + '_show').hide();
        cj('#icon_' + targetRow + '_hide').show();
      }

      function hideSubDetails(element) {
        var targetRow = cj(element).data('target_row');
        cj('#' + targetRow + '_row').hide();
        cj('#icon_' + targetRow + '_show').show();
        cj('#icon_' + targetRow + '_hide').hide();
      }

  </script>
{/literal}
