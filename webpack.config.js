const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve(__dirname, 'blocks/random-quote/index.js')
    },
    output: {
        path: path.resolve(__dirname, 'blocks/random-quote/build'),
        filename: '[name].js'
    },
    plugins: defaultConfig.plugins.filter(
        plugin => plugin.constructor.name !== 'CleanWebpackPlugin'
    )
};