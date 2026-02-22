const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
  entry: {
    'claudechat-main':  './js/src/main.js',
    'claudechat-admin': './js/src/admin.js',
  },
  output: {
    path: path.resolve(__dirname, 'js'),
    filename: '[name].js',
    clean: false,
  },
  resolve: {
    alias: {
      vue$: 'vue/dist/vue.esm-bundler.js',
    },
    extensions: ['.js', '.vue'],
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader',
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/,
      },
    ],
  },
  plugins: [new VueLoaderPlugin()],
  mode: 'production',
}
