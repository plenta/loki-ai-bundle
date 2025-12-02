var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public')
    .setPublicPath('/bundles/lokiai')
    .setManifestKeyPrefix('lokiai')
    .addEntry('backend', './assets/js/backend.js')
    .addStyleEntry('button', './assets/scss/button.scss')


    //.splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    //.enableSingleRuntimeChunk()
    .disableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    //.enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableSassLoader()
    .configureCssLoader((options) => {
        options.url = {
            filter: (url) => {
                return !url.startsWith('/system/themes/');
            },
        };
    })
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())


    .enableStimulusBridge('./assets/js/controllers.json')

    // enables @babel/preset-env polyfills
    /*.configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })*/
;

module.exports = Encore.getWebpackConfig();