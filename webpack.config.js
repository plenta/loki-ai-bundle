var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('src/Brkwsky/ContaoPropstack/Resources/public')
    .setPublicPath('/bundles/contaopropstack')
    .setManifestKeyPrefix('contaopropstack')

    .addEntry('autoComplete', './assets/js/autoComplete.js')
    .addStyleEntry('autoCompleteStyles', './assets/scss/autoComplete.scss')
    .addStyleEntry('backend', './assets/scss/backend.scss')

    .addEntry('leaflet', './assets/js/leaflet.js')
    .addStyleEntry('leafletStyles', './assets/scss/leaflet.scss')

    .addStyleEntry('energyStyles', './assets/scss/energy.scss')

    .addEntry('app', './assets/js/app.js')
    .addEntry('embed', './assets/js/embed.js')


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