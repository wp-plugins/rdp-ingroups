<?php
/**
 * Custom RSS feed intended for use with MailChimp newsletter template
 */
?>
<?php
header("Content-Type: application/xml; charset=ISO-8859-1");


class RDP_LIG_RSS {
    public $RSS = '';
    private $_id = '';
    private $_baseLink = '';
    
    function __construct(){
        $this->RSS = '<?xml version="1.0" encoding="ISO-8859-1" ?>';
        $this->_id = (isset($_REQUEST['id']))?strip_tags( $_REQUEST['id']  ):0;
        $this->_baseLink  = (isset($_REQUEST['link']))?$_REQUEST['link']:'';
        if(empty($this->_id) || !is_numeric($this->_id)){
            $this->throwException('Invalid ID Supplied');
            return;
        }
        if(empty($this->_baseLink) || !filter_var($this->_baseLink, FILTER_VALIDATE_URL)){
            $this->throwException('Invalid Source Link Supplied');            
            return;
        }        
        $sLocation = '../bl/simple_html_dom.php';
        require_once $sLocation;
        
        $url = "https://www.linkedin.com/groups?newItemsAbbr=&gid={$this->_id}&trk=groups_most_recent-h-srp";
        $html = rdp_file_get_html($url);

        if(!$html){
            $this->throwException('Bad Request');
            return;
        }else{
            $body = $html->find('div#body',0);
            if(!$body){
                $this->throwException('Bad Request');
                return;
            }
            
            foreach($body->find('script') as $script){
                $script->outertext = '';
            }
            
            $this->RSS .= '<rss xmlns:media="http://search.yahoo.com/mrss/" version="2.0"><channel>';
            $this->createChannelDetails($body);
            $this->createFeedItems($body);
            $this->RSS .= '</channel></rss>';            
        }

    }//__construct
    
    private function throwException($msg){
        $this->RSS .= "<error>{$msg}</error>";
    }
    
    private function createChannelDetails($html){
        
        $ret = $html->find('h1.group-name span',0);
        $title = RDP_LIG_RSS::entitiesPlain($ret->innertext);
        $this->RSS .= "<title><![CDATA[{$title}]]></title>";
        $this->RSS .= "<link><![CDATA[{$this->_baseLink}]]></link>";
        $this->RSS .= "<description>Recent discussions from LinkedIn.</description>";
        $this->RSS .= "<ttl>12</ttl>";
        $ret = $html->find('.header img.image',0);
        $url = $ret->src;
        $this->RSS .= "<image>";
        $this->RSS .= "<url><![CDATA[{$url}]]></url>";        
        $this->RSS .= "<title><![CDATA[{$title}]]></title>";   
        $this->RSS .= "<link><![CDATA[{$this->_baseLink}]]></link>";        
        $this->RSS .= "</image>";  
    }//createChannelDetails
    
    private function createFeedItems($html){
        
        $description = <<<EOD
<div class="entity">
    <p style="float: left;margin: 0 3px 0 0" class="cover-image-container">
        <a href="%%PostLink%%" class="poster-photo-link">
            <img src="%%Image%%" alt="%%Poster%%" width="40" height="40" class="groups photo poster-photo">			
        </a>
    </p><div class="user-contributed" style="min-height: 55px;">
 <p style="margin: 0 0 3px;"  class="groups"><span class="new-miniprofile-container"><a href="%%PostLink%%" class="poster">%%Poster%%</a></span> <a href="%%PostLink%%" class="discussion-title">%%Title%%</a> </p>
 </div> 
 </div>
EOD;
        
        foreach($html->find('#content .discussion-item') as $element){
            $this->RSS .= '<item>';

            $X = 'data-li-item_id';
            $id = $element->$X;
            $discussionID = "{$this->_id}-{$id}";
            $sPostLink = $this->_baseLink . "&rdpingroupspostid={$discussionID}";

            $ret = $element->find('a.discussion-title',0);
            $sTitle = '';
            if($ret){
                $sTitle = RDP_LIG_RSS::entitiesPlain($ret->plaintext);
              
            }

            $ret = $element->find('div.entity',0);
            $sImgSrc = '';
            $sPoster = '';
            if($ret){
                $img = $ret->find('img.photo',0);
                if($img){
                    $sImgSrc = $img->src;
                    $sPoster = $img->alt;
                }
            }
            
            $ret = $element->find('div.discussion-content li.timestamp',0);
            if($ret){
                $sTimestamp = trim($ret->innertext);
            }
            
            $sFullTitle = str_replace (array ( 
            '%%Image%%', 
            '%%Title%%' , 
            '%%Poster%%',
            '%%PostLink%%',
            '%%Timestamp%%') , 
            array ( 
            $sImgSrc, 
            $sTitle, 
            $sPoster,
            $sPostLink,
            $sTimestamp), 
            $description );            
            
            
            $this->RSS .= "<title><![CDATA[]]></title>";  
            
            $ret = $element->find('div.discussion-content div.user-contributed',0);
            $sDescriptionItem = '';
            if($ret){
                foreach($ret->find('p') as $paragraph) {
                    $sDescriptionItem .= '<p style="margin: 0 0 6px;">';
                    $sDescriptionItem .= $paragraph->innertext;
                    $sDescriptionItem .= '</p>';
                }
                
                $sDescriptionItem .= '<p class="timestamp last">' . $sTimestamp . '</p>';
            }              
            
            $this->RSS .= "<description><![CDATA[{$sFullTitle}{$sDescriptionItem}]]></description>";
            $this->RSS .= "<link><![CDATA[{$sPostLink}]]></link>";
            $this->RSS .= "<guid isPermaLink='true'><![CDATA[{$sPostLink}]]></guid>";
            $this->RSS .= '</item>';
        }
  
    }//createFeedItems
    
    static function xmlEntities($string) { 
       return str_replace ( array ( '&', '"', "'", '<', '>', '�' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), $string ); 
    } 
    
    static function entitiesPlain($string){
        return str_replace ( array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&quest;',  '&#39;' ), array ( '&', '"', "'", '<', '>', '?', "'" ), $string ); 
    }
}//RDP_LIG_RSS
$oRSS = new RDP_LIG_RSS();
echo $oRSS->RSS;
die();
/* EOF */
