<?php if ( have_properties() ) { ?>

<ul class="wpp_featured_properties_shortcode clearfix">

<?php foreach ( returned_properties('load_gallery=false') as $property) {  ?>
 

	<li class="<?php echo $class; ?> wpp_featured_property_container wp-caption clearfix " >

		<a class="featured_property_thumbnail"  href="<?php echo $property['permalink']; ?>">
      <?php property_overview_image($thumbnail_size); ?>
		</a>

		<?php if(is_array($stats)): ?>
		<ul class="wp-caption-text shortcode_featured_properties">

		<?php foreach($stats as $stat):
			 
			if(empty($property[$stat]))
				continue;
        
		?>
			<li class="<?php echo $stat; ?>">
				<dl>
					<dt><?php echo (empty($wp_properties['property_stats'][$stat]) ? ucwords($stat) : $wp_properties['property_stats'][$stat]); ?>:</dt>
					<dd><?php echo $property[$stat];  ?></dd>
				</dl>
			</li>
		<?php endforeach; ?>
		</ul>
		<?php endif; ?>
    
   </li>
<?php } ?>
</ul>

<?php } ?>