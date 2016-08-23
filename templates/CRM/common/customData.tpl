{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{literal}
<script type="text/javascript">
  (function($) {
    CRM.buildCustomData = function (type, subType, subName, cgCount, groupID, isMultiple) {
      var dataUrl = CRM.url('civicrm/custom', {type: type}),
        prevCount = 1,
        fname = '#customData',
        storage = {};

      if (subType) {
        dataUrl += '&subType=' + subType;
      }

      if (subName) {
        dataUrl += '&subName=' + subName;
        $('#customData' + subName).show();
      }
      else {
        $('#customData').show();
      }
      if (groupID) {
        dataUrl += '&groupID=' + groupID;
      }

      {/literal}
      {if $groupID}
        dataUrl += '&groupID=' + '{$groupID}';
      {/if}
      {if $entityID}
        dataUrl += '&entityID=' + '{$entityID}';
      {/if}
      {if $qfKey}
        dataUrl += '&qf=' + '{$qfKey}';
      {/if}
      {literal}

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
