{* This takes an $afform and generates an AngularJS module.

 @param string $afform.camel     The full camel-case name of the AngularJS module being created
 @param array  $afform.meta      The full metadata record of the form
 @param string $afform.metaJson  The full metadata record of the form
 @param string $afform.layout    The template content (HTML)
 *}
{literal}
(function(angular, $, _) {
  angular.module('{/literal}{$afform.camel}{literal}', CRM.angRequires('{/literal}{$afform.camel}{literal}'));
  angular.module('{/literal}{$afform.camel}{literal}').directive('{/literal}{$afform.camel}{literal}', function(afformCoreDirective) {
    return afformCoreDirective({/literal}{$afform.camel|json},{$afform.metaJson}{literal}, {
      template: {/literal}{$afform.layout|json}{literal}
    });
  });
})(angular, CRM.$, CRM._);
{/literal}