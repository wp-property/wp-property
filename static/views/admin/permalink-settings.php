<?php
/**
 *
 *
 */

global $rewrite_rules, $blog_prefix;

//echo( '<pre>' . print_r( $rewrite_rules, true ) . '</pre>' );
?>
<table class="form-table wpp-permalinks-structure">
	<tr>
		<th><label><input name="wpp_permalinks" type="radio" value="" <?php checked('', $rewrite_rules); ?> /> <?php _e( 'Plain' ); ?></label></th>
		<td><code><?php echo get_option('home'); ?>/?p=123</code></td>
	</tr>
	<tr>
		<th>
			<label><input name="wpp_permalinks" id="custom_wpp_permalinks" type="radio" value="custom" <?php checked( !in_array( $rewrite_rules, $structures) ); ?> /><?php _e('Custom Structure'); ?></label>
		</th>
		<td>
			<code><?php echo get_option('home') . $blog_prefix; ?></code>
			<input name="wpp_permalinks" id="permalink_structure" type="text" value="<?php echo esc_attr($rewrite_rules); ?>" class="regular-text code" />
		</td>
	</tr>
</table>