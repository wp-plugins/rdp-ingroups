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
        foreach($html->find('#content .discussion-item') as $element){
            $this->RSS .= '<item>';
            
            $X = 'data-li-item_type';
            $element->$X = null;
            $X = 'data-li-item_id';
            $id = $element->$X;
            $element->$X = null;            
            $discussionID = "{$this->_id}-{$id}";
            $link = $this->_baseLink . "&rdpingroupspostid={$discussionID}";
            
            foreach ($element->find('a') as $anchor) {
                $anchor->href = $link;
            }
            
            $miniContainer = $element->find('span.new-miniprofile-container',0);
            if($miniContainer){
                $miniContainer->class = 'new-miniprofile-container';
                $X = 'data-li-url';
                $miniContainer->$X = null;
                $X = 'data-tracking';
                $miniContainer->$X = null;
                $X = 'data-li-tl';
                $miniContainer->$X = null;                
            }

            $ret = $element->find('a.discussion-title',0);
            if($ret){
                $title = RDP_LIG_RSS::entitiesPlain($ret->plaintext);
                $this->RSS .= "<title><![CDATA[{$title}]]></title>";                
            }

            $description = '';
            $ret = $element->find('div.entity',0);
            if($ret){
                $img = $ret->find('img.photo',0);
                if($img)$ret->innertext = $img->outertext;
                $description .= RDP_LIG_RSS::entitiesPlain($ret->outertext);
            }
            
            $ret = $element->find('div.discussion-content',0);
            if($ret){
                $description .= '<div>';
                $description .= RDP_LIG_RSS::entitiesPlain($ret->innertext); 
                $description .= '</div>';
            }

            $this->RSS .= "<description><![CDATA[{$description}]]></description>";
            $this->RSS .= "<link><![CDATA[{$link}]]></link>";
            $this->RSS .= "<guid isPermaLink='true'><![CDATA[{$link}]]></guid>";
            $ret = $element->find('.entity img.photo',0);
            if($ret){
                $img = $ret->src;
                $title = $ret->alt;
                $this->RSS .= "<media:content url='{$img}' type='image/jpeg' expression='full'>";
                $this->RSS .= "<media:title type='plain'><![CDATA[{$title}]]></media:title>";
                $this->RSS .= "</media:content>";                
            }
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
