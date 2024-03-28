{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
<script type="text/javascript">
  (function($) {
    CRM.buildCustomData = function (type, subType, subName, cgCount, groupID, isMultiple, onlySubtype, cid) {
      var dataUrl = CRM.url('civicrm/custom', {type: type}),
        prevCount = 1,
        fname = '#customData_' + type,
        storage = {};

      if (subType) {
        dataUrl += '&subType=' + subType;
      }

      if (onlySubtype) {
        dataUrl += '&onlySubtype=' + onlySubtype;
      }

      if (subName) {
        dataUrl += '&subName=' + subName;
        $('#customData_' + type + subName).show();
      }
      else {
        $('#customData_' + type).show();
      }
      if (groupID) {
        dataUrl += '&groupID=' + groupID;
      }

      {/literal}
      {if !empty($groupID)}
        dataUrl += '&groupID=' + '{$groupID}';
      {/if}
      {if !empty($entityID)}
        dataUrl += '&entityID=' + '{$entityID}';
      {/if}
      {if !empty($qfKey)}
        dataUrl += '&qf=' + '{$qfKey}';
      {/if}
      {if !empty($action)}
        dataUrl += '&action=' + '{$action}';
      {/if}
      {literal}
      if (cid) {
        dataUrl += '&cid=' + cid;
      }

      if (!cgCount) {
        cgCount = 1;
      }
      else if (cgCount >= 1) {
        prevCount = cgCount;
        cgCount++;
      }

      dataUrl += '&cgcount=' + cgCount;


      if (isMultiple) {
        fname = '#custom_group_' + groupID + '_' + prevCount;
        if ($(".add-more-link-" + groupID + "-" + prevCount).length) {
          $(".add-more-link-" + groupID + "-" + prevCount).hide();
        }
        else {
          $("#add-more-link-" + prevCount).hide();
        }
      }
      else if (subName && subName != 'null') {
        fname += subName;
      }

      return CRM.loadPage(dataUrl, {target: fname});
    };
  })(CRM.$);
</script>
{/literal}
