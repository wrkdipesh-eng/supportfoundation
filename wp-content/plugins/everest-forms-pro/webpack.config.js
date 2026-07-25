/**
 * Webpack config: Gutenberg block editor scripts (React).
 */
const path = require( 'path' );

module.exports = {
	entry: './src/blocks/payment-subscriptions/index.jsx',
	output: {
		path: path.resolve( __dirname, 'assets/js/blocks' ),
		filename: 'payment-subscriptions-block.js',
	},
	externals: {
		'@wordpress/blocks': [ 'wp', 'blocks' ],
		'@wordpress/block-editor': [ 'wp', 'blockEditor' ],
		'@wordpress/components': [ 'wp', 'components' ],
		'@wordpress/i18n': [ 'wp', 'i18n' ],
		'@wordpress/element': [ 'wp', 'element' ],
		'@wordpress/server-side-render': [ 'wp', 'serverSideRender' ],
	},
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							'@babel/preset-env',
							[
								'@babel/preset-react',
								{
									pragma: 'createElement',
									pragmaFrag: 'Fragment',
								},
							],
						],
					},
				},
			},
		],
	},
	resolve: {
		extensions: [ '.js', '.jsx' ],
	},
	performance: {
		hints: false,
	},
};
