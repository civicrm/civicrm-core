// @todo configure grunt default stuff to run on every save so we know that 
// dist is always up to date and jsLinted

module.exports = function(grunt) {

    require('load-grunt-tasks')(grunt, {
        scope: ['dependencies', 'devDependencies']
    });

    grunt.initConfig({
        // end 2 end testing with protractor
        protractor: {
            options: {
                keepAlive: false,
                configFile: './protractor.conf.js'
            },
            singlerun: {},
            travis: {
                configFile: './protractor_travis.conf.js'
            },
            auto: {
                keepAlive: true,
                options: {
                    args: {
                        seleniumPort: 4444
                    }
                }
            }
        },
        connect: {
            server: {
                options: {
                    port: 9001,
                    open: 'http://localhost:9001/demo',
                    keepalive: true
                }
            },
            // our protractor server
            testserver: {
                options: {
                    port: 9999
                }
            },
            travisServer: {
                options: {
                    port: 9999
                }
            },
        },
        // watch tasks
        // Watch specified files for changes and execute tasks on change
        watch: {
            livereload: {
                options: {
                    livereload: true
                },
                files: [
                    'src/*.js',
                    'demo/*.js'
                ],
                tasks: ['jshint']
            },
        },
        karma: {
            plugins: [
                'karma-osx-reporter'
            ],
            unit: {
                configFile: 'karma-unit.conf.js',
                autoWatch: false,
                singleRun: true
            },
            unitAuto: {
                configFile: 'karma-unit.conf.js',
                autoWatch: true,
                singleRun: false
            }
        },
        'min': {
            'dist': {
                'src': ['dist/unsavedChanges.js'],
                'dest': 'dist/unsavedChanges.min.js'
            }
        },
        jshint: {
            all: ['src/*.js']
        },
        strip: {
            main: {
                src: 'src/unsavedChanges.js',
                dest: 'dist/unsavedChanges.js'
            }
        }

    });

    grunt.registerTask('test', [
        'test:unit'
    ]);

    grunt.registerTask('server', [
        'connect:server'
    ]);

    grunt.registerTask('test:unit', [
        'karma:unit'
    ]);

    grunt.registerTask('autotest', [
        'autotest:unit'
    ]);

    grunt.registerTask('autotest:unit', [
        'karma:unitAuto'
    ]);

    grunt.registerTask('default', [
        'jshint',
        'strip:main',
        'min'
    ]);

    grunt.registerTask('autotest:e2e', [
        'connect:testserver', // - starts the app so the test runner can visit the app
        'shell:selenium', // - starts selenium server in watch mode
        'watch:protractor' // - watches scripts and e2e specs, and starts tests on file change
    ]);

    grunt.registerTask('test:e2e', [
        'connect:testserver', // - run concurrent tests
        'protractor:singlerun' // - single run protractor
    ]);

    grunt.registerTask('test:travis', [
        'connect:travisServer', // - run concurrent tests
        'karma:unit' // - single run karma unit
    ]);

};