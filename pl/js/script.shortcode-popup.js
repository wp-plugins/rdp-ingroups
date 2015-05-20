var $j=jQuery.noConflict();
// Use jQuery via $j(...)

$j(document).ready(rdp_lig_shortcode_popup_onReady);

function rdp_lig_shortcode_popup_onReady(){
    $j('.wp-admin').on( "click", '#btnInsertLIGShortcode' , rdp_lig_insertShortcode ); 
    $j('.wp-admin').on( "change", '#ddLIGShortcode' , rdp_lig_ddLIGShortcode_onChange ); 

    
}//rdp_lig_shortcode_popup_onReady

function rdp_lig_insertShortcode(){
    var value = $j('#ddLIGShortcode').val();
    if(value == '*')return;
    var win = window.dialogArguments || opener || parent || top;
    var code = '';
    switch(value) {
        case 'login':
            win.send_to_editor('[rdp-ingroups-login]' );
            break;
        case 'Member Count':
            code = '[rdp-ingroups-member-count id=' + $j('#txtLIGID').val();
            if($j('#txtLIGLink').val() != '')code += ' link=' + $j('#txtLIGLink').val();
            if($j('#chkLIGNewWindow').prop( "checked")) code += ' new';
            code += ']';
            win.send_to_editor(code);
            break;
        case 'Discussion':
            if($j('#txtLIGLink').val().indexOf('linkedin.com/groups/') >= 0){
                var url = $j('#txtLIGLink').val();
                var pieces = url.substr(url.lastIndexOf('-')+1).split('.');
                code = "[rdp-ingroups-group id='"+pieces[0]+"' discussion_id='g-"+pieces[0]+ '-S-' +pieces[2]+"' discussion_url='"+url+"']";
                win.send_to_editor(code);
            }
            break;
        default:
            code = '[rdp-ingroups-' + value.toLowerCase();
            if($j('#txtLIGID').val() != '') code += ' id=' + $j('#txtLIGID').val();
            if($j('#txtLIGDiscussionID').val() != '') code += " discussion_id='g-" + $j('#txtLIGID').val() + '-S-' + $j('#txtLIGDiscussionID').val() + "'";
            code += ']';
            win.send_to_editor(code);
            
            break;
    }
}//rdp_lig_insertShortcode

function rdp_lig_ddLIGShortcode_onChange(){
    var value = $j('#ddLIGShortcode').val();
    $j('#txtLIGID').val('');
    $j('#txtLIGDiscussionID').val('');        
    $j('#txtLIGLink').val('');
    $j('#txtLIGID-wrap').hide();
    $j('#txtLIGLink-wrap label').html('Link:');
    $j('#txtLIGLink-wrap').hide();
    $j('#ddLIGTemplate-wrap').hide();
    $j('#chkLIGNewWindow').prop( "checked", false );  
    $j('#chkLIGNewWindow-wrap').hide();    
    $j('#txtLIGDiscussionID-wrap').hide(); 
    
    switch(value) {
        case 'Discussion':
            $j('#txtLIGLink-wrap label').html('Discussion URL:');
            $j('#txtLIGLink-wrap').show();
            break;        
        case 'Group':
            $j('#txtLIGID-wrap').show();
            $j('#txtLIGDiscussionID-wrap').show();    
            break;
        case 'Member Count':
            $j('#txtLIGID-wrap label').html('Group ID:');
            $j('#txtLIGID-wrap').show();
            $j('#txtLIGLink-wrap').show();
            $j('#chkLIGNewWindow-wrap').show();            
            break;
        case 'My Groups':
            $j('#ddLIGTemplate-wrap').show();
            $j('#chkLIGNewWindow-wrap').show();
            break;
        default:
            break;
    }
    
}//rdp_lig_ddLIGShortcode_onChange