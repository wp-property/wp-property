<?php 
global $wpp_feps_taxonomy_field_counter;
$wpp_feps_taxonomy_field_counter++;
?>
<div class="<?php echo "{$att_data['ui_class']} rwmb-field rwmb-taxonomy-wrapper";?>" data-slug="<?php echo $att_data['slug'];?>" data-tax-counter="<?php echo $wpp_feps_taxonomy_field_counter;?>">
	<div class="tagsdiv">
		<div class="jaxtag">
		 	<div class="ajaxtag clearfix">
				<label class="screen-reader-text" for="new-tag-property_feature">Add New Feature</label>
				<div class="ui-widget">
					<input type="text" class="newtag form-input-tip" size="16" autocomplete="off" aria-describedby="new-tag-property_feature-desc" value="">
					<input type="button" id="feps-input-auto-<?php echo $wpp_feps_taxonomy_field_counter;?>" class="button tagadd" value="Add">
				</div>
			</div>
			<p class="howto" id="new-tag-property_feature-desc">Separate tags with commas</p>
		</div>
		<div class="tagchecklist">
			<?php
			$i = 0;
			if(is_array($value))
				foreach ($value as $key => $val) {
					$i++;
					echo "<span id='tax-$i' class='tax-tag'>";
						echo "<span><a id='property_feature-check-num-$i' class='ntdelbutton notice-dismiss' tabindex='0'>X</a>&nbsp;$val</span>";
						echo "<input type='hidden' name='wpp_feps_data[{$att_data['slug']}][]' value='$val' />";
					echo "</span>";
				}
			?>
		</div>
		<input type="hidden" id="wpp-feps-tax-<?php echo $att_data['slug'];?>">
	</div>
	<script>
		var availableTags_<?php echo $wpp_feps_taxonomy_field_counter;?> = ["<?php echo implode('", "', $availableTags);?>"];
	</script>
</div>
<?php if($wpp_feps_taxonomy_field_counter == 1):?>
<script type="text/html" id="wpp-feps-taxnomy-template">
	<span class='tax-<%= i %>'>
		<span><a id='property_feature-check-num-<%= i %>' class='ntdelbutton notice-dismiss' tabindex='0'>X</a>&nbsp;<%= val %></span>
		<input type='hidden' name='wpp_feps_data[<%= slug %>][]' value='<%= val %>' />
	</span>
</script>
<?php endif;?>