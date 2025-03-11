var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public')
    .setPublicPath('/bundles/lokiai')
    .setManifestKeyPrefix('lokiai')
    .addEntry('backend', './assets/js/backend.js')


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
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // enables @babel/preset-env polyfills
    /*.configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })*/
    .configureBabel(function(babelConfig) {
        babelConfig.plugins.push('@babel/plugin-transform-runtime');
    }, {})
;

module.exports = Encore.getWebpackConfig();