<?php

class RDP_LIG_Shortcode_Popup {
    public static function addMediaButton($context){
	global $post, $pagenow;
	
	if ( in_array( $pagenow, array( "post.php", "post-new.php" ) ) && in_array( $post->post_type , array( "post", "page" ) ) ) {
            $rdp_lig_button_src = plugins_url('/images/linkedin.ico', __FILE__);
	    $output_link = '<a href="#TB_inline?width=400&inlineId=rdp-lig-shortcode-popup" class="thickbox button" title="inGroups+" id="rdp-lig-shortcode-button">';
            $output_link .= '<span class="wp-media-buttons-icon" style="background: url('. $rdp_lig_button_src.'); background-repeat: no-repeat; background-position: left bottom;"/></span>';
            $output_link .= '</a>';
            return $context.$output_link;
	} else {
            return $context;
	}        
    }//addMediaButton
    
    public static function renderPopupForm(){
        echo '<div id="rdp-lig-shortcode-popup" style="display:none;">';
        echo '<h3>Insert inGroups+ Shortcode</h3>';
        echo '<select id="ddLIGShortcode">';
        echo '<option value="*">Choose shortcode</option>';
        echo '<option value="Group">Group Discussions</option>';
        echo '<option value="Discussion">Individual Discussion</option>';
        echo '<option value="login">Login Button</option>';        
        echo '<option value="Member Count">Member Count</option>';        
        echo '</select>';
        echo '<p id="txtLIGID-wrap" style="display:none;"><label for="txtLIGID">ID #:</label> <input style="vertical-align: middle;padding: 2px;height: 28px;" type="text" id="txtLIGID"  value=""/></p>';
        echo '<p id="txtLIGDiscussionID-wrap" style="display:none;"><label for="txtLIGDiscussionID">Discussion ID #:</label> <input style="vertical-align: middle;padding: 2px;height: 28px;" type="text" id="txtLIGDiscussionID"  value=""/></p>';
        echo '<p id="txtLIGLink-wrap" style="display:none;"><label for="txtLIGLink">Link:</label> <input style="vertical-align: middle;width: 85%;padding: 2px;height: 28px;" type="text" id="txtLIGLink" value="" /></p>';
        echo '<p id="chkLIGNewWindow-wrap" style="display:none;"><input style="vertical-align: middle;padding: 2px;" type="checkbox" id="chkLIGNewWindow" value="" /> <label for="txtLIGLink">Open in new window</label></p>';
        
        echo '<p id="ddLIGTemplate-wrap" style="display:none;">Template to Use for Group Content: ';
        echo '<select id="ddLIGTemplate">';
        echo '<option value="same">Same as Current Page</option>';
        $templates = get_page_templates();
        foreach ( $templates as $template_name => $template_filename ) {
            echo "<option value='$template_name'>$template_name</option>";
        }        
        echo '</select>';
        echo '</p>';
        
        echo '<div>&nbsp;</div>';
        echo '<input type="button" value="Insert into Post/Page" id="btnInsertLIGShortcode" class="button">';
        echo '</div>';
        
        $script_src = plugins_url('/js/script.shortcode-popup.js', __FILE__);                
        wp_enqueue_script('rdp-lig-shortcode',$script_src, array('jquery'));
    }
    
    
}//RDP_LIG_Shortcode_Popup

/* EOF */
