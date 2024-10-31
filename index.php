<?php

/* 

Plugin name: Post favorite - H3
Description: This plugin will add fevorite stars in the Paragraph heading
Author: Md. Sarwar-A-Kawsar
Author URI: https://fiverr.com/sa_kawsar
Version: 1.0
*/

defined('ABSPATH') or die('You can\'t access to this page');
add_action('wp_enqueue_scripts','pfh3_custom_page_enqueue_script');
function pfh3_custom_page_enqueue_script(){
	wp_enqueue_script('jquery');
    wp_enqueue_style( 'pfh3-font-awesome-follow', plugin_dir_url( __FILE__ ).'/css/font-awesome.css' );
}

add_action('admin_menu','pfh3_prevent_follow_star');
function pfh3_prevent_follow_star(){
    add_options_page('Follow Settings','Follow Settings','manage_options','follow_settings','follow_settings_callback');
}
function pfh3_follow_settings_callback(){
    if(isset($_POST['not_follow_submit'])):
		update_option( 'not_follow_pages', sanitize_text_field( $_POST['not_follow_pages'] ));
	    ?>
	    <div class="notice notice-success is-dismissible">
	        <p><?php _e( 'Follow settings updated!' ); ?></p>
	    </div>
	    <?php
	endif;
    ?>
    <h2>Follow settings</h2>
    <p>Check the checkboxes beside the page title from which you want to remove the follow stars</p>
    <form method="post">
        <?php
            $args = array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => -1
            );
            
            $query = new WP_Query( $args );
            $not_follow_data = get_option('not_follow_pages');
            if($query->have_posts()):
                while($query->have_posts()): $query->the_post();
                    if($not_follow_data && is_array($not_follow_data) && in_array(get_the_ID(),$not_follow_data)){
                        $checked = 'checked';
                    }else{
                        $checked = '';
                    }
                    echo esc_html( '<span><input '.$checked.' style="margin:8px;" type="checkbox" name="not_follow_pages[]" value="'.get_the_ID().'" />'.get_the_title().'</span><br>' );
                endwhile;
            else:
                echo 'No pages found';
            endif;
        ?>
        <input style="margin-top:16px;" type="submit" name="not_follow_submit" value="Update" class="button button-primary"/>
    </form>
    <?php
}

function pfh3_activate(){
	global $wpdb;
	$table_name = $wpdb->prefix.'fev_add_remove';
		if( $wpdb->get_var("SHOW TABLES LIKE ".$table_name) != $table_name ){
			$sql = "CREATE TABLE $table_name (
	          fev_id INT(9) NOT NULL AUTO_INCREMENT,
	          fev_text VARCHAR(100) NOT NULL,
	          page_id VARCHAR(100) NOT NULL,
	          user_id VARCHAR(100) NOT NULL,
	          value VARCHAR(100) NOT NULL,
	          theparent LONGTEXT NOT NULL,
	          nth_in_page INT(100) NOT NULL,
	          UNIQUE KEY fev_id (fev_id)
	     	) $charset_collate;";
	     	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	     	dbDelta( $sql );
		}
}
register_activation_hook( __FILE__, 'pfh3_activate' );

function pfh3_add_script_to_footer(){
	?>
	<script type="text/javascript">
	    function stripHtml(html) {
            var StrippedString = html.replace(/(<([^>]+)>)/ig,"");
            return StrippedString;
        }
		function add_fev(unique_id,element,post_id,user_id,value,i,isPage){
			jQuery(document).ready(function(){
			 //   console.log(i);
				var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
			    var h3s = document.getElementsByTagName('h3');
				element.setAttribute('class', 'fa fa-refresh fa-spin');
				if ( isPage == true ) {
    				var parent_div = h3s[i].closest("div[data-q_id]");
    				var temp_data = parent_div;
    				temp_data.getElementsByTagName('h3').innerHTML = "";
                    var parent = temp_data.innerHTML;
				} else {
			        var parent = '';
				}
				jQuery.ajax({
				    type: 'POST',
				    url: ajaxurl,
				    dataType: 'html',
				    data: {
				        action: 'pfh3_add_fev_callback',
				        newValue: [unique_id,post_id,user_id,value,parent,i]
				    },
				    success: function(response) {
				        console.log(response);
				        element.setAttribute('class', 'fa fa-star');
				        element.style.color = response;
				        if ( isPage != true ) {
				        	element.closest('.trigger-holder').style.display = "none";
				        }
				    },
				    error: function(errorThrown){
				        console.log(errorThrown);
				    }
				})
			});
		}
		<?php
		    global $wpdb;
		    $user_id = get_current_user_id();
		    $table_name = $wpdb->prefix.'fev_add_remove';
		    $fevs_data = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id='$user_id'");
		    $fev_texts = [];
		    foreach($fevs_data as $fev_data){
		        $fev_texts[$fev_data->fev_text] = $fev_data->value;
		    }
		?>
		
        jQuery(document).ready(function(){
            var page_fev_data = <?php echo json_encode($fev_texts); ?>;
            console.log(page_fev_data);
            var h3_tags = document.getElementsByTagName('h3');
            <?php
            global $post;
            $not_follow_data = is_user_logged_in() ? get_option('not_follow_pages') : 'false';
            if(!$not_follow_data || !is_array($not_follow_data) || !in_array($post->ID,$not_follow_data)):
                if(is_user_logged_in()):
            ?>
            for (var i = h3_tags.length - 1; i >= 0; i--) {
                var parent_div = h3_tags[i].closest("div[data-q_id]");
                var parent = parent_div.innerHTML;
                // console.log(parent);
                var h3_html = h3_tags[i].innerHTML;
                var value = stripHtml(h3_html);
                var star_color = '';
                var unique_id = '<?php echo $post->ID; ?>' + i;
                // console.log(unique_id);
                if (page_fev_data.hasOwnProperty(unique_id)) {
                    star_color = '#ecd218';
                } else {
                    star_color = 'gray';
                }
                h3_tags[i].innerHTML = '<i onclick="add_fev(<?php echo $post->ID;?>' + i + ',this,<?php echo $post->ID; ?>,<?php echo get_current_user_id(); ?>,\'' + value +'\',' + i + ',true)" class="fa fa-star" style="color:' + star_color + ';margin-right:8px;"></i>' + h3_html;
            }
            <?php endif; ?>
            <?php endif; ?>
            console.log(h3_tags);
        });
        // alert('test');
	</script>
	<?php
}
add_action( 'wp_footer', 'pfh3_add_script_to_footer',999 );

function pfh3_add_fev_callback(){
	global $wpdb;
	$table_name = $wpdb->prefix.'fev_add_remove';
	if(isset($_POST['newValue'])):
		$unique_key = sanitize_text_field( $_POST['newValue'][0] );
		$post_id = sanitize_text_field( $_POST['newValue'][1] );
		$user_id = sanitize_text_field( $_POST['newValue'][2] );
		$value = sanitize_text_field( $_POST['newValue'][3] );
        $theparent = stripslashes(sanitize_text_field( $_POST['newValue'][4] ));
        $nth_in_page = sanitize_text_field( $_POST['newValue'][5] );
		$wpdb->get_results("SELECT * FROM $table_name WHERE fev_text='$unique_key' AND user_id='$user_id'");
		if($wpdb->num_rows > 0):
			$wpdb->delete($table_name,array('fev_text' => $unique_key,'user_id' => $user_id));
			echo 'gray';
		else:
			$wpdb->insert($table_name,array('fev_text' => $unique_key,'page_id' => $post_id,'user_id' => $user_id,'value' => $value,'theparent' => $theparent,'nth_in_page' => $nth_in_page));
			echo '#ffee6b';
		endif;
	endif;
	exit();
}

add_action('wp_ajax_add_fev_callback','pfh3_add_fev_callback');
add_action('wp_ajax_nopriv_add_fev_callback','pfh3_add_fev_callback');

add_shortcode( 'fev_list', 'pfh3_fev_list_callback' );
function pfh3_fev_list_callback(){
	ob_start();
	if(is_user_logged_in()):
	global $wpdb,$post;
	$table_name = $wpdb->prefix.'fev_add_remove';
	$user_id = get_current_user_id();
	$get_fevs = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id='$user_id'"); ?>
	<div id="fev-holder" style="width:100%;">
	<?php
	foreach($get_fevs as $get_fev):
		$post_id = $get_fev->page_id;
		$user_id = $get_fev->user_id;
		$unique_key = $get_fev->fev_text;
		$value = $get_fev->value;
		$parent = $get_fev->theparent;
		$nth_in_page = $get_fev->nth_in_page;
		$get_post = get_post( $post_id );
		?>
		<div style="display: flex;flex-direction: row;flex-wrap: wrap;">
			<div class="trigger-holder" style="width:100%;
										display:flex;padding:16px;
										width: 100%;
									    display: flex;
									    padding: 16px;
									    /* background: #dcd8d8; */
									    margin: 16px 0px 0px 0px;
									    box-shadow: 0px 0px 10px 0px grey;">
				<div style="text-align:center;width:10%;"><i class="fa fa-star" style="font-size:24px;color:#ffee6b;" onclick="add_fev(<?php echo $unique_key; ?>,this,<?php echo $post_id; ?>,<?php echo $user_id; ?>,'<?php echo $value; ?>',<?php echo $nth_in_page; ?>,false)"></i></div>
				<div style="width:85%;text-align:left;"><a href="<?php the_permalink( $post_id ); ?>"><?php echo $get_post->post_title.' - '.$value; ?></a>
				
	            </div>
	            <div class="trigger" style="width:5%;text-align:right;font-size:18px;">
	                <i style="cursor:pointer;color:#0D3A85;" class="fa fa-caret-down"></i>
	            </div>
        	</div>
            <div style="display:none;width:100%;padding:32px 16px;background: #f3f1f1;">
            	<?php echo do_shortcode(stripslashes($parent)); ?>
            </div>
		</div>
		<?php
	endforeach;
	echo esc_html( '</div>' );
	?>
	<script>
	    jQuery(document).ready(function(){
	       jQuery('#fev-holder h3, .vc_empty_space').hide();
	       jQuery('.fa-star').on('click',function(){
	           jQuery(this).closest('.trigger-holder').next().fadeOut('medium');
	       });
	       jQuery('.trigger').on('click',function(){
	            jQuery(this).closest('.trigger-holder').next().slideToggle('slow');
	       });
	    });
	</script>
	<?php
	else:
	   echo esc_html( '<script>window.location.replace("https://solve-x.dk/login/")</script>' );
	endif;
	return ob_get_clean();
}