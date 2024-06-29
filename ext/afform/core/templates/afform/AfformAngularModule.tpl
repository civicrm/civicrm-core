{* This takes an $afform and generates an AngularJS module.

 @param string $afform.camel     The full camel-case name of the AngularJS module being created
 @param array  $afform.meta      Relevant form metadata
 @param string $afform.layout    The template content (HTML)
 *}
{literal}
(function(angular, $, _) {
  angular.module('{/literal}{$afform.camel}{literal}', CRM.angRequires('{/literal}{$afform.camel}{literal}'));
  angular.module('{/literal}{$afform.camel}{literal}').directive('{/literal}{$afform.camel}{literal}', function(afCoreDirective) {
    return afCoreDirective({/literal}{$afform.camel|json nofilter}, {$afform.meta|@json_encode nofilter}{literal}, {
      templateUrl: {/literal}{$afform.templateUrl|json nofilter}{literal}
    });
  });
})(angular, CRM.$, CRM._);
{/literal}
