<?php
/**
 * Filter Template
 */

?>
<ul class="fields-list">
  <?php foreach( $this->fields as $field ) : ?>
    <li><?php echo $field->show(); ?></li>
  <?php endforeach; ?>
</ul>