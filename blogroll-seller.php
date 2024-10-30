<?php
/*
Plugin Name: Blogroll Seller
Plugin URI: http://www.crivion.com
Description: This plugin allows you to setup an end date when adding a blogroll link. When the link will expire it will automatically be removed/excluded from the blogroll links so you don't have to manually delete them.
Version: 1.0
Author: crivion
Author URI: http://www.crivion.com
License: GPLv2
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/

//CREATE THE METABOX TO ADD EXPIRY DATE INPUTBOX WHEN ADDING LINK
function crv_meta_box()
{
    $theDate = isset($_REQUEST['link_id']) ? get_option('crv_link_expiry_date_id_'. (int) $_REQUEST['link_id']) : date("m/d/Y"); 
    print 'Format (mm/dd/yyyy) : <input type="text" name="crv_link_expiry_date" value="'.$theDate.'" /><br/><br/><div align="right">Powered by <a href="http://www.crivion.com">crivion</a></div>';    
}

//INSERT LINK EXPIRY TIME AS WP_OPTION
function crv_on_link_insert()
{
    global $wpdb;
    $query = $wpdb->get_row("select max(link_id) as id from wp_links");
    $insertID = $query->id;    
    add_option('crv_link_expiry_date_id_'.$insertID, $_POST['crv_link_expiry_date']);    
}

//UPDATE LINKY EXPIRY TIME ON EDIT
function crv_on_link_update()
{
    $insertID = (int) $_REQUEST['link_id'];
    delete_option('crv_link_expiry_date_id_'.$insertID);
    add_option('crv_link_expiry_date_id_'.$insertID, $_POST['crv_link_expiry_date']);
}

//REMOVE OPTION ON DELETE
function crv_on_link_removal() {
    $insertID = (int) $_REQUEST['link_id'];
    delete_option('crv_link_expiry_date_id_'.$insertID);
}


//FILTER LINKS TO SHOW ONLY ACTIVE ONES AND REMOVE EXPIRED ONES
function crv_remove_expired_links()
{
    global $wpdb;    
    $rs = $wpdb->get_results("SELECT * FROM  `wp_options` WHERE  `option_name`  
                REGEXP  'crv_link_expiry_date_id_.*'", 'OBJECT');
    $ids = array();
    #return var_dump($rs);
    foreach($rs as $row) 
    {      
        if(strtotime($row->option_value) < time()) {
           $exp = explode("_", $row->option_name);
           $ids[] = end($exp);
        }
    }
    #return var_dump($ids);
    return get_bookmarks('exclude='.implode(",", $ids));    
}

//ACTUALLY BUILD THE BLOGROLL
function crv_build_blogroll()
{  
    #return var_dump(crv_remove_expired_links());
    $return = '<h3>Blogroll</h3>';
    $return .= "<ul>";
    foreach(crv_remove_expired_links() as $link)
    {
        $return .= "<li>";
        $return .= "<a href=\"$link->link_url\" target=_\"_blank\">$link->link_name</a>";
        $return .= "</li>";
    }
    $return .= "<ul>";
    return $return;
    #return var_dump();
    #get_bookmarks();    
    #return preg_replace('/<a href="http\:\/\/te2">(.*?)<\/a>/', '$1', $output);
    #return "<a href='mata'>tactu</a><br/><a href='b'>bunictu</a>";
}

add_filter('wp_list_bookmarks', 'crv_build_blogroll');

//DO THE INIT STUFF
function crv_meta_init()
{
    #add_filter('wp_list_bookmarks', 'crv_build_blogroll');
    add_meta_box('crv_metabox_id', 'Link Expiry Date', 'crv_meta_box', 'link', 'side', 'high');
    add_action('add_link', 'crv_on_link_insert');
    add_action('edit_link', 'crv_on_link_update');
    add_action('delete_link', 'crv_on_link_removal');
}
add_action('admin_init','crv_meta_init');
?>