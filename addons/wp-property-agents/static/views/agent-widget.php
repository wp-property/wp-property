<div class='wpp_agent_info_single_wrapper'>

  <?php
  foreach ($fields as $slug):

//        print_r($slug);
    if (empty($slug))
      continue;

    if (!in_array(trim($slug), $no_label_items))
      continue;

    $this_field = nl2br($user_data->$slug);

    $this_field = do_shortcode($this_field);
    if ($slug !== 'full_bio' && $slug !== 'agent_image') {
      $ul_print_rows[] = apply_filters('wpp_agent_widget_field_' . $slug, "<li class='wpp_agent_stats_{$slug}'>{$this_field}</li>", $display_fields, $slug, $user_data->ID);
    } else if ($slug == 'agent_image') {
      $agent_image = apply_filters('wpp_agent_widget_field_agent_image', $this_field, $display_fields, $slug, $user_data->ID);
    } else if ($slug == 'full_bio') {
        $field_full_bio = apply_filters('wpp_agent_widget_field_full_bio', "<div class='wpp_agent_full_bio clearfix'>{$user_data->full_bio}</div>", $display_fields, $slug, $user_data->ID);
    }
  endforeach;

  if ($fields) foreach ($fields as $slug) {
    if (empty($slug) || empty($display_social_fields[$slug])) {
      continue;
    }
    /** Skip if this attribute is a no label attribute */
    if (in_array(trim($slug), $no_label_items)) {
      continue;
    }
    $this_field = nl2br($user_data->$slug);
    $this_field = str_replace(" ", "", $this_field);
    if (empty($this_field)) {
      continue;
    }
    if (WPP_F::isURL($this_field)) {
      $social_element = "<li class='wpp_agent_social_link with_permalink {$slug}'><a href='{$this_field}'>{$display_social_fields[$slug]}</a></li>";
    } else if (WPP_F::is_email($this_field)) {
      $social_element = "<li class='wpp_agent_social_link with_permalink {$slug}'><a href='mailto:{$this_field}'>{$display_social_fields[$slug]}</a></li>";
    } else {
      $social_element = "<li class='wpp_agent_social_link {$slug}'>{$this_field}</li>";
    }
    $social_print_fields[] = apply_filters('wpp_agent_widget_field_' . $slug, $social_element, $display_social_fields, $slug, $user_data->ID);
  }
  ?>


  <?php if ($ul_print_rows):
    if ($agent_image):
      echo '<div class="wpp_agent_image">' . $agent_image . '</div>';
    endif;
    ?>
    <ul class="wpp_agent_info_list">
      <?php
      echo implode('', $ul_print_rows);

      if (isset($social_print_fields) && is_array($social_print_fields)): ?>
        <li class="wpp_agent_stats_socials">
          <ul class="wpp_agent_socials_list"><?php echo implode('', $social_print_fields); ?></ul>
        </li>
      <?php endif;
      ?>
    </ul>
  <?php endif; ?>

  <?php
  if ($fields) foreach ($fields as $slug) {
    if (empty($slug) || empty($display_fields[$slug])) {
      continue;
    }
    //** Skip if this attribute is a no label attribute */
    if (in_array(trim($slug), $no_label_items)) {
      continue;
    }
    $this_field = nl2br($user_data->$slug);
    if (empty($this_field)) {
      continue;
    }
    $dt_element = "<dt class='wpp_agent_stats_{$slug}'>{$display_fields[$slug]}:</dt>";
    //** Make link */
    if (WPP_F::isURL($this_field)) {
      $this_field = "<a class='wpp_agent_attribute_link' href='{$this_field}' title='{$this_field}'>{$display_fields[$slug]}</a>";
      $dt_element = "<dt class='wpp_agent_stats_{$slug}'></dt>";
    }
    $this_field = do_shortcode($this_field);
    $dl_print_fields[] = apply_filters('wpp_agent_widget_field_' . $slug, "{$dt_element}<dd class='wpp_agent_stats_{$slug}'>{$this_field}</dd>", $display_fields, $slug, $user_data->ID);
  }

  if (isset($dl_print_fields) && is_array($dl_print_fields)): ?>
    <dl class="wpp_agent_info_list clearfix"><?php echo implode('', $dl_print_fields); ?></dl>
  <?php endif;
  if ($field_full_bio) {
    echo $field_full_bio;
  }
  ?>


</div>