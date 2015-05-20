<?php if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); ?>
<?php
/**
 * Description of rdpLIGGroup
 *
 * @author Robert
 */
class RDP_LIG_Group {
    
    static function fetchDiscussionItemList(){
        $key = (isset($_POST['key']))? $_POST['key'] : '';
        check_ajax_referer( 'rdp-lig-group-comment-'.$key, 'security' );
        $id = (isset($_POST['group_id']))? $_POST['group_id'] : 0;
        $dataPass = array();
        $oDatapass = RDP_LIG_DATAPASS::get($key);
        
        $resource = "https://www.linkedin.com/groups?newItemsAbbr=&gid={$id}&split_page={$_POST['start']}";
        $html = rdp_file_get_html($resource);

        if(!$html){
            $dataPass['code'] = 503;
            $dataPass['message'] = 'Service Unavailable - Unable to retrieve group data';
            echo json_encode($dataPass);
            die();
        } 
        
        $nextPage = null;
        $ret = $html->find('div.more-feed li.next',0);
        if($ret){
            $anchor = $ret->find('a',0);
            if($anchor){
                $href = RDP_LIG_Utilities::entitiesPlain($anchor->href);
                parse_str(parse_url($href, PHP_URL_QUERY), $output);
                if(key_exists('split_page', $output))$nextPage = $output['split_page'];                 
            }
        }
        
       
        $dataPass = array(
            'html' => self::buildDiscussionItemList($html,$id,$_POST['paging_style'],$_POST['start'],$id,$oDatapass),
            'paging_style' => $_POST['paging_style'],
            'start'=>$_POST['start'],
            'next_page' => (int)$nextPage,
            'code' => 200,
            'message' => 'OK'
        );        
        


        echo json_encode($dataPass);
        die();
    }//fetchDiscussionItemList
    
    static function buildDiscussionItemList($html,$groupID,$pagingStyle,$start,$gid,$oDatapass) {
        $JSON = array();        
        foreach($html->find('#content .discussion-item') as $element){
            $Post = new stdClass;
            $Post->id = '';
            $Post->title = '';
            $Post->summary = '';
            $Post->creationTimestamp = '';
            
            // discussion id
            $X = 'data-li-item_id';
            $itemID = $element->$X;
            $Post->id = "{$groupID}-{$itemID}";
            
            // discussion title
            $ret = $element->find('a.discussion-title',0);
            if($ret)$Post->title = RDP_LIG_Utilities::entitiesPlain($ret->plaintext);
            
            // discussion summary
            $ret = $element->find('div.user-contributed p',0);            
            if($ret)$Post->summary = RDP_LIG_Utilities::entitiesPlain($ret->plaintext);
            
            // discussion timestamp
            $ret = $element->find('li.timestamp',0);
            if($ret)$Post->creationTimestamp =  $ret->plaintext;       
            
            // discussion creator
            $Creator = new stdClass;
            $Creator->id = '';
            $Creator->pictureUrl = '';
            $Creator->name = '';
            $Creator->profileURL = ''; 
            $ret = $element->find('div.entity',0);
            if($ret){
                $img = $ret->find('img.photo',0);
                if($img){
                    $Creator->pictureUrl = $img->src;
                    $Creator->name = $img->alt;
                }
                $anchor = $ret->find('a',0);
                if($anchor){
                    $Creator->profileURL = $anchor->href;
                    $url = RDP_LIG_Utilities::entitiesPlain($anchor->href);
                    parse_str(parse_url($url, PHP_URL_QUERY), $output);
                    if(key_exists('id', $output))$Creator->id = $output['id'];                    
                }
            }
            $Post->creator = $Creator;
             
            // discussion attachment
            $ret = $element->find('div.referenced-item',0); 
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
                
                $span = $ret->find('span.content-source',0);
                if($span)$Attachment->contentDomain = $span->plaintext;
                
                $summary = $element->find('div.discussion-content p.article-summary',0);
                if($summary)$Attachment->summary = RDP_LIG_Utilities::entitiesPlain($summary->plaintext);
                $Post->attachment = $Attachment;
            }
          

            $JSON[] = $Post;
        }        

        if($JSON){
            $postID = (isset($_POST['wp_post_id']))? $_POST['wp_post_id'] : '';
            $oDatapass->wpPostID_set($postID); 
            return self::postsToHTML($JSON,$pagingStyle,$start,$gid,$oDatapass);
        }else{
            return '<div class="alert notice" role="alert"><span></span>No discussion items found.</div>';
        }

    }//buildDiscussionItemList

    private static function postsToHTML($Posts,$paging_style,$start,$gid,$Datapass) {
	$sHTML = '';
        if($start == 1 || $paging_style != 'infinity')$sHTML .= '<div id="list-view-container"><ul class="discussion-item-list">';
	foreach($Posts as $item){
            $sHTML .= self::createDiscussionItem($item,$gid,$Datapass);
        }//foreach($Posts as $item)
        if($start == 1 || $paging_style != 'infinity')$sHTML .= '</ul><!--end discussion-item-list--></div><!--end list-view-container--><div class="clear before-paging"></div>';

        return $sHTML;
    }//postsToHTML

    private static function createDiscussionItem($Post,$gid,$Datapass){
        $sFullName = $Post->creator->name;
        $sHTML = '<li class="discussion-item" id="' . $Post->id . '" postid="' . $Post->id . '">';
        $sHTML .= self::getMiniprofileSection($Post, $sFullName,$Datapass);

        $sHTML .= '<div class="discussion-content "><div class="discussion-article">';
        $sAttachmentSummary = (!empty($Post->attachment) && property_exists($Post->attachment, 'summary'))? $Post->attachment->summary : '' ;
        $sPostSummary = (property_exists($Post, 'summary'))? $Post->summary : '' ;
        if(empty($Post->attachment) || $sAttachmentSummary != $sPostSummary) $sHTML .= self::getUserContributedSection($Post,$sFullName,$gid,$Datapass);
        if(!empty($Post->attachment)) $sHTML .= self::getReferencedItemSection($Post);
        $sHTML .= '</div><!--end discussion-article-->';
        $sHTML .= self::getItemActionsSection($Post);        
        $sHTML .= '</div><!--end discussion-content-->';
        $sHTML .= '</li><!--end discussion-item-->';
        return $sHTML;
    }//createDiscussionItem
    
    
    static function getItemActionsSection($Post,$isSingle = false,$isOpenGroup = false){
        $sHTML = '<ul class="item-actions">';
        
        if($isSingle && $isOpenGroup){
            $options = get_option( 'rdp_lig_options' );
            $text_string = empty($options['sLIGBitlyAccessToken'])? '' : $options['sLIGBitlyAccessToken'];
            if(!empty($text_string)){
                $sHTML .= '<li class="share" style="display: none;">';
                $sHTML .= '<a id="share-link-' . $Post->id . '" postid="' . $Post->id . '" class="rdp-lig-discussion-share-link share">Share</a>';

                $sHTML .= '<div id="third-party-sharing">';
                $sHTML .= '<div class="content">';
                $sHTML .= '<div class="third-party">';
                $sHTML .= '<h4>Share this discussion</h4>';
                $sHTML .= '<div class="buttons">';
                $sHTML .= '<a id="anetShareTwitter" class="share-button" data-count="none" target="_blank"><span class="lig-site twitter"></span></a>';
                $sHTML .= '<a name="fb_share" id="anetShareFB" type="icon" class="share-button"><span class="FBConnectButton_Simple"></span></a>';

                $sHTML .= '<div class="share-button plusone">
    <div style="text-indent: 0px; margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px; padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 0px; background-attachment: scroll; background-repeat: repeat; background-image: none; background-position: 0% 0%; background-size: auto; background-origin: padding-box; background-clip: border-box; background-color: transparent; border-top-style: none; border-right-style: none; border-bottom-style: none; border-left-style: none; float: none; line-height: normal; font-size: 1px; vertical-align: baseline; display: inline-block; width: 24px; height: 15px;" id="___plusone_0">
    <div class="g-plusone" data-size="small" data-annotation="none"></div>
    </div><!--end #___plusone_0-->
    </div><!--end .plusone--><script src="https://apis.google.com/js/platform.js" async defer></script>';


                $sHTML .= '</div>';
                $sHTML .= '<input type="text" value="" class="short-url"/>';
                $sHTML .= '</div><!--end .third-party-->';
                $sHTML .= '</div><!--end .content-->';
                $sHTML .= '<div class="arrow"></div>';
                $sHTML .= '</div><!--end #third-party-sharing-->';

                $sHTML .= '</li>';                  
            }//if(!empty($text_string))
        }//if($isSingle)


         // Creation Time
         $sHTML .= '<li class="create-date timestamp last">' . $Post->creationTimestamp . '</li>';
         $sHTML .= '</ul><!--end item-actions-->';
         return $sHTML;
    } //getItemActionsSection    

    static function getMiniprofileSection($item,$sFullName){
        $sHTML = '<span class="new-miniprofile-container">';
        $sURL = empty($item->creator->pictureUrl)? RDP_LIG_Utilities::mysteryPicUrl() : $item->creator->pictureUrl ;
        $sHTML .= '<a href="' . $item->creator->profileURL . '" target="_new" class="rdp-lig-member-info-link" memberid="' . $item->creator->id . '" postid="'. $item->id . '" title="Click to see this member&apos;s bio">';
        $sHTML .= '<img id="img-'. $item->id . '" src="' . $sURL . '" width="60" height="60" alt="' . $sFullName . '" /></a>';
        $sHTML .= '</a>';
        $sHTML .= '</span>';
        return $sHTML;
    }//getMiniprofileSection

    static function getUserContributedSection($Post,$sFullName,$gid,$Datapass, $fTruncate = true){
        $sHTML = '<div class="user-contributed">';
        $postID = $Datapass->wpPostID_get();
        $url = get_permalink($postID);
        $params = RDP_LIG_Utilities::clearQueryParams();
        $params['rdpingroupspostid'] = $Post->id;
        $params['rdpingroupsid'] = $gid;       
        $params['rdpingroupscb'] = uniqid('', true); 
        $params['rdpingroupskey'] = $Datapass->key(); 
        $url = add_query_arg($params,$url);  
        $sHTML .= '<h3>';
        if($fTruncate)$sHTML .= '<a href="' . $url . '" class="rdp-lig-post-link" postid="' . $Post->id . '" >';
        $sHTML .= stripslashes($Post->title);
        if($fTruncate)$sHTML .= '</a>';
        $sHTML .= '</h3>';
        $sHTML .= '<div class="originator">';
        $sURL = (property_exists($Post->creator, 'pictureUrl'))? empty($Post->creator->pictureUrl)? RDP_LIG_Utilities::mysteryPicUrl() : $Post->creator->pictureUrl : RDP_LIG_Utilities::mysteryPicUrl() ;
        $sHTML .= '<p class="poster-name">';
        $sHTML .= '<a href="' . $Post->creator->profileURL . '" target="_new" class="rdp-lig-member-info-link" postid="' . $Post->id . '" memberid="' . $Post->creator->id . '" title="Click to see this member&apos;s bio">';
        $sHTML .= $sFullName;
        $sHTML .= '</a>';
        $sHTML .= '</p>';
        if(property_exists($Post->creator, 'headline') && !empty($Post->creator->headline)) $sHTML .= '<p class="headline">' . $Post->creator->headline . '</p>';
        $sHTML .= '</div><!--end originator--><p class="user-details">';
        $sSummary = empty($Post->summary)? '' : stripslashes($Post->summary);
        if($fTruncate){
            $sSummary = RDP_LIG_Utilities::truncateString(nl2br ($sSummary), 250);
        }else{
            $sSummary = nl2br ($sSummary);
        }
        $sHTML .= preg_replace ( 
            "/(?<!a href=\")(?<!src=\")((http)+(s)?:\/\/[^<>\s]+)/i",
            "<a href=\"\\0\" target=\"_blank\">\\0</a>",
            $sSummary
        );        
        $sHTML .= '</p></div><!--end user-contributed-->';
        return $sHTML;
    }//getUserContributedSection

    static function getReferencedItemSection($Post){
        $sHTML = '<div class="referenced-item">';
        if(property_exists($Post->attachment, 'imageUrl') && !empty($Post->attachment->imageUrl))$sHTML .= '<img src="' . $Post->attachment->imageUrl . '" onerror="javascript:this.style.display=\'none\'" />';
        $sHTML .= '<div class="wrap"><h4 class="article-title">';
        $sHTML .= '<a href="' . $Post->attachment->contentUrl . '" target="_blank" alt="View details for this item">' . $Post->attachment->title . '</a>';
        $sHTML .= ' <span class="article-source">' . $Post->attachment->contentDomain . '</span>';
        $sHTML .= '</h4></div>';
        $sAttachmentSummary = (!empty($Post->attachment) && property_exists($Post->attachment, 'summary'))? $Post->attachment->summary : '' ;
        $sHTML .= '<p class="article-summary">' . stripslashes($sAttachmentSummary). '</p>';
        $sHTML .= '</div><!--end referenced-item-->';
        return $sHTML;
    }//getReferencedItemSection


}//RDP_LIG_Group


/* EOF */