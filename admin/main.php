<div class="wrap">
	<h3><?php _e('Add a New Feed', GCE_TEXT_DOMAIN); ?></h3>

	<a href="<?php echo admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php&action=add'); ?>" class="button-secondary" title="<?php _e('Click here to add a new feed', GCE_TEXT_DOMAIN); ?>"><?php _e('Add Feed', GCE_TEXT_DOMAIN); ?></a>

	<br /><br />
	<h3><?php _e('Current Feeds', GCE_TEXT_DOMAIN); ?></h3>

	<?php
	//If there are no saved feeds
	$options = get_option(GCE_OPTIONS_NAME);
	if(empty($options)){
	?>

	<p><?php _e('You haven\'t added any Google Calendar feeds yet.', GCE_TEXT_DOMAIN); ?></p>

	<?php //If there are saved feeds, display them ?>
	<?php }else{ ?>

	<table class="widefat">
		<thead>
			<tr>
				<th scope="col"><?php _e('ID', GCE_TEXT_DOMAIN); ?></th>
				<th scope="col"><?php _e('Title', GCE_TEXT_DOMAIN); ?></th>
				<th scope="col"><?php _e('URL', GCE_TEXT_DOMAIN); ?></th>
				<th scope="col"></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="col"><?php _e('ID', GCE_TEXT_DOMAIN); ?></th>
				<th scope="col"><?php _e('Title', GCE_TEXT_DOMAIN); ?></th>
				<th scope="col"><?php _e('URL', GCE_TEXT_DOMAIN); ?></th>
				<th scope="col"></th>
			</tr>
		</tfoot>

		<tbody>
			<?php 
			foreach($options as $key => $event){ ?>
			<tr>
				<td><?php echo $key; ?></td>
				<td><?php echo $event['title']; ?></td>
				<td><?php echo $event['url']; ?></td>
				<td align="right">
					<a href="<?php echo admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php&action=edit&id=' . $key); ?>"><?php _e('Edit', GCE_TEXT_DOMAIN); ?></a>
					|
					<a href="<?php echo admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php&action=delete&id=' . $key); ?>"><?php _e('Delete', GCE_TEXT_DOMAIN); ?></a>
				</td>
			</tr>
			<?php } ?>
		</tbody>

	</table>

	<?php } ?>

	<br />
	<h3><?php _e('Custom Stylesheet', GCE_TEXT_DOMAIN); ?></h3>
	<p><?php _e('If you would rather use a custom CSS stylesheet than the default, enter the stylesheet URL below. Leave blank to use the default.', GCE_TEXT_DOMAIN); ?></p>

	<p><input type="text" name="gce_stylesheet" value="<?php echo get_option('gce_stylesheet'); ?>" size="100" /></p>

	<input type="submit" class="button-primary" value="<?php esc_attr_e('Save URL', GCE_TEXT_DOMAIN); ?>" />
</div>