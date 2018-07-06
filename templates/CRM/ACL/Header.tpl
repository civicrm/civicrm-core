{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{capture assign=docLink}{docURL page='user/initial-set-up/permissions-and-access-control/' text='Access Control Documentation'}{/capture}

<div class="help">
  <p>{ts 1=$docLink}ACLs allow you to control access to CiviCRM data. An ACL consists of an <strong>Operation</strong> (e.g. 'View' or 'Edit'), a <strong>set of data</strong> that the operation can be performed on (e.g. a group of contacts), and a <strong>Role</strong> that has permission to do this operation. Refer to the %1 for more info.{/ts}</p>
</div>

{php}
  $currentStep = $this->get_template_vars('step');
  $wizard = array(
    'style' => array(),
    'currentStepNumber' => $currentStep,
    'steps' => array(
      array(
        'title' => ts('Manage Roles'),
        'link' => CRM_Utils_System::url('civicrm/admin/options/acl_role', 'reset=1'),
      ),
      array(
        'title' => ts('Assign Users'),
        'link' => CRM_Utils_System::url('civicrm/acl/entityrole', 'reset=1'),
      ),
      array(
        'title' => ts('Manage ACLs'),
        'link' => CRM_Utils_System::url('civicrm/acl', 'reset=1'),
      ),
    ),
  );
  foreach ($wizard['steps'] as $num => &$step) {
    $step['step'] = $step['valid'] = $step['stepNumber'] = $num + 1;
    if ($step['stepNumber'] == $currentStep) {
      $step['link'] = NULL;
    }
  }
  $this->assign('wizard', $wizard);
{/php}

{include file="CRM/common/WizardHeader.tpl"}
