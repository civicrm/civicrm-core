var cv = require('civicrm-cv')({mode: 'sync'});
var _CV = cv('vars:show');
const buildCrmAngular =
  'define("CIVICRM_KARMA", 1);' +
  'CRM_Core_BAO_ConfigSetting::enableAllComponents();' +
  '$a=Civi::service("angular");' +
  '$data = "var CRM = CRM || {}; CRM.angular =";' +
  '$data .= json_encode(array(' +
  '   "modules" => array_keys($a->getModules()),' +
  '   "requires" => $a->getResources(array_keys($a->getModules()), "requires","requires"),' +
  '));' +
  '$data .= ";";' +
  'global $civicrm_root;' +
  '$f = CRM_Utils_File::addTrailingSlash($civicrm_root)."tmp/crm.angular.js";' +
  'mkdir(dirname($f), 0777, TRUE);' +
  'file_put_contents($f, $data);' +
  'return $f;';
const crmAngularTmp = cv(['php:eval', '-U', _CV.ADMIN_USER, buildCrmAngular]);

const buildCrmVisual =
  '$data = Civi::service("asset_builder")->render("visual-bundle.js")["content"];' +
  'global $civicrm_root;' +
  '$f = CRM_Utils_File::addTrailingSlash($civicrm_root)."tmp/crm.visual.js";' +
  'mkdir(dirname($f), 0777, TRUE);' +
  'file_put_contents($f, $data);' +
  'return $f;';
const crmVisualTmp = cv(['php:eval', '-U', _CV.ADMIN_USER, buildCrmVisual]);

module.exports = function(config) {
  config.set({
    autoWatch: true,
    browsers: ['ChromeHeadless'],
    exclude: [
      'ang/api4Explorer/Explorer.js',
      'ext/civi_mail/ang/crmMailingAB/Stats.js'
    ],
    files: [
      'bower_components/jquery/dist/jquery.min.js',
      'bower_components/jquery-ui/jquery-ui.min.js',
      'bower_components/lodash-compat/lodash.min.js',
      'bower_components/select2/select2.min.js',
      'packages/jquery/plugins/jquery.blockUI.js',
      'bower_components/jquery-validation/dist/jquery.validate.min.js',
      'packages/jquery/plugins/jquery.timeentry.js',
      'js/Common.js',
      'js/crm.datepicker.js',
      'bower_components/angular/angular.js',
      crmAngularTmp,
      crmVisualTmp,
      'bower_components/angular-file-upload/dist/angular-file-upload.js',
      'bower_components/angular-jquery-dialog-service/dialog-service.js',
      'bower_components/angular-route/angular-route.js',
      'bower_components/angular-mocks/angular-mocks.js',
      'bower_components/angular-ui-sortable/sortable.js',
      'bower_components/angular-unsavedChanges/dist/unsavedChanges.js',
      'js/crm.ajax.js',
      'ang/*.js',
      'ang/**/*.js',
      'tests/karma/lib/*.js',
      'tests/karma/**/*.js',
      'ang/**/*.html',
      'ext/civi_mail/ang/*.js',
      'ext/civi_mail/ang/**/*.js',
      'ext/civi_mail/ang/**/*.html',
      'ext/civi_case/ang/*.js',
      'ext/civi_case/ang/**/*.js',
      'ext/civi_case/ang/**/*.html'
    ],
    preprocessors : {
      'ang/**/*.html': ['ng-html2js'],
      'ext/*/ang/**/*.html': ['ng-html2js'],
    },

    ngHtml2JsPreprocessor: {
      cacheIdFromPath: function(filepath) {
        return filepath.replace(/.*ang\//, '~/');
      },
      moduleName: 'crmResource'
    },
    frameworks: ['jasmine'],
    logLevel: config.LOG_INFO,
    port: 9876,
    reporters: ['progress'],
    junitReporter: {
      useBrowserName: false,
      outputFile: 'tests/output/karma.xml',
      suite: ''
    },
    singleRun: false
  });
};
