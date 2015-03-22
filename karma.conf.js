module.exports = function(config) {
  config.set({
    autoWatch: true,
    browsers: ['PhantomJS'],
    exclude: [
    ],
    files: [
      'bower_components/jquery/dist/jquery.min.js',
      'bower_components/jquery-ui/jquery-ui.min.js',
      'bower_components/lodash-compat/lodash.min.js',
      'bower_components/select2/select2.min.js',
      'packages/jquery/plugins/jquery.blockUI.js',
      'packages/jquery/plugins/jquery.validate.js',
      'packages/jquery/plugins/jquery.timeentry.js',
      'js/Common.js',
      'bower_components/angular/angular.js',
      'bower_components/angular-file-upload/angular-file-upload.js',
      'bower_components/angular-jquery-dialog-service/dialog-service.js',
      'bower_components/angular-route/angular-route.js',
      'bower_components/angular-mocks/angular-mocks.js',
      'bower_components/angular-ui-sortable/sortable.js',
      'bower_components/angular-ui-utils/ui-utils.js',
      'bower_components/angular-unsavedChanges/dist/unsavedChanges.js',
      'tests/karma/modules.js',
      'js/crm.ajax.js',
      'js/angular-*.js',
      'js/angular-crmMailing/*.js',
      'tests/karma/lib/*.js',
      'tests/karma/**/*.js',
      'partials/**/*.html'
    ],
    preprocessors : {
      'partials/**/*.html' : ['ng-html2js']
    },

    ngHtml2JsPreprocessor: {
      stripPrefix: 'partials/',
      prependPrefix: '~/',
      moduleName: 'crmResource'
    },
    frameworks: ['jasmine'],
    logLevel: config.LOG_INFO,
    port: 9876,
    reporters: ['progress'],
    junitReporter: {
      outputFile: 'tests/output/karma.xml',
      suite: ''
    },
    singleRun: false
  });
};
