const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'blocks/random-quote/index': path.resolve(__dirname, 'blocks/random-quote/index.js'),
    },
    output: {
        path: path.resolve(__dirname),
        filename: '[name].js',
    },
    // غیرفعال کردن CleanWebpackPlugin برای جلوگیری از پاک شدن فایل‌ها
    plugins: defaultConfig.plugins.filter(plugin => 
        plugin.constructor.name !== 'CleanWebpackPlugin'
    ),
};