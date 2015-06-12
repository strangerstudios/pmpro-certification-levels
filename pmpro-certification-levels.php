<?php
/*
Plugin Name: Paid Memberships Pro - Certification Levels Addon
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-certification-levels/
Description: Adds a "certification level" field to the user profile which determines which levels are available to a user.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

/*
	Add certification level value to edit user profile
*/
//show the shipping address in the profile
function pmprocl_show_extra_profile_fields($user)
{
	if(!current_user_can('edit_users'))
		return;

	$certification_level = $user->certification_level;
?>
	<h3><?php _e('Certification Level', 'pmpro');?></h3>
 
	<table class="form-table">
 
		<tr>
			<th><?php _e('Level', 'pmpro');?></th>			
			<td>
				<input id="certification_level" name="certification_level" type="text" size="5" value="<?php echo esc_attr($certification_level);?>" /> <small>Whole numbers only.</small>
			</td>
		</tr>
 
	</table>
<?php
}
add_action( 'show_user_profile', 'pmprocl_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'pmprocl_show_extra_profile_fields' );
 
function pmprocl_save_extra_profile_fields( $user_id ) 
{
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;
 
 	if(isset($_POST['certification_level']) && current_user_can('edit_users'))
		update_usermeta( $user_id, 'certification_level', intval($_POST['certification_level'] ));
}
add_action( 'personal_options_update', 'pmprocl_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'pmprocl_save_extra_profile_fields' );

/*
	add column to export
*/
//columns
function pmprocl_pmpro_members_list_csv_extra_columns($columns)
{
	$new_columns = array(
		"certification_level" => "pmprocl_extra_column_certification_level",
	);
	
	$columns = array_merge($columns, $new_columns);
	
	return $columns;
}
add_filter("pmpro_members_list_csv_extra_columns", "pmprocl_pmpro_members_list_csv_extra_columns");
//callback
function pmprocl_extra_column_certification_level($user)
{
	return $user->certification_level;
}

/*
	Add certification level value to edit levels page
*/
//show the checkbox on the edit level page
function pmprocl_pmpro_membership_level_after_other_settings()
{	
	$level_id = intval($_REQUEST['edit']);
	if($level_id > 0)
		$certification_level = get_option('pmpro_certification_level_' . $level_id);	
	else
		$certification_level = 0;
?>
<h3 class="topborder">Require Certification Level</h3>
<p>Only users with a certification level equal to or greater than the value above will be able to checkout for this level. Users and levels default to certification level 0.</p>
<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="certification_level"><?php _e('Certification Level Required:', 'pmpro');?></label></th>
		<td>
			<input id="certification_level" name="certification_level" type="text" size="5" value="<?php echo esc_attr($certification_level);?>" /> <small>Whole numbers only.</small>
		</td>
	</tr>
</tbody>
</table>
<?php
}
add_action('pmpro_membership_level_after_other_settings', 'pmprocl_pmpro_membership_level_after_other_settings');
//save hide shipping setting when the level is saved/added
function pmprocl_pmpro_save_membership_level($level_id)
{
	if(isset($_REQUEST['certification_level']))
		$certification_level = intval($_REQUEST['certification_level']);
	else
		$certification_level = 0;
	delete_option('pmpro_certification_level_' . $level_id);
	add_option('pmpro_certification_level_' . $level_id, $certification_level, "", "no");
}
add_action("pmpro_save_membership_level", "pmprocl_pmpro_save_membership_level");

/*
	Check certification level at checkout.
*/
function pmprocl_template_redirect()
{
	//make sure pmpro is activated
	if(!function_exists('pmpro_url'))
		return;

	//check if we're on front end and looking for a level
	if(!is_admin() && !empty($_REQUEST['level']))
	{
		//make sure this is a checkout page
		global $post;

		if(!empty($post) && strpos($post->post_content, "pmpro_checkout") !== false)
		{
			//see if level requires certification
			$level_certification_level = get_option('pmpro_certification_level_' . intval($_REQUEST['level']));
			
			if(!empty($level_certification_level))
			{
				//must be logged in and have a certification level !=
				if(is_user_logged_in())
				{
					global $current_user;
					$user_certification_level = $current_user->certification_level;

					if($user_certification_level >= $level_certification_level);
						return;	//we're good, don't redirect away
				}

				//if we get here, need to redirect back to levels page
				$url = add_query_arg('notcertified', '1', pmpro_url('levels'));
				
				wp_redirect($url);
				exit;
			}
		}
	}
}
add_action('template_redirect', 'pmprocl_template_redirect');

/*
	Show message on levels page if not certified
*/
function pmprocl_set_level_page_message() 
{
    if(!empty($_REQUEST['notcertified']) && function_exists('pmpro_setMessage'))
    {
    	pmpro_setMessage('That level requires a higher level of certification to checkout.', 'pmpro_error');
    }
}
add_action('wp','pmprocl_set_level_page_message', 99);
