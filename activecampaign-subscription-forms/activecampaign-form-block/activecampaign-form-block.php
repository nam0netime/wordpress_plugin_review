<?php

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function activecampaign_form_block_init() {
	if(!function_exists('register_block_type')){
		// Allow graceful degradation for pre-5.0 installs with no block editor
		return;
	}

	$dir = dirname( __FILE__ );

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "activecampaign-form/activecampaign-form-block" block first.'
		);
	}
	$index_js     = 'build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'activecampaign-form-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);

	$editor_css = 'build/index.css';
	wp_register_style(
		'activecampaign-form-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'build/style-index.css';
	wp_register_style(
		'activecampaign-form-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	$all_settings = get_option("settings_activecampaign");

	$settings = [ 'forms' => [], 'connected' => true ];
	// Best to do this formation in one spot
	if(!empty($all_settings) && !empty($all_settings['forms'])){
		foreach($all_settings['forms'] as $form){
			$script_src = activecampaign_form_script_src($all_settings, $form, false, null, true);
			if(isset($script_src)){
				$settings['forms'][$form['id']] = [
					'script_container_id' => '_form_'.$form['id'],
					'script_src' => $script_src,
					'name' => $form['name'],
					'id' => $form['id'],
					'version' => $form['version'],
				];
			}
		}
	}
	elseif(empty($all_settings) || empty($all_settings['api_url']) || empty($all_settings['api_key'])){
		$settings['connected'] = false;
	}

	$block_attributes = [
		'className'             => [
			'type'    => 'string',
			'default' => ''
		],
		'formId'             => [
			'type'    => 'string',
			'default' => '0'
		],
		'useCss'             => [
			'type'    => 'string',
			'default' => 'global'
		],
		'settings_activecampaign'       => [
			'type' => 'object',
			'default' => $settings
		],
		// Indicates dynamic block update
		'hasRenderCallback'	=> [
			'type' => 'boolean',
			'default' => false
		],
		'migrationVersion' => [
			'type' => 'integer',
			'default' => 0
		]
	];

	register_block_type( 'activecampaign-form/activecampaign-form-block', [
		'editor_script' => 'activecampaign-form-block-editor',
		'editor_style'  => 'activecampaign-form-block-editor',
		'style'         => 'activecampaign-form-block',
		'attributes'    => $block_attributes,
		'render_callback' => 'activecampaign_form_block_render'
	] );


}

function activecampaign_form_block_render($attributes, $content){
	// can compare $attributes['migrationVersion'] for conditional rendering based on attribute changes

	// CSS support still needs global setting support for un-upgraded blocks
	$css = ''; // global fallback
	if(isset($attributes['useCss']) && $attributes['useCss'] === '0'){
		$css = ' css=0';
	}
	elseif(isset($attributes['useCss']) && $attributes['useCss'] === '1'){
		$css = ' css=1';
	}
	$escapedClassNames = esc_attr($attributes['className']);
	if(!empty($attributes['formId'])){
		return "<div class=\"{$escapedClassNames}\">[activecampaign form=".$attributes['formId'].$css."]</div>";
	}
	return "<div class=\"{$escapedClassNames}\">[activecampaign]</div>";
}
