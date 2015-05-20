<?php if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); ?>
<?php

class RDP_LIG_GROUP_PROFILE {
    private $_id = 0;
    private $_status_code = 200;
    private $_data_filled = false;
    private $_data_saved = false;    
    private $_has_errors = false;
    private $_last_error = '';
    private $_numMembers = 0;
    private $_memberCountToString = '';
    private $_name = '';
    private $_largeLogoUrl = '';
    private $_isOpenToNonMembers = false;
    public static $_key_prefix = 'rdp-lig-group-profile-';
    
    private function __construct($id = 0,$props = null){
        if(!is_numeric($id)){
            $this->_status_code = 406;
            $this->_last_error = 'Not Acceptable - Invalid group id given.';
            $this->_has_errors = true;
            return;
        }
        $this->_id = $id;
        
        if(!$props)return ;
        $oProps = get_object_vars($this); 
        foreach ($oProps as $key => $value ) {
            $newvalue = (isset($props[$key])) ? $props[$key] : null;
            if ($newvalue === null) continue;
            if ($newvalue === "true") $newvalue = true;
            if ($newvalue === "false") $newvalue = false;
            $this->$key = $newvalue;
        }

        $this->_data_filled = true;       
       
    }//__construct
    
    public static function get($id) {
        $key = self::$_key_prefix . $id;
        $options = get_transient($key);
        return new self($id,$options);
    }    
    
    public static function getNew($id) {
        return new self($id);
    } 
    
    public function save() {
        $this->_data_saved = false;
        if($this->_has_errors) return false;
        if(!$this->_data_filled) return false;
        $key = self::$_key_prefix . $this->_id;
        $this->_data_saved = set_transient($key, get_object_vars($this), HOUR_IN_SECONDS);
    }      
    
    public function load() {
        if(!is_numeric($this->_id)) return;
        $resource = "https://www.linkedin.com/groups?newItemsAbbr=&gid={$this->_id}";
        $html = rdp_file_get_html($resource);

        if(!$html){
            $this->_status_code = 503;
            $this->_last_error = 'Service Unavailable - Unable to retrieve group data';
            $this->_has_errors = true;
            return;
        }
        $this->_data_filled = true;
        $this->parse($html);
    }//load
    
    
    
    
    private function parse($html){
        $oMemberCount = $html->find('div.header .right-entity .member-count',0);
        if(!$oMemberCount){
           $this->_last_error = 'Unable to retrieve member count';
           $this->_has_errors = true;
           return;
       }
       $this->_memberCountToString = $oMemberCount->plaintext;
       $this->_numMembers = filter_var($oMemberCount->plaintext, FILTER_SANITIZE_NUMBER_INT); 
       
       
       $oGroupName = $html->find('h1.group-name span',0);
        if(!$oGroupName){
           $this->_last_error = 'Unable to retrieve group name';
           $this->_has_errors = true;
           return;
       }       
       $this->_name = $oGroupName->plaintext;
       
       $oGroupPrivate = $html->find('div.left-entity h1.private',0);
       $this->_isOpenToNonMembers = empty($oGroupPrivate);
       
       $oGroupLogoURL = $html->find('div.header a.image-wrapper img',0);
        if(!$oGroupLogoURL){
           $this->_last_error = 'Unable to retrieve group logo';
           $this->_has_errors = true;
           return;
       }       
       $this->_largeLogoUrl = $oGroupLogoURL->src;       
    }//parse
    
    public function dataFilled(){
        $filled = (isset($this->_data_filled))? $this->_data_filled : false;
        return $filled;        
    }
    
    public function hasErrors(){
        $errors = (isset($this->_has_errors))? $this->_has_errors : false;
        return $errors;        
    } 
    
    public function statusCode(){
        $code = (isset($this->_status_code))? $this->_status_code : 0 ;
        return $code;        
    } 
    
    public function lastError(){
        $text = (isset($this->_last_error))? $this->_last_error : '';
        return $text;
    }      
    
    public function id(){
        $id = (isset($this->_id))? $this->_id : 0 ;
        return $id;        
    }
    
    public function numMembers(){
        $num = (isset($this->_numMembers))? $this->_numMembers : 0;
        return $num;
    }   
    
    public function numMembersToString(){
        $text = (isset($this->_memberCountToString))? $this->_memberCountToString : '';
        return $text;
    }   
    
    public function largeLogoUrl() {
        $url = (isset($this->_largeLogoUrl))? $this->_largeLogoUrl : '';
        return $url;        
    }

    public function name(){
        $text = (isset($this->_name))? $this->_name : '';
        return $text;
    }    

    public function isOpenToNonMembers(){
        $f = (isset($this->_isOpenToNonMembers))? $this->_isOpenToNonMembers : false;
        return $f;        
    }
    
}//rdpLIGGroupProfile

/*  EOF */
