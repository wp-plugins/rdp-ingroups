var $j=jQuery.noConflict();
// Use jQuery via $j(...)

$j(document).ready(rdp_lig_cache_buster_onReady);

function rdp_lig_cache_buster_onReady(){
    var cacheBuster = Math.random()*10000000000000000; 
    var key = jQuery.query.get("rdpingroupskey");
    if(key == '' && typeof rdp_lig != 'undefined' && typeof rdp_lig.key != 'undefined' ){
        key = rdp_lig.key;
    }
    $j("body a[href*='" + url('hostname', rdp_lig_ajax.site_url) + "']").not("[href*=rdpingroupskey]").each(function() {
        if($j( this ).hasClass('btnLGILogin'))return true;
        if($j( this ).hasClass('view-group-rss'))return true;    
        var sHREF = $j( this ).attr('href');
        if(typeof sHREF == 'undefined')return true;
        var baseURL = url('protocol', sHREF) + "://" + url('hostname', sHREF) + url('path', sHREF);

        var queryObject = jQuery.query.load(sHREF);   
        queryObject.SET('rdpingroupskey',key);  
        queryObject.SET('rdpingroupscb',cacheBuster);

        var params = queryObject.toString();
        $j( this ).attr('href',baseURL+params);                
    });

}//rdp_lig_cache_buster_onReady


