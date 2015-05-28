<?php if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); ?>
<?php

/**
 * Description of rdpLIGDiscussion
 *
 * @author Robert
 */
class RDP_LIG_Discussion {
    static function fetchContent(){
        $key = (isset($_POST['key']))? $_POST['key'] : '';
        $oDatapass = RDP_LIG_DATAPASS::get($key);
        $postID = (isset($_POST['wp_post_id']))? $_POST['wp_post_id'] : '';
        $oDatapass->wpPostID_set($postID);

        $gid = (isset($_POST['group_id']))? $_POST['group_id'] : $_POST['id']; 
        
        if ( false === ( $dataPass = get_transient($_POST['id']) ) ) {
            // It wasn't there, so regenerate the data and save the transient
            $discussionURL = "https://www.linkedin.com/grp/post/{$_POST['id']}";
            $html = rdp_file_get_html($discussionURL);        
            $dataPass = array();
            if(!$html){
                $dataPass['code'] = 503;
                $dataPass['message'] = 'Service Unavailable - Unable to retrieve group data';
                echo json_encode($dataPass);
                die();
            }        

            $Post = new stdClass;
            $Post->id = $_POST['id'];
            $Post->title = '';
            $Post->summary = '';
            $Post->creationTimestamp = '';
            $Post->hasComments = false;        


            $comments = $html->find('div.post-comments ul.disc-comment-list li.disc-comment');
            if($comments)$Post->hasComments = true;

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
                    if(key_exists('id', $output))$Creator->id = $output['id'];                    
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
                    if(key_exists('url', $output))$Attachment->contentUrl = rawurldecode($output['url']);
                    $Attachment->title = RDP_LIG_Utilities::entitiesPlain($anchor->plaintext);
                }

                $span = $ret->find('div.content span.source',0);
                if($span)$Attachment->contentDomain = $span->plaintext;

                $summary = $ret->find('div.content span.summary',0);
                if($summary)$Attachment->summary = RDP_LIG_Utilities::entitiesPlain($summary->plaintext);
                $Post->attachment = $Attachment;
            } 
            
            $dataPass = array(
                'html' => self::postToHTML($Post,$gid,$oDatapass,$_POST['is_open_group']),
                'comments_header' => self::renderCommentsHeader($Post,$oDatapass),
                'code' => 200,
                'message' => 'OK'
            );            
            
            set_transient( $Post->id, $dataPass, 15 * MINUTE_IN_SECONDS );
        }        
        
        echo json_encode($dataPass);
        die();
    }//fetchContent
    
    static function renderCommentsHeader($Post){
        $sHTML = '<div class="section-header"><h3>Comments</h3></div>';
        $sHTML .= '<div id="comments-wrapper-' . $Post->id . '" style="position:relative" class="comment-items">';
        $sHTML .= '<a class="rdp-lig-paging-link rdp-lig-paging-more rdp-lig-paging-more-middle show-more-items" rel="previous" style="display: none;"><span class="show-more-text">SHOW PREVIOUS COMMENTS</span></a>';
        $sHTML .= '<ul class="rdp-lig-comments-list"></ul>';
        $sHTML .= '</div><!-- commentItems -->';

       return $sHTML;

    }    

    static function postToHTML($Post,$gid,$Datapass,$isOpenGroup){
        $sFullName = $Post->creator->name;
        $sHTML = '<div id="anetItemSubject" class="discussion-item subject">';
        $sHTML .= '<div class="discussion-author">';
        $sHTML .= RDP_LIG_Group::getMiniprofileSection($Post, $sFullName);
        $sHTML .= '</div><!--end .discussion-author-->';

        $sHTML .= '<div class="discussion-content">';
        $sHTML .= '<div class="discussion-article">';
        $sAttachmentSummary = (!empty($Post->attachment) && property_exists($Post->attachment, 'summary'))? $Post->attachment->summary : '' ;
        $sPostSummary = (property_exists($Post, 'summary'))? $Post->summary : '' ;        
        if(empty($Post->attachment) || $sAttachmentSummary != $sPostSummary)$sHTML .= RDP_LIG_Group::getUserContributedSection($Post, $sFullName,$gid,$Datapass, false);
        if(!empty($Post->attachment)) $sHTML .= RDP_LIG_Group::getReferencedItemSection($Post);
        $sHTML .= '</div><!--end .discussion-article--></div><!--end .discussion-content-->';
        $sHTML .= '</div><!--end #anetItemSubject-->';
        $sHTML .= '<div id="itemActions" class="item-actions">';
        $sHTML .= RDP_LIG_Group::getItemActionsSection($Post, true,$isOpenGroup);
        $sHTML .= '</div>';
        return $sHTML;
    }//postToHTML


    static function fetchComments(){
        $key = (isset($_POST['key']))? $_POST['key'] : '';
        check_ajax_referer( 'rdp-lig-group-comment-'.$key, 'security' );
        $oDatapass = RDP_LIG_DATAPASS::get($key);
        $dataPass = array();
        
        $discussionURL = "https://www.linkedin.com/grp/post/{$_POST['id']}";
        $html = rdp_file_get_html($discussionURL);        

        if(!$html){
            $dataPass['code'] = 503;
            $dataPass['message'] = 'Service Unavailable - Unable to retrieve group data';
            echo json_encode($dataPass);
            die();
        }  
        
        $dataPass['code'] = 200;

        $JSON = array();

        foreach($html->find('div.post-comments ul.disc-comment-list li.disc-comment') as $comment){
            $oComment = new stdClass;
            $oComment->creationTimestamp = '';
            $oComment->id = '';
            $oComment->text = '';

            
            // comment timestamp
            $ret = $comment->find('div.comment-date',0);
            if($ret)$oComment->creationTimestamp = $ret->plaintext;
            
            // commnet body - re-write links to point to original source
            $ret = $comment->find('p.comment-content',0);
            foreach($ret->find('a') as $anchor){
                $url = RDP_LIG_Utilities::entitiesPlain($anchor->href);
                parse_str(parse_url($url, PHP_URL_QUERY), $output);
                if(key_exists('url', $output))$anchor->href = rawurldecode($output['url']);
             }
            if($ret)$oComment->text = $ret->plaintext;
            
            
            // comment creator
            $Creator = new stdClass;
            $Creator->id = '';
            $Creator->pictureUrl = '';
            $Creator->name = '';
            $Creator->profileURL = ''; 
            
            $anchor = $comment->find('a.entity-image',0);
            if($anchor){
                $Creator->profileURL = $anchor->href;
                $url = RDP_LIG_Utilities::entitiesPlain($anchor->href);
                parse_str(parse_url($url, PHP_URL_QUERY), $output);
                if(key_exists('id', $output))$Creator->id = $output['id'];
                $img = $anchor->find('img',0);
                if($img)$Creator->pictureUrl = $img->src;
            }
            
            $name = $comment->find('p.entity-name a',0);
            if($name)$Creator->name = $name->plaintext;
            
            $oComment->creator = $Creator;
            
            $JSON[] = $oComment;
            
        }
        $dataPass['comments_returned'] = count($JSON);
        if($dataPass['comments_returned'] == 0){
            $dataPass['message'] = '<h3 class="no-comments-yet rdp-lig-comments-message"><a style="color: blue;text-decoration: underline;" href="' . $discussionURL . '" target="_new">Be the first to comment!</a></h3>';
        }else{
            $dataPass['html'] = self::commentsToHTML($JSON,$oDatapass);
            $dataPass['message'] = '<h3 class="no-comments-yet rdp-lig-comments-message">To continue this discussion, <a style="color: blue;text-decoration: underline;" href="' . $discussionURL . '" target="_new">visit LinkedIn</a></h3>';
        }

        echo json_encode($dataPass);
        die();

    }//fetchComments


    private static function commentsToHTML($Comments,$Datapass){
        $sHTML = '';
        $nCount = count($Comments);
        $postID = (isset($_POST['wp_post_id']))? $_POST['wp_post_id'] : '';
        $Datapass->wpPostID_set($postID);   
        for($i = 0; $i < $nCount; $i++){
            $oComment = $Comments[$i];
            $sHTML .= self::createCommentItem($oComment,$Datapass);
        }

        return $sHTML;
    }//commentsToHTML

    private static function createCommentItem($Comment,$Datapass){
        $sCommentText = trim(nl2br ($Comment->text));
        if(strlen(trim($sCommentText)) == 0) return '';
        $sCommentText = preg_replace("/(<br\/>)+/", "", $sCommentText);
        $sCommentID = $Comment->id;
        $sPictureUrl = empty($Comment->creator->pictureUrl)? RDP_LIG_Utilities::mysteryPicUrl() : $Comment->creator->pictureUrl ;
        $sCommentCreatorFullName = $Comment->creator->name;

        $sCommentItem = '<li class="comment-item" id="' . $sCommentID . '">';

        $sCommentItem .= '<div class="comment-entity">';

        $sCommentItem .= '<a href="' . $Comment->creator->profileURL . '" target="_new" class="rdp-lig-member-info-link" commentid="' . $sCommentID . '">';
        $sCommentItem .= '<img src="' . $sPictureUrl . '" alt="' . $sCommentCreatorFullName . '" width="60" height="60" class="photo" />';
        $sCommentItem .= '</a>';
        $sCommentItem .= '</div><!--end comment-entity-->';
        $sCommentItem .= '<div class="comment-content show-contributor-badge">';

        $sCommentItem .= '<p class="commenter">';
        $sCommentItem .= '<a href="' . $Comment->creator->profileURL . '" target="_new" class="rdp-lig-member-info-link" commentid="' . $sCommentID . '" title="See this member&apos;s bio" class="commenter">';
        $sCommentItem .= $sCommentCreatorFullName;
        $sCommentItem .= '</a>';
        $sCommentItem .= '</p>';
        if(property_exists($Comment->creator, 'headline'))$sCommentItem .= '<p class="commenter-headline">' . $Comment->creator->headline . '</p>';
        
        $sCommentText = preg_replace ( 
            "/(?<!a href=\")(?<!src=\")((http)+(s)?:\/\/[^<>\s]+)/i",
            "<a href=\"\\0\" target=\"_blank\">\\0</a>",
            $sCommentText
        );        

        $sCommentItem .= '<p class="comment-body">' . $sCommentText . '</p>';
        $sCommentItem .= '</div><!--end comment-content -->';
        $sCommentItem .= '</li><!--end comment-item-->';
        return $sCommentItem;

    }//createCommentItem




}//RDP_LIG_Discussion

/* EOF */
