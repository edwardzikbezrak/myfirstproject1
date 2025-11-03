<?php
/**************************************************************************
 *                                                                        *
 *    4images - A Web Based Image Gallery Management System               *
 *    ----------------------------------------------------------------    *
 *                                                                        *
 *             File: top.php                                              *
 *        Copyright: (C) 2002-2010 Jan Sorgalla                           *
 *            Email: jan@4homepages.de                                    * 
 *              Web: http://www.4homepages.de                             * 
 *    Scriptversion: 1.7.8                                                *
 *                                                                        *
 *    Never released without support from: Nicky (http://www.nicky.net)   *
 *                                                                        *
 **************************************************************************
 *                                                                        *
 *    Dieses Script ist KEINE Freeware. Bitte lesen Sie die Lizenz-       *
 *    bedingungen (Lizenz.txt) für weitere Informationen.                 *
 *    ---------------------------------------------------------------     *
 *    This script is NOT freeware! Please read the Copyright Notice       *
 *    (Licence.txt) for further information.                              *
 *                                                                        *
 *************************************************************************/

$main_template = 'top';

define('GET_CACHES', 1);
define('ROOT_PATH', './');
define('MAIN_SCRIPT', __FILE__);
include(ROOT_PATH.'global.php');
require(ROOT_PATH.'includes/sessions.php');
$user_access = get_permission();
include(ROOT_PATH.'includes/page_header.php');

$cache_id = create_cache_id(
  'page.top',
  array(
    $user_info[$user_table_fields['user_id']],
    $cat_id,
    $config['template_dir'],
    $config['language_dir']
  )
);

if (!$cache_page_top || !$content = get_cache_file($cache_id)) {
if ($cache_page_top) {
  // Always append session id if cache is enabled
  $old_session_mode = $site_sess->mode;
  $site_sess->mode = 'get';
}

ob_start();

$cat_match_sql = ($cat_id && check_permission("auth_viewcat", $cat_id)) ? "AND i.cat_id = '$cat_id' " : "";
$register_array = array();

$cat_id_sql = get_auth_cat_sql("auth_viewcat", "NOTIN");

// Rating
$sql = "SELECT i.image_id, i.user_id, i.cat_id, i.image_name, i.image_thumb_file, i.image_rating, i.image_votes, c.cat_name".get_user_table_field(", u.", "user_name")."
        FROM (".IMAGES_TABLE." i, ".CATEGORIES_TABLE." c)
        LEFT JOIN ".USERS_TABLE." u ON (".get_user_table_field("u.", "user_id")." = i.user_id)
        WHERE i.image_active = 1 AND i.cat_id NOT IN ($cat_id_sql) AND i.cat_id = c.cat_id
        $cat_match_sql
        ORDER BY i.image_rating DESC, i.image_name ASC
        LIMIT 20";
$result = $site_db->query($sql);
$top_list = array();
$i = 1;
while ($row = $site_db->fetch_array($result)) {
  $top_list[$i] = $row;
  $i++;
}
$site_db->free_result();

for ($i = 1; $i <= 20; $i++) {
  if (isset($top_list[$i])) {
    $register_array['image_rating_'.$i] = (check_permission("auth_viewimage", $top_list[$i]['cat_id'])) ? "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\">".format_text($top_list[$i]['image_name'], 2)."</a>" : format_text($top_list[$i]['image_name'], 2);
    $register_array['image_rating_openwindow_'.$i] = (check_permission("auth_viewimage", $top_list[$i]['cat_id'])) ? "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\" onclick=\"opendetailwindow()\" target=\"detailwindow\">".format_text($top_list[$i]['image_name'])."</a>" : format_text($top_list[$i]['image_name']);
    if (isset($top_list[$i][$user_table_fields['user_name']]) && $top_list[$i]['user_id'] != GUEST) {
      $user_profile_link = (!empty($url_show_profile)) ? preg_replace("/{user_id}/", $top_list[$i]['user_id'], $url_show_profile) : ROOT_PATH."member.php?action=showprofile&amp;".URL_USER_ID."=".$top_list[$i]['user_id'];
      $register_array['image_rating_user_'.$i] = "<a href=\"".$site_sess->url($user_profile_link)."\">".format_text($top_list[$i][$user_table_fields['user_name']])."</a>";
    }
    else {
      $register_array['image_rating_user_'.$i] = $lang['userlevel_guest'];
    }
    $register_array['image_rating_cat_'.$i] = "<a href=\"".$site_sess->url(ROOT_PATH."categories.php?".URL_CAT_ID."=".$top_list[$i]['cat_id'])."\">".format_text($top_list[$i]['cat_name'])."</a>";
    $register_array['image_rating_number_'.$i] = "<b>".$top_list[$i]['image_rating']."</b> (".$top_list[$i]['image_votes']." ".$lang['votes'].")";



	

$register_array['image_rating_thumb_'.$i] = "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\"><img src=\"data/thumbnails/".$top_list[$i]['cat_id']."/".$top_list[$i]['image_thumb_file']."\" class=\"miniaturka\" width=\"220\" height=\"160\" border=\"0\" alt=\"{image_name}\" />"; 




  }
  else {
    $register_array['image_rating_'.$i] = "--";
    $register_array['image_rating_user_'.$i] = "--";
    $register_array['image_rating_cat_'.$i] = "--";
    $register_array['image_rating_number_'.$i] = "--";


$register_array['image_rating_thumb_'.$i] = "--";



  }
}

// Votes
$sql = "SELECT i.image_id, i.user_id, i.cat_id, i.image_name, i.image_thumb_file, i.image_rating, i.image_votes, c.cat_name".get_user_table_field(", u.", "user_name")."
        FROM (".IMAGES_TABLE." i, ".CATEGORIES_TABLE." c)
        LEFT JOIN ".USERS_TABLE." u ON (".get_user_table_field("u.", "user_id")." = i.user_id)
        WHERE i.image_active = 1 AND i.cat_id NOT IN ($cat_id_sql) AND i.cat_id = c.cat_id
        $cat_match_sql
        ORDER BY i.image_votes DESC, i.image_name ASC
        LIMIT 30";
$result = $site_db->query($sql);
$top_list = array();
$i = 1;
while ($row = $site_db->fetch_array($result)) {
  $top_list[$i] = $row;
  $i++;
}
$site_db->free_result();

for ($i = 1; $i <= 20; $i++) {
  if (isset($top_list[$i])) {
    $register_array['image_votes_'.$i] = (check_permission("auth_viewimage", $top_list[$i]['cat_id'])) ? "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\">".format_text($top_list[$i]['image_name'])."</a>" : format_text($top_list[$i]['image_name']);
    $register_array['image_votes_openwindow_'.$i] = (check_permission("auth_viewimage", $top_list[$i]['cat_id'])) ? "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\" onclick=\"opendetailwindow()\" target=\"detailwindow\">".format_text($top_list[$i]['image_name'])."</a>" : format_text($top_list[$i]['image_name']);
    if (isset($top_list[$i][$user_table_fields['user_name']]) && $top_list[$i]['user_id'] != GUEST) {
      $user_profile_link = (!empty($url_show_profile)) ? preg_replace("/{user_id}/", $top_list[$i]['user_id'], $url_show_profile) : ROOT_PATH."member.php?action=showprofile&amp;".URL_USER_ID."=".$top_list[$i]['user_id'];
      $register_array['image_votes_user_'.$i] = "<a href=\"".$site_sess->url($user_profile_link)."\">".format_text($top_list[$i][$user_table_fields['user_name']])."</a>";
    }
    else {
      $register_array['image_votes_user_'.$i] = $lang['userlevel_guest'];
    }
    $register_array['image_votes_cat_'.$i] = "<a href=\"".$site_sess->url(ROOT_PATH."categories.php?".URL_CAT_ID."=".$top_list[$i]['cat_id'])."\">".format_text($top_list[$i]['cat_name'])."</a>";
    $register_array['image_votes_number_'.$i] = "<b>".$top_list[$i]['image_rating']."</b> (".$top_list[$i]['image_votes']." ".$lang['votes'].")";



$register_array['image_votes_thumb_'.$i] = "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\"><img src=\"data/thumbnails/".$top_list[$i]['cat_id']."/".$top_list[$i]['image_thumb_file']."\" class=\"miniaturka\" width=\"220\" height=\"160\" border=\"0\" alt=\"{image_name}\" />"; 




  }
  else {
    $register_array['image_votes_'.$i] = "--";
    $register_array['image_votes_user_'.$i] = "--";
    $register_array['image_votes_cat_'.$i] = "--";
    $register_array['image_votes_number_'.$i] = "--";


$register_array['image_votes_thumb_'.$i] = "--";


  }
}

// Hits
$sql = "SELECT i.image_id, i.user_id, i.cat_id, i.image_name, i.image_thumb_file, i.image_hits, c.cat_name".get_user_table_field(", u.", "user_name")."
        FROM (".IMAGES_TABLE." i, ".CATEGORIES_TABLE." c)
        LEFT JOIN ".USERS_TABLE." u ON (".get_user_table_field("u.", "user_id")." = i.user_id)
        WHERE i.image_active = 1 AND i.cat_id NOT IN ($cat_id_sql) AND i.cat_id = c.cat_id
        $cat_match_sql
        ORDER BY i.image_hits DESC, i.image_name ASC
        LIMIT 40";
$result = $site_db->query($sql);
$top_list = array();
$i = 1;
while ($row = $site_db->fetch_array($result)) {
  $top_list[$i] = $row;
  $i++;
}
$site_db->free_result();

for ($i = 1; $i <= 40; $i++) {
  if (isset($top_list[$i])) {
    $register_array['image_hits_'.$i] = (check_permission("auth_viewimage", $top_list[$i]['cat_id'])) ? "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\">".format_text($top_list[$i]['image_name'])."</a>" : format_text($top_list[$i]['image_name']);
    $register_array['image_hits_openwindow_'.$i] = (check_permission("auth_viewimage", $top_list[$i]['cat_id'])) ? "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\" onclick=\"opendetailwindow()\" target=\"detailwindow\">".format_text($top_list[$i]['image_name'])."</a>" : format_text($top_list[$i]['image_name']);
    if (isset($top_list[$i][$user_table_fields['user_name']]) && $top_list[$i]['user_id'] != GUEST) {
      $user_profile_link = (!empty($url_show_profile)) ? preg_replace("/{user_id}/", $top_list[$i]['user_id'], $url_show_profile) : ROOT_PATH."member.php?action=showprofile&amp;".URL_USER_ID."=".$top_list[$i]['user_id'];
      $register_array['image_hits_user_'.$i] = "<a href=\"".$site_sess->url($user_profile_link)."\">".format_text($top_list[$i][$user_table_fields['user_name']])."</a>";
    }
    else {
      $register_array['image_hits_user_'.$i] = $lang['userlevel_guest'];
    }
    $register_array['image_hits_cat_'.$i] = "<a href=\"".$site_sess->url(ROOT_PATH."categories.php?".URL_CAT_ID."=".$top_list[$i]['cat_id'])."\">".format_text($top_list[$i]['cat_name'])."</a>";
    $register_array['image_hits_number_'.$i] = "<b>".$top_list[$i]['image_hits']."</b>";



$register_array['image_hits_thumb_'.$i] = "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\"><img src=\"data/thumbnails/".$top_list[$i]['cat_id']."/".$top_list[$i]['image_thumb_file']."\" class=\"miniaturka\" width=\"220\" height=\"160\" border=\"0\" alt=\"{image_name}\" />"; 





  }
  else {
    $register_array['image_hits_'.$i] = "--";
    $register_array['image_hits_user_'.$i] = "--";
    $register_array['image_hits_cat_'.$i] = "--";
    $register_array['image_hits_number_'.$i] = "--";


$register_array['image_hits_thumb_'.$i] = "--";


  }
}

// Downloads
$sql = "SELECT i.image_id, i.user_id, i.cat_id, i.image_name, i.image_thumb_file, i.image_downloads, c.cat_name".get_user_table_field(", u.", "user_name")."
        FROM (".IMAGES_TABLE." i, ".CATEGORIES_TABLE." c)
        LEFT JOIN ".USERS_TABLE." u ON (".get_user_table_field("u.", "user_id")." = i.user_id)
        WHERE i.image_active = 1 AND i.cat_id NOT IN ($cat_id_sql) AND i.cat_id = c.cat_id
        $cat_match_sql
        ORDER BY i.image_downloads DESC, i.image_name ASC
        LIMIT 10";
$result = $site_db->query($sql);
$top_list = array();
$i = 1;
while ($row = $site_db->fetch_array($result)) {
  $top_list[$i] = $row;
  $i++;
}
$site_db->free_result();

for ($i = 1; $i <= 10; $i++) {
  if (isset($top_list[$i])) {
    $register_array['image_downloads_'.$i] = (check_permission("auth_viewimage", $top_list[$i]['cat_id'])) ? "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\">".format_text($top_list[$i]['image_name'])."</a>" : format_text($top_list[$i]['image_name']);
    $register_array['image_downloads_openwindow_'.$i] = (check_permission("auth_viewimage", $top_list[$i]['cat_id'])) ? "<a href=\"".$site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$top_list[$i]['image_id'])."\" onclick=\"opendetailwindow()\" target=\"detailwindow\">".format_text($top_list[$i]['image_name'])."</a>" : format_text($top_list[$i]['image_name']);
    if (isset($top_list[$i][$user_table_fields['user_name']]) && $top_list[$i]['user_id'] != GUEST) {
      $user_profile_link = (!empty($url_show_profile)) ? preg_replace("/{user_id}/", $top_list[$i]['user_id'], $url_show_profile) : ROOT_PATH."member.php?action=showprofile&amp;".URL_USER_ID."=".$top_list[$i]['user_id'];
      $register_array['image_downloads_user_'.$i] = "<a href=\"".$site_sess->url($user_profile_link)."\">".format_text($top_list[$i][$user_table_fields['user_name']])."</a>";
    }
    else {
      $register_array['image_downloads_user_'.$i] = $lang['userlevel_guest'];
    }
    $register_array['image_downloads_cat_'.$i] = "<a href=\"".$site_sess->url(ROOT_PATH."categories.php?".URL_CAT_ID."=".$top_list[$i]['cat_id'])."\">".format_text($top_list[$i]['cat_name'])."</a>";
    $register_array['image_downloads_number_'.$i] = "<b>".$top_list[$i]['image_downloads']."</b>";
  }
  else {
    $register_array['image_downloads_'.$i] = "--";
    $register_array['image_downloads_user_'.$i] = "--";
    $register_array['image_downloads_cat_'.$i] = "--";
    $register_array['image_downloads_number_'.$i] = "--";
  }
}

$site_template->register_vars($register_array);

//-----------------------------------------------------
//--- Clickstream -------------------------------------
//-----------------------------------------------------
$clickstream = "<span class=\"clickstream\"><a title=\"".$lang['home']."\" href=\"".$site_sess->url(ROOT_PATH."")."\" class=\"clickstream\">".$lang['home']."</a>".$config['category_separator'];
$page_title = $config['category_separator']; // MOD: Dynamic page title
if ($cat_id && isset($cat_cache[$cat_id])) {
  $clickstream .= get_category_path($cat_id, 1).$config['category_separator'];
  $page_title .= get_category_path_nohtml($cat_id).$config['category_separator']; // MOD: Dynamic page title
}
$clickstream .= $lang['top_images']."</span>";
$page_title .= $lang['top_images']; // MOD: Dynamic page title



//#################################### Start Random Slide Show #################################################

$sql = "SELECT image_id, cat_id, user_id, image_name, image_media_file
        FROM ".IMAGES_TABLE." 
        WHERE image_active = 1 AND cat_id NOT IN (".get_auth_cat_sql("auth_viewcat", "NOTIN").") AND image_media_file LIKE '%.jpg'
        ORDER BY RAND()
        LIMIT 40"; 
        $result = $site_db->query($sql);
     $minis = "";
 while($row = $site_db->fetch_array($result))
   { 
     $minis .= "[\"./".THUMB_DIR."/".$row['cat_id']."/".$row['image_media_file']."\","; 
     $minis .= "\"".$site_sess->url($script_url."/details.php?".URL_IMAGE_ID."=".$row['image_id'])."\",\"\"";
   //$minis .= ",\"".$row['image_name']."\"";
     $minis .= "],";
   }
     $minis = substr($minis, 0, -1);
$max_width = "140";
$max_hight = "105";
     $minislide ="
    <script type=\"text/javascript\">
     var mygallery=new fadeSlideShow({
      wrapperid: \"fadeshow\",
      dimensions: [$max_width, $max_hight],
      imagearray: [$minis],
      displaymode: {type:'auto', pause:1000, cycles:0, wraparound:true},
      fadeduration: 1600,
      togglerid: \"fadeshowtoggler\"
     })
    </script>";

$minislide .= "<div style=\"width:140px;margin:2px;margin-left:41px;background-color:#ffffff\">";
$minislide .= "<div id=\"fadeshow\" style=\"margin-top:3px;margin-bottom:0px;\"></div>";
$minislide .= "<div id=\"fadeshowtoggler\" style=\"width:140px;\">";
$minislide .= "<span style=\"float:left;margin-left:0px;margin-top:10px;\"><a href=\"#\" class=\"prev\"><img src=\"./js/fade_slide/bwd.png\" style=\"border-width:0;\" alt=\"prev\"></a></span>";
$minislide .= "<span class=\"status\" style=\"float:left;margin-top:3px;text-indent:3px;font-weight:lighter;\"></span>";
$minislide .= "<span style=\"float:right;margin-right:0px;margin-top:10px;\"><a href=\"#\" class=\"next\"><img src=\"./js/fade_slide/fwd.png\" style=\"border-width:0\" alt=\"next\"></a></span>";
$minislide .= "</div>";
$minislide .= "</div>";




$site_template->register_vars("minislide", $minislide);
//#################################### End Random Slide Show #################################################



//-----------------------------------------------------
//--- Print Out ---------------------------------------
//-----------------------------------------------------
$site_template->register_vars(array(
  "msg" => $msg,
  "clickstream" => $clickstream,
  "page_title" => $page_title, // MOD: Dynamic page title
  "lang_top_image_hits" => $lang['top_image_hits'],
  "lang_top_image_downloads" => $lang['top_image_downloads'],
  "lang_top_image_rating" => $lang['top_image_rating'],
  "lang_top_image_votes" => $lang['top_image_votes']
));
$site_template->print_template($site_template->parse_template($main_template));

$content = ob_get_contents();
ob_end_clean();

if ($cache_page_top) {
  // Reset session mode
  $site_sess->mode = $old_session_mode;

  save_cache_file($cache_id, $content);
}

} // end if get_cache_file()

echo $content;

include(ROOT_PATH.'includes/page_footer.php');
?>