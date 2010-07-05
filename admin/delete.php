<?php
//Redirect to the main plugin options page if form has been submitted
if($_GET['action'] == 'delete' && $_GET['updated']){
	wp_redirect(admin_url() . 'options-general.php?page=' . GCE_PLUGIN_NAME . '.php&updated=deleted');
}

//Main text
function gce_delete_main_text(){
	?>
	<p><?php _e('Are you want you want to delete this feed? (Remember to remove / adjust any widgets or shortcodes associated with this feed).', GCE_TEXT_DOMAIN); ?></p>
	<?php
}

//ID
function gce_delete_id_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<input type="text" disabled="disabled" value="<?php echo $options['id']; ?>" size="3" />
	<input type="hidden" name="gce_options[id]" value="<?php echo $options['id']; ?>" />
	<?php
}

//Title
function gce_delete_title_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<input type="text" name="gce_options[title]" disabled="disabled" value="<?php echo $options['title']; ?>" size="50" />
	<?php
}
?>