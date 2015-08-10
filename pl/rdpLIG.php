<?php if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); ?>
<?php

class RDP_LIG {
    private $_key = '';
    private $_datapass = null;
    private $_version;
    private $_options = array();
    private $_discussionID = '';
    
    public function __construct($version,$options){
        $this->_version = $version;
        $this->_options = $options;        

        add_shortcode('rdp-ingroups-group', array(&$this, 'shortcode'));
        add_shortcode('rdp-ingroups-login', array(&$this, 'shortcode_login'));        
        add_shortcode('rdp-ingroups-member-count', array(&$this, 'shortcode_member_count')); 
    }//__construct
    
    function run() {
        if ( defined( 'DOING_AJAX' ) ) return;        

        $options = get_option( RDP_LIG_PLUGIN::$options_name );
        $fLIGRegisterNewUser = isset($options['fLIGRegisterNewUser'])? $options['fLIGRegisterNewUser'] : 'off';
        if($fLIGRegisterNewUser == 'on' && is_user_logged_in()){
            $current_user = wp_get_current_user();
            $this->_key = md5($current_user->user_email);
        }

        if(!has_filter('widget_text','do_shortcode'))add_filter('widget_text','do_shortcode',11);
        $this->_datapass = RDP_LIG_DATAPASS::get($this->_key); 
        if(isset($_GET['rdpingroupsaction']) && $_GET['rdpingroupsaction'] == 'logout'){
            self::handleLogout($this->_datapass);
        }
        
        if(!$this->_datapass->data_filled()) return;
        if($this->_datapass->tokenExpired()) return;

        $storedIP = $this->_datapass->ipAddress_get();
        $currentIP = RDP_LIG_Utilities::getClientIP();
        $ipVerified = ($storedIP === $currentIP );
        $rdpligrt =  $this->_datapass->sessionNonce_get();
        $rdpligrtAction = 'rdp-lig-read-'.$this->_key; 
        if($rdpligrt === 'new'){
            $rdpligrt = wp_create_nonce( $rdpligrtAction );
            $this->_datapass->sessionNonce_set($rdpligrt);
            $this->_datapass->save();
        }
        $nonceVerified = wp_verify_nonce( $rdpligrt, $rdpligrtAction );
        if($nonceVerified && $ipVerified )RDP_LIG_Utilities::$sessionIsValid = true;        
    }//run
    
   
    public function shortcode_member_count($attr){
        $sHTML = '';        
        if(empty($attr['id'])) return $sHTML;
        if(!is_numeric($attr['id'])) return $sHTML;
        $sURL = 'https://www.linkedin.com/grp/home?gid=' . $attr['id'];
        $html = rdp_file_get_html($sURL);
        if(!$html)return $sHTML;
        $oMemberCount = $html->find('div.header .right-entity .member-count',0);
        if(!$oMemberCount)return $sHTML;
        $text = (!empty($attr['prepend']))? trim($attr['prepend']) . ' ' : '' ;
        $text .= $oMemberCount->plaintext;
        $link = (!empty($attr['link']))? $attr['link'] : '' ;
        $fValidLink = filter_var($link, FILTER_VALIDATE_URL);
        if($fValidLink){
            $sHTML .= "<a class='rdp-lig-member-count' href='{$link}'";
            if(in_array('new', $attr)) $sHTML .= ' target="_blank"';
            $sHTML .= '>';
        }
        $sHTML .= $text;
        if($fValidLink)$sHTML .= '</a>';
        return apply_filters( 'rdp_lig_render_member_count', $sHTML );
    }//shortcode_member_count

    
    public function shortcode_login(){
        if(isset($_GET['rdpingroupsaction']) && $_GET['rdpingroupsaction'] == 'logout')return;
        $fIsLoggedIn = false;
        $token = $this->_datapass->access_token_get();
        if (RDP_LIG_Utilities::$sessionIsValid && !empty($token))$fIsLoggedIn = true;

        $sStatus = ($fIsLoggedIn)? "true":"false";
        
        $sHTML = '';

        if($sStatus == 'false'){
            $sHTML .= '<img style="cursor: pointer;" class="btnLGILogin" src="' . plugins_url( 'images/js-signin.png' , __FILE__ ) . '" > ';
        }else{
            
            $sHTML .= '<a class="rdp-lig-loginout rdp-lig-item logged-in-' . $sStatus . '" aria-haspopup="true" title="My Account">';
            $sHTML .= '<img alt="" src="' . $this->_datapass->pictureUrl_get() . '" class="avatar avatar-26 photo" height="26" width="26"/>';
            $sFName = $this->_datapass->firstName_get();
            if(!empty($sFName))$sHTML .= "Hello, {$sFName}.";
            $sHTML .= '</a>';
            if($this->_datapass->submenuCode_get() == ''):
                $imgSrc = $this->_datapass->pictureUrl_get();
                $fullName = $this->_datapass->fullName_get();
                $rdpingroupsid = 0;
                $rdpingroupspostid = 0;
                foreach($_GET as $query_string_variable => $value) {
                    if($query_string_variable == 'rdpingroupsid')$rdpingroupsid = $value;
                    if($query_string_variable == 'rdpingroupspostid')$rdpingroupspostid = $value;
                }
                $params = RDP_LIG_Utilities::clearQueryParams();
                if(!empty($rdpingroupsid))$params['rdpingroupsid'] = $rdpingroupsid;
                if(!empty($rdpingroupspostid))$params['rdpingroupspostid'] = $rdpingroupspostid;
                $params['rdpingroupsaction'] = 'logout';
                $url = add_query_arg($params);
               
                $oCustomMenuItems = array();
                $oCustomMenuItems = apply_filters( 'rdp_lig_custom_menu_items', $oCustomMenuItems, $sStatus );
                $sCustomMenuItems = '';
                foreach ($oCustomMenuItems as $key => $value) {
                    $sCustomMenuItems .= '<p><a href="' . $value . '">' . $key . '</a></p>';
                }

                $submenuHTML = <<<EOD
        <div id="rdp-lig-sub-wrapper" class="hidden">
            <div class="rdp-lig-wrap">
            <p>
                <img alt="" src="{$imgSrc}" class="rdp-lig-avatar rdp-lig-avatar-64 photo" height="64" width="64"/>
                <span class="rdp-lig-display-name">{$fullName}</span>
            </p>
            {$sCustomMenuItems}
            <p>
                <a href="{$url}">Sign Out</a>
            </p>
            </div><!-- .rdp-lig-wrap -->
        </div><!-- .rdp-lig-sub-wrapper -->   
   
EOD;
                $this->_datapass->submenuCode_set($submenuHTML);
                add_action('wp_footer', array(&$this,'renderUserActionsSubmenu'));
            endif;
        }

        $this->handleScripts($sStatus, null);
        return apply_filters( 'rdp_lig_render_login', $sHTML, $sStatus );
    }//shortcode_login
    
    public function renderUserActionsSubmenu(){
        echo $this->_datapass->submenuCode_get();
    }//renderUserActionsSubmenu


    public function shortcode($attr){
        if(isset($_GET['rdpingroupsaction']) && $_GET['rdpingroupsaction'] == 'logout')return;

        // Contents of this function will execute when the blogger
        // uses the [rdp-ingroups-group] shortcode.
        $fIsLoggedIn = false;
        $token = $this->_datapass->access_token_get();
        if (RDP_LIG_Utilities::$sessionIsValid && !empty($token))$fIsLoggedIn = true;

        // Get Discussion Post ID
        $this->_discussionID = '';
        if(isset($attr['discussion_id']))$this->_discussionID = $attr['discussion_id'];
        if(isset($_GET['rdpingroupspostid']))$this->_discussionID = $_GET['rdpingroupspostid'];
        
        // Get Discussion Source URL
        $nDiscussionURL = '';
        if(isset($attr['discussion_url']))$nDiscussionURL = $attr['discussion_url'];
        if(isset($_GET['rdpingroupsdiscussionurl']))$nDiscussionURL = $_GET['rdpingroupsdiscussionurl'];
        
        // Get Discussion Comment ID    
        $nCommentID = (isset($_GET['rdpingroupscommentid']))?$_GET['rdpingroupscommentid']:'';

        // Get Group ID
        $nGroupID = 0;
        if(isset($attr['id'])) $nGroupID = preg_replace( '/[^\d]/', '', $attr['id'] );
        $rdpingroupsid = (isset($_GET['rdpingroupsid']))?$_GET['rdpingroupsid']:0;
        if($rdpingroupsid != 0 && $rdpingroupsid != $nGroupID)$nGroupID = $rdpingroupsid;
        if(!is_numeric($nGroupID))$nGroupID = 0;
        
        $sStatus = ($fIsLoggedIn)? "true":"false";
        $this->handleScripts($sStatus);
        $this->renderHeaderTop($sStatus);
        $this->renderHeader($sStatus);
        $this->renderHeaderBottom($sStatus);
        $this->renderMainContainerHeader($sStatus,$nGroupID);
        $this->renderPaging($sStatus, 'top');
        $this->renderMainContainer($sStatus,$nGroupID,$nDiscussionURL);
        $this->renderPaging($sStatus, 'bottom');
        return '';
    }//shortcode

    private function renderMainContainer($status,$groupID,$discussionURL){
        $discussionID = $this->_discussionID;
        wp_nonce_field('rdp-lig-group-comment-'.$this->_key,'commentToken',false);
        wp_nonce_field('rdp-lig-shorten-url-'.$this->_key,'shortenURL',false);
        
        echo '<input type="hidden" id="txtCurrentAction" name="txtCurrentAction" value=""/>'; 
        $fFromSingle = isset($_GET['rdpingroupsfromsingle'])? $_GET['rdpingroupsfromsingle'] : 0 ;
        echo '<input type="hidden" id="txtFromSingle" name="txtFromSingle" value="' . $fFromSingle . '"/>';        
        echo '<input type="hidden" id="' . $groupID . '" class="defaultGroupID" value=""/>';
        
        $sLastPostID = isset($_GET['rdpingroupslastpostid'])? $_GET['rdpingroupslastpostid'] : '';
        $sPostID = empty($sLastPostID)? $discussionID : $sLastPostID;
        echo '<input type="hidden" id="txtLastDiscussionID" class="txtLastDiscussionID" value="' . $sPostID . '"/>';
        
        $nCurrentPage = isset($_GET['rdpingroupscurrentpage'])? $_GET['rdpingroupscurrentpage'] : 1 ;
        echo '<input type="hidden" id="txtCurrentPage" name="txtCurrentPage" value="' . $nCurrentPage . '"/>';
        
        $HTML = '';
        if(!empty($discussionID)){
            if($status == 'false' && $groupID != 0){
                $HTML = '<div style="text-align: right;">';
                $HTML .= $this->shortcode_login();
                $HTML .= '</div>'; 
                if(empty($discussionURL)){
                    $discussionURL = "https://www.linkedin.com/grp/post/{$discussionID}";
                }

                $HTML .= self::grabDiscussionFromLinkedIn($groupID,$discussionID,$discussionURL,$this->_datapass);
                
                $HTML .= '<div id="comment-response-container" class="alert">Please ';
                $HTML .= $this->shortcode_login();
                $HTML .= ' to view the discussion.</div>';
                $status .= ' abbreviated';
            }else{
                $params = array(
                   'post_id' => $discussionID,
               );
               wp_localize_script( 'rdp-lig-ajax', 'rdp_lig_single', $params );  
               $HTML = '<div id="comment-response-container" class="alert" style="display: none;"></div><div class="discussion-post layer" id="' . $discussionID . '"></div><div id="discussion-comments" class="discussion-comments layer"  style="position: relative;"></div><div class="comment-container" style="display: none;"></div>';
               $HTML .= '<div style="display:none"><div id="data">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</div></div>';
            }

        }elseif($status == 'false' && $groupID != 0){
            $HTML = $this->grabContentFromLinkedIn($groupID,$this->_options['sLIGPagingStyle'], $nCurrentPage,$this->_datapass);
        }
        
        echo '<div id="rdp-lig-main" class="' . $status . '">';        
        echo $HTML;
	echo '</div><!-- #rdp-lig-main -->';
        $options = get_option( 'rdp_lig_options' );
        $nMultiDiscussionContentWidth = empty($options['nMultiDiscussionContentWidth'])? '84' :$options['nMultiDiscussionContentWidth'] ;
        $nSingleDiscussionCommentWidth = empty($options['nSingleDiscussionCommentWidth'])? '82' : $options['nSingleDiscussionCommentWidth'];
        echo '<style>'."\n";
        echo "#rdp-lig-main .discussion-content{width: {$nMultiDiscussionContentWidth}%;}"."\n";
        echo "#rdp-lig-main .comment-content{width: {$nSingleDiscussionCommentWidth}%;}"."\n";
        echo "#rdp-lig-main .rdp-lig-group-photo-name{width: 75%;}"."\n";
        echo '</style>';
    }//renderMainContainer
    
    public static function grabDiscussionFromLinkedIn($groupID,$discussionID,$sURL,$Datapass){
        $sHTML = '';
        $html = rdp_file_get_html($sURL);  
        if(!$html)return $sHTML;
        
        $Post = new stdClass;
        $Post->id = $discussionID;
        $Post->title = '';
        $Post->summary = '';
        $Post->creationTimestamp = '';
        
        
        // discussion creator
        $Creator = new stdClass;
        $Creator->id = '';
        $Creator->pictureUrl = '';
        $Creator->name = '';
        $Creator->profileURL = '';
        $Creator->headline = '';
        $oHeader = $html->find('div.post-header',0);
        if($oHeader){
            $img = $oHeader->find('div.header-image img',0);
            if($img){
                $Creator->pictureUrl = $img->src;

            }
            $anchor = $oHeader->find('a.title',0);
            if($anchor){
                $Creator->name = $anchor->plaintext;
                $Creator->profileURL = $anchor->href;
                parse_str(parse_url($anchor->href, PHP_URL_QUERY), $output);
                if(in_array('id', $output))$Creator->id = $output['id'];                    
            }
            $headline = $oHeader->find('span.subtitle',0);
            if($headline)$Creator->headline = RDP_LIG_Utilities::entitiesPlain($headline->plaintext);
        }
        $Post->creator = $Creator;         

        $oBody = $html->find('div.post-body',0);
        // discussion title
        $ret = $oBody->find('h3.post-title',0);
        if($ret)$Post->title = RDP_LIG_Utilities::entitiesPlain($ret->plaintext);

        // discussion summary
        $ret = $oBody->find('p.post-details',0);            
        if($ret)$Post->summary = RDP_LIG_Utilities::entitiesPlain($ret->plaintext);

        // discussion timestamp
        $ret = $oBody->find('div.post-date',0);
        if($ret)$Post->creationTimestamp =  $ret->plaintext;       

        // discussion attachment
        $ret = $oBody->find('div.disc-article-preview',0); 
        if($ret){
            $Attachment = new stdClass;
            $Attachment->contentDomain = '';
            $Attachment->contentUrl = '';
            $Attachment->imageUrl = '';
            $Attachment->summary = '';
            $Attachment->title = '';                 
            $img = $ret->find('img',0);
            if($img)$Attachment->imageUrl = $img->src;

            $anchor = $ret->find('h4.article-title a',0);
            if($anchor){
                parse_str(parse_url($anchor->href, PHP_URL_QUERY), $output);
                if(in_array('url', $output))$Attachment->contentUrl = rawurldecode($output['url']);
                $Attachment->title = RDP_LIG_Utilities::entitiesPlain($anchor->plaintext);
            }

            $span = $ret->find('div.content span.source',0);
            if($span)$Attachment->contentDomain = $span->plaintext;

            $summary = $ret->find('div.content span.summary',0);
            if($summary)$Attachment->summary = RDP_LIG_Utilities::entitiesPlain($summary->plaintext);
            $Post->attachment = $Attachment;
        }   
        
        $oGroupProfile = RDP_LIG_GROUP_PROFILE::get($groupID);
        if (!$oGroupProfile->dataFilled()) {
            $oGroupProfile->load();
            $oGroupProfile->save();
        }          
        
        $sHTML = RDP_LIG_Discussion::postToHTML($Post, $groupID, $Datapass,$oGroupProfile->isOpenToNonMembers());
        $html->load('<html><body>'.$sHTML.'</body></html>');
        $body = $html->find('body',0);
        foreach ($body->find('a') as $anchor) {
            $anchor->href = null;
            $anchor->class = 'btnLGILogin';
            $anchor->postid = $discussionID;
        }
        $sHTML = $html->find('body',0)->innertext;        
        return $sHTML;
    }//grabDiscussionFromLinkedIn
    
    private function grabContentFromLinkedIn($groupID,$pagingStyle,$nCurrentPage,$oDatapass){
        $sHTML = '';
        $sURL = 'http://www.linkedin.com/groups?newItemsAbbr=&gid=' . $groupID;
        $html = rdp_file_get_html($sURL);
        if(!$html)return $sHTML;
        $oGroupProfile = RDP_LIG_GROUP_PROFILE::get($groupID);
        
        if (!$oGroupProfile->dataFilled()) {
            $oGroupProfile->load();
            $oGroupProfile->save();
        }    
        
        if($oGroupProfile->dataFilled() && !$oGroupProfile->hasErrors()){
            if(!$oGroupProfile->isOpenToNonMembers()){
               $sHTML .= '<div class="alert notice" role="alert"><span></span>This is a private group and discussions are not publicly visible.</div>';
            }else{
               $sHTML = RDP_LIG_Group::buildDiscussionItemList($html,$groupID,$pagingStyle,$nCurrentPage,$groupID,$oDatapass);
                $html->load('<html><body>'.$sHTML.'</body></html>');
                $body = $html->find('body',0);
                foreach ($body->find('a') as $anchor) {
                    $anchor->href = null;
                    $anchor->class = 'btnLGILogin';
                }
                $sHTML = $html->find('body',0)->innertext;
            }
        }

        return $sHTML;
    }//grabContentFromLinkedIn

    
    private function renderMainContainerHeader($status,$groupID){
        $oGroupProfile = RDP_LIG_GROUP_PROFILE::get($groupID);
        
        if (!$oGroupProfile->dataFilled()) {
            $oGroupProfile->load();
            $oGroupProfile->save();
        }  

        $sHTML = '<div id="rdp-lig-main-header" class="rdp-lig-main-header-' . $status . '"><div class="wrap">';
        $sHTML .= '<div id="rdp-lig-top-bar" class="top-bar with-wide-image with-nav">';
        $sHTML .= '<div class="header">';
        if($oGroupProfile->dataFilled() && !$oGroupProfile->hasErrors()){
            $url = get_permalink();
            $sHTML .= RDP_LIG_Groups::renderHeader($oGroupProfile,$url); 
        }
        $sHTML .= '</div><!-- .header" -->';
        $sHTML .= '</div><!-- #rdp-lig-top-bar" -->';
        $sHTML .= '</div><!-- .wrap --></div><!-- #rdp-lig-main-header -->';

        $sHTML = apply_filters( 'rdp_lig_render_main_container_header', $sHTML, $status);
        echo $sHTML;
    }//renderMainContainerHeader

    private function renderPaging($status,$location){
        if($status == 'false')return;
        $options = get_option( 'rdp_lig_options' );
        $sLIGPagingStyle = (empty($options))? '' :strtolower($options['sLIGPagingStyle']);
        if(empty($sLIGPagingStyle))$sLIGPagingStyle = 'full';
        if($sLIGPagingStyle == 'infinity' && $location == 'top') return;

        $sHTML = '<a class="rdp-lig-paging-link rdp-lig-paging-more rdp-lig-paging-more-' . $location . ' rdp-lig-paging-more-' . $location . '-' . $sLIGPagingStyle . ' show-more-items" rel="next" style="display: none;"><span class="show-more-text">SHOW MORE DISCUSSIONS</span></a>';
        $sHTML .= '<div id="rdp-lig-paging-container-' . $location . '" class="rdp-lig-paging-container rdp-lig-paging-container-' . $sLIGPagingStyle . '" style="display: none;">';
        if($location == 'bottom' && $sLIGPagingStyle == 'full') $sHTML .= '<div id="rdp-lig-paging-message"></div><!-- #rdp-lig-paging-message -->';
        $sHTML .= '<div id="rdp-lig-paging-controls-' . $location . '" class="rdp-lig-paging-controls"><div class="wrap">';
        if($sLIGPagingStyle != 'infinity')$sHTML .= '<a class="rdp-lig-paging-link rdp-lig-paging-previous">Previous Page</a> <span class="rdp-lig-paging-sep">&bull;</span> <a class="rdp-lig-paging-link rdp-lig-paging-next">Next Page</a>';
        $sHTML .= '</div><!-- wrap --></div><!-- .rdp-lig-paging-controls --></div><!-- .rdp-lig-paging-container -->';
        $sHTML = apply_filters( 'rdp_lig_render_paging', $sHTML, $status, $location);

        echo $sHTML;
    }//renderPaging
    
    public function scriptsEnqueue(){
        // GLOBAL FRONTEND SCRIPTS
        if(!wp_script_is( 'jquery-url', 'registered' )){
            wp_register_script( 'jquery-url', plugins_url( 'js/url.min.js' , __FILE__ ), array( 'jquery','jquery-query' ), '1.8.6', TRUE);
            wp_enqueue_script( 'jquery-url');
        } 

        wp_enqueue_script( 'rdp-lig-global', plugins_url( 'js/script.global.js' , __FILE__ ), array( 'jquery','jquery-query' ), $this->_version, TRUE);        
        $params = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'site_url' => get_site_url()
        );      
        wp_localize_script( 'rdp-lig-global', 'rdp_lig_global', $params );   
        
        
    }//scriptsEnqueue

    private function handleScripts($status){
        if(wp_style_is( 'rdp-lig-style-common', 'enqueued' )) return;
        // LinkedIn CSS
        wp_register_style( 'rdp-lig-style-common', plugins_url( 'style/linkedin.common.css' , __FILE__ ) );
	wp_enqueue_style( 'rdp-lig-style-common' );

        // RDP inGroups+ CSS
        wp_register_style( 'rdp-lig-style', plugins_url( 'style/default.css' , __FILE__ ),array( 'rdp-lig-style-common' ), $this->_version );
        wp_enqueue_style( 'rdp-lig-style' );        
        
        $filename = get_stylesheet_directory() .  '/ingroups.custom.css';
        if (file_exists($filename)) {
            wp_register_style( 'rdp-lig-style-custom',get_stylesheet_directory_uri() . '/ingroups.custom.css',array('rdp-lig-style','rdp-lig-style-common' ) );
            wp_enqueue_style( 'rdp-lig-style-custom' );
        }
        
        $wcrActive = (RDP_LIG_Utilities::pluginIsActive('we'))? 1 : 0 ;
        if($wcrActive){
            $wikiembed_options = get_option( 'wikiembed_options' );
            $wcrActive = empty($wikiembed_options['default']['global-content-replace'])? '0' : $wikiembed_options['default']['global-content-replace'];
            if(!is_numeric($wcrActive))$wcrActive = 0;
        }

        // RDP inGroups+ login script
        wp_enqueue_script( 'rdp-lig-login', plugins_url( 'js/script.login.js' , __FILE__ ), array( 'jquery','jquery-query','jquery-url','rdp-lig-global' ), $this->_version, TRUE);
        $url = get_home_url();
        $params = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'weActive' => $wcrActive,
            'loginurl' => $url 
        );
        wp_localize_script( 'rdp-lig-login', 'rdp_lig_login', $params );
        
        if($status == 'true'){
            // Position Calculator
            if(!wp_script_is('jquery-position-calculator'))wp_enqueue_script( 'jquery-position-calculator', plugins_url( 'js/position-calculator.min.js' , __FILE__ ), array( 'jquery' ), '1.1.2', TRUE);

            // RDP inGroups+ paging script
            wp_enqueue_script( 'rdp-lig-posts-paging', plugins_url( 'js/script.posts-paging-default.js' , __FILE__ ), array( 'jquery' ), $this->_version, TRUE);

            // RDP inGroups+ AJAX script
            wp_enqueue_script( 'rdp-lig-ajax', plugins_url( 'js/script.ajax.js' , __FILE__ ), array( 'jquery','jquery-query','jquery-url','rdp-lig-global'), $this->_version, TRUE);
            $options = get_option( 'rdp_lig_options' );
            $browser = new RDP_LIG_Browser();
            $versionPieces = explode('.', $browser->getVersion());
            $platform = '';
            switch ($browser->getPlatform()) {
                case RDP_LIG_Browser::PLATFORM_APPLE:
                    $platform = 'os-mac';
                    break;
                case RDP_LIG_Browser::PLATFORM_LINUX:
                    $platform = 'os-linux';
                    break;
                case RDP_LIG_Browser::PLATFORM_WINDOWS:
                    $platform = 'os-win';
                    break;            
                default:
                    break;
            }

            global $wp_query;
            
            $params = array(
                'key' => $this->_key,
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'person_id' => $this->_datapass->personID_get(),
                'paging_style' => $options['sLIGPagingStyle'],
                'mystery_pic_url' => plugins_url( 'images/ghost_person_60x60_v1.png' , __FILE__ ),
                'browser_name' => $browser->getBrowser(),
                'browser_version' => $versionPieces[0],
                'platform' => $platform,
                'wcr_active' => $wcrActive,
                'wp_post_id' => $wp_query->get_queried_object_id()
            );
            wp_localize_script( 'rdp-lig-ajax', 'rdp_lig', $params );
            
        }//if($status == 'false') ... else ...

        do_action( 'rdp_lig_after_scripts_styles');
    }//handleScripts

    private function renderHeader($status) {
        $sHTML = '';

        if($status == 'false'){
            $sHTML = '<img style="cursor: pointer;" class="btnLGILogin" src="' . plugins_url( 'images/js-signin.png' , __FILE__ ) . '" > ';
        }else{
            $sHTML = $this->shortcode_login();
        }

       echo apply_filters( 'rdp_lig_render_header', $sHTML, $status );
    } //renderHeader

    private function renderHeaderTop($status) {
        $sHTML = '<div id="rdp-lig-head" class="rdp-lig-head-' . $status . '"><div class="wrap">';
        echo apply_filters( 'rdp_lig_render_header_top', $sHTML,$status );
    }//renderHeaderTop

    private function renderHeaderBottom($status) {
        $sHTML = '</div><!-- .wrap --></div> <!-- #rdp-lig-head -->';
        echo apply_filters( 'rdp_lig_render_header_bottom', $sHTML, $status);
    }//renderHeaderBottom

    public static function handleLogout($datapass = null){
        if($datapass != null && $datapass->data_filled()){
            RDP_LIG_DATAPASS::delete($datapass->key());            
        }

        $rdpingroupsid = 0;
        $rdpingroupspostid = 0;
        foreach($_GET as $query_string_variable => $value) {
            if($query_string_variable == 'rdpingroupsid')$rdpingroupsid = $value;
            if($query_string_variable == 'rdpingroupspostid')$rdpingroupspostid = $value;
        }
        $params = RDP_LIG_Utilities::clearQueryParams();
        if(!empty($rdpingroupsid))$params['rdpingroupsid'] = $rdpingroupsid;
        if(!empty($rdpingroupspostid))$params['rdpingroupspostid'] = $rdpingroupspostid;
        $url = add_query_arg($params);
        
        // log the user out of WP, as well
        if(is_user_logged_in()){
            $url = wp_logout_url( $url );
        }

        // Hack to deal with 'headers already sent' on Linux servers
        // and persistent browser session cookies
        echo "<meta http-equiv='Refresh' content='0; url={$url}'>";
        ob_flush();
        exit;
    }//handleLogout
    
    
}//class RDP_LIG


/* EOF */
 