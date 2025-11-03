<?php
/**************************************************************************
 *                                                                        *
 *    4images - A Web Based Image Gallery Management System               *
 *    ----------------------------------------------------------------    *
 *                                                                        *
 *             File: categories.php                                       *
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

$templates_used = 'categories,category_bit,thumbnail_bit';
$main_template = 'categories';



define('GET_CACHES', 1);
define('ROOT_PATH', './');
define('MAIN_SCRIPT', __FILE__);


include(ROOT_PATH.'global.php');
require(ROOT_PATH.'includes/sessions.php');

// Wejście w kategorię czyści tryb wyszukiwania
$site_sess->drop_session_var("image_mode");
$site_sess->drop_session_var("image_mode_active");
$site_sess->drop_session_var("search_keywords");

// Obsługa SEO URL
if (!isset($cat_id) || !$cat_id) {
    $cat_id = (isset($_GET['cat_id'])) ? intval($_GET['cat_id']) : 0;
}

// ========== 301 REDIRECT - Stary URL → Nowy SEO URL ==========
// Sprawdź czy to jest STARY URL (bezpośredni request do categories.php)
if ($cat_id > 0 && strpos($_SERVER['REQUEST_URI'], 'categories.php') !== false) {
    // To jest stary URL - pobierz nazwę i zrób 301
    if (isset($cat_cache[$cat_id]) && !empty($cat_cache[$cat_id]['cat_name'])) {
        require_once(ROOT_PATH.'includes/seo_urls.php');
        
        // Generuj nowy URL
        $redirect_url = get_category_seo_url($cat_id, $cat_cache[$cat_id]['cat_name']);
        
        // Zachowaj parametr page jeśli istnieje
        if (isset($_GET['page']) && $_GET['page'] > 1) {
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 'page=' . intval($_GET['page']);
        }
        
        // Usuń session ID z URL
        $redirect_url = preg_replace('/[?&]'.session_name().'=[^&]*(&|$)/', '$1', $redirect_url);
        $redirect_url = rtrim($redirect_url, '?&');
        
        // 301 Permanent Redirect
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $redirect_url);
        exit;
    }
}
// ========== KONIEC 301 REDIRECT ==========

$user_access = get_permission();
include(ROOT_PATH.'includes/page_header.php');



if (!$cat_id || !isset($cat_cache[$cat_id]) || !check_permission("auth_viewcat", $cat_id)) {
  redirect("");
}

$cache_id = create_cache_id(
  'page.categories',
  array(
    $user_info[$user_table_fields['user_id']],
    $cat_id,
    $page,
    $perpage,
    isset($user_info['lightbox_image_ids']) ? substr(md5($user_info['lightbox_image_ids']), 0, 8) : 0,
    $config['template_dir'],
    $config['language_dir']
  )
);

if (!$cache_page_categories || !$content = get_cache_file($cache_id)) {
// Always append session id if cache is enabled
if ($cache_page_categories) {
  $old_session_mode = $site_sess->mode;
  $site_sess->mode = 'get';
}

ob_start();

//-----------------------------------------------------
//--- SEO variables -----------------------------------
//-----------------------------------------------------

$site_template->register_vars(array('prepend_head_title' => $cat_cache[$cat_id]['cat_name'] . " - "));

//-----------------------------------------------------
//--- Show Categories ---------------------------------
//-----------------------------------------------------
if (!check_permission("auth_upload", $cat_id)) {
  $upload_url = "";
  $upload_button = "<img src=\"".get_gallery_image("upload_off.gif")."\" border=\"1\" alt=\"\" />"; 
}
else {
  $upload_url = $site_sess->url(ROOT_PATH."member.php?action=uploadform&amp;".URL_CAT_ID."=".$cat_id);
  $upload_button = "<a href=\"".$upload_url."\"><img src=\"".get_gallery_image("upload.gif")."\" border=\"0\" alt=\"\" /></a>";
}

$random_cat_image = (defined("SHOW_RANDOM_IMAGE") && SHOW_RANDOM_IMAGE == 0) ? "" : get_random_image($cat_id);
$site_template->register_vars(array(
  "categories" => get_categories($cat_id),
  "cat_name" => format_text($cat_cache[$cat_id]['cat_name'], 2),
  "cat_description" => format_text($cat_cache[$cat_id]['cat_description'], 1, 0, 1),
  "cat_hits" => $cat_cache[$cat_id]['cat_hits'],
  "upload_url" => $upload_url,
  "upload_button" => $upload_button,
  "random_cat_image" => $random_cat_image
));

unset($random_cat_image);


//#################### Multi Cat Start ########################################
$multicat = array(); // ? Zapobiega Notice: Undefined variable
$sql = "SELECT image_id,cat_id,image_multicat 
        FROM ".IMAGES_TABLE." 
        WHERE image_active = 1 And cat_id <> $cat_id AND image_multicat <> 0";
  $result = $site_db->query($sql); 
  while ($row = $site_db->fetch_array($result)){
    $keys = explode(',',$row['image_multicat']);
      if (in_array( $cat_id, $keys)) {
        $multicat[] = $row['image_id'];
      } 
  }
    $multicount = count($multicat);
    $multicat = (!empty($multicat)) ? " OR i.image_id IN (".implode(", ", $multicat).")" : "";
//#################### Multi Cat End ########################################



//-----------------------------------------------------
//--- Show Images -------------------------------------
//-----------------------------------------------------
$site_template->register_vars(array(
  "has_rss"   => true,
  "rss_title" => "RSS Feed: ".format_text($cat_cache[$cat_id]['cat_name'], 2)." (".str_replace(':', '', $lang['new_images']).")",
  "rss_url"   => $script_url."/rss.php?action=images&amp;".URL_CAT_ID."=".$cat_id
));

$num_rows_all = (isset($cat_cache[$cat_id]['num_images'])) ? $cat_cache[$cat_id]['num_images'] : 0;

$num_rows_all = $num_rows_all + $multicount;

$link_arg = $site_sess->url(ROOT_PATH."categories.php?".URL_CAT_ID."=".$cat_id);

include(ROOT_PATH.'includes/paging.php');
$getpaging = new Paging($page, $perpage, $num_rows_all, $link_arg);
$offset = $getpaging->get_offset();

$site_template->register_vars(array(
  "paging" => $getpaging->get_paging(),
  "paging_stats" => $getpaging->get_paging_stats()
));

$imgtable_width = ceil((intval($config['image_table_width'])) / $config['image_cells']);
if ((substr($config['image_table_width'], -1)) == "%") {
  $imgtable_width .= "%";
}

$additional_sql = "";
if (!empty($additional_image_fields)) {
  foreach ($additional_image_fields as $key => $val) {
    $additional_sql .= ", i.".$key;
  }
}

$sql = "SELECT i.image_id, i.cat_id, i.user_id, i.image_name, i.image_description, i.image_keywords, i.image_date, i.image_active, i.image_media_file, i.image_thumb_file, i.image_download_url, i.image_allow_comments, i.image_comments, i.image_downloads, i.image_votes, i.image_rating, i.image_hits".$additional_sql.", c.cat_name".get_user_table_field(", u.", "user_name")."
        FROM (".IMAGES_TABLE." i,  ".CATEGORIES_TABLE." c)
        LEFT JOIN ".USERS_TABLE." u ON (".get_user_table_field("u.", "user_id")." = i.user_id)
        WHERE i.image_active = 1 AND i.cat_id = $cat_id AND c.cat_id = i.cat_id $multicat Group by image_id
        ORDER BY ".$config['image_order']." ".$config['image_sort'].", i.image_id ".$config['image_sort']."
        LIMIT $offset, $perpage";
$result = $site_db->query($sql);
$num_rows = $site_db->get_numrows($result);

if (!$num_rows)  {
  $thumbnails = "";
  $msg = $lang['no_images'];
}
else {
  $thumbnails = "<div id=\"thumb_resp\" width=\"".$config['image_table_width']."\" border=\"0\" cellpadding=\"".$config['image_table_cellpadding']."\" cellspacing=\"".$config['image_table_cellspacing']."\">\n";
  $count = 0;
  $bgcounter = 0;
  while ($image_row = $site_db->fetch_array($result)){
    if ($count == 0) {
      $row_bg_number = ($bgcounter++ % 2 == 0) ? 1 : 2;

    }
    $thumbnails .= "<div class=\"thumb_resp_box\" align=\"center\"><div class=\"thumb_resp_content\">\n";

    show_image($image_row);
	
	$site_template->register_vars("cat_name",$cat_cache[$image_row['cat_id']]['cat_name']);
	
    $thumbnails .= $site_template->parse_template("thumbnail_bit");
    $thumbnails .= "\n</div></div>\n";

    $count++;
    if ($count == $config['image_cells']) {

      $count = 0;
	  
	  
	  
	  
	  
      if ($bgcounter == 2)
      {
        $thumbnails .= "";
        $thumbnails .= "";
        $thumbnails .= '






		
';
        $thumbnails .= "";
        $thumbnails .= "";
      }
	  
	  
	  
	  
	  
    }
  } // end while

  if ($count > 0)  {
    $leftover = ($config['image_cells'] - $count);
    if ($leftover > 0) {
      for ($i = 0; $i < $leftover; $i++){
        $thumbnails .= "";
      }
      $thumbnails .= "";
    }
  }
  $thumbnails .= "</div>\n";
} //end else
$site_template->register_vars("thumbnails", $thumbnails);
unset($thumbnails);

//-----------------------------------------------------
//--- Clickstream -------------------------------------
//-----------------------------------------------------
$clickstream = "<li><a href=\"".$site_sess->url(ROOT_PATH."")."\">".$lang['home']."</a></li>".get_category_path($cat_id);
$page_title = $config['category_separator'].get_category_path_nohtml($cat_id); // MOD: Dynamic page title 1.7.7



//#################################### Start Random Slide Show #################################################

$sql = "SELECT image_id, cat_id, user_id, image_name, image_media_file
        FROM ".IMAGES_TABLE." 
        WHERE image_active = 1 AND cat_id NOT IN (".get_auth_cat_sql("auth_viewcat", "NOTIN").") AND image_media_file LIKE '%.jpg'
        ORDER BY RAND()
        LIMIT 30"; 
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

$minislide .= "<div style=\"width:140px;margin:2px;margin-left:41px;background-color:#ffffff\" />";
$minislide .= "<div id=\"fadeshow\" style=\"margin-top:3px;margin-bottom:0px;\" /></div>";
$minislide .= "<div id=\"fadeshowtoggler\" style=\"width:140px;\" />";
$minislide .= "<span style=\"float:left;margin-left:0px;margin-top:10px;\" /><a href=\"#\" class=\"prev\" /><img src=\"./js/fade_slide/bwd.png\" style=\"border-width:0;\" alt=\"prev\" /></a></span>";
$minislide .= "<span class=\"status\" style=\"float:left;margin-top:3px;text-indent:3px;font-weight:lighter;\" /></span>";
$minislide .= "<span style=\"float:right;margin-right:0px;margin-top:10px;\" /><a href=\"#\" class=\"next\" /><img src=\"./js/fade_slide/fwd.png\" style=\"border-width:0\" alt=\"next\" /></a></span>";
$minislide .= "</div>";
$minislide .= "</div>";




$site_template->register_vars("minislide", $minislide);
//#################################### End Random Slide Show #################################################




//-----------------------------------------------------
//--- Print Out ---------------------------------------
//-----------------------------------------------------
$site_template->register_vars(array(
 "no_adds" => 
 ($cat_id == 4 )  || ($cat_id == 483 )  || ($cat_id == 484 )  || ($cat_id == 485 )  || ($cat_id == 5 )  || ($cat_id == 628 )  || ($cat_id == 6 )  || ($cat_id == 486 )  || ($cat_id == 7 )  || ($cat_id == 8 )  || ($cat_id == 9 )  || ($cat_id == 10 )  || ($cat_id == 11 )  || ($cat_id == 487 )  ||  ($cat_id == 488 )  ||  ($cat_id == 12 )  ||  ($cat_id == 13 )  ||  ($cat_id == 14 )  ||  ($cat_id == 490 )  ||  ($cat_id == 489 )  ||  ($cat_id == 15 )  ||  
 ($cat_id == 725 )  ||  ($cat_id == 724 )  ||  ($cat_id == 679 )  ||  ($cat_id == 605 )  ||  ($cat_id == 708 )  ||  ($cat_id == 555 )  ||  ($cat_id == 558 )  || ($cat_id == 497 )  || 
 
 ($cat_id == 1 )  || ($cat_id == 353) || ($cat_id == 30) || ($cat_id == 442) || ($cat_id == 31) || ($cat_id == 32) || ($cat_id == 33) || ($cat_id == 354) || ($cat_id == 355) || ($cat_id == 356) || ($cat_id == 357) || ($cat_id == 539) || ($cat_id == 540) || ($cat_id == 528) || ($cat_id == 579) || ($cat_id == 541) || ($cat_id == 533) || ($cat_id == 362) || ($cat_id == 363) || ($cat_id == 364)  || ($cat_id == 542) || ($cat_id == 532) || ($cat_id == 581) || ($cat_id == 574) || ($cat_id == 545) || 
 ($cat_id == 460) || ($cat_id == 49) || ($cat_id == 50) || ($cat_id == 51) || ($cat_id == 52) || ($cat_id == 53) || ($cat_id == 54) || ($cat_id == 305) || ($cat_id == 55) || ($cat_id == 56) || ($cat_id == 369) || ($cat_id == 583) || ($cat_id == 57) || ($cat_id == 60) || ($cat_id == 61) || ($cat_id == 62) || ($cat_id == 63) || ($cat_id == 65) || ($cat_id == 64) || ($cat_id == 66) || ($cat_id == 67) || ($cat_id == 345) || ($cat_id == 68) || ($cat_id == 328) || ($cat_id == 69) || ($cat_id == 70) || ($cat_id == 370) || ($cat_id == 59) || ($cat_id == 71) || ($cat_id == 72) || ($cat_id == 73) || ($cat_id == 329) || ($cat_id == 74) || ($cat_id == 75) || ($cat_id == 76) || ($cat_id == 77) || ($cat_id == 78) || ($cat_id == 79) || ($cat_id == 80) || ($cat_id == 81) || ($cat_id == 326) || ($cat_id == 82) || ($cat_id == 83) || ($cat_id == 415) || ($cat_id == 84) || ($cat_id == 86) || ($cat_id == 87) || ($cat_id == 371) || ($cat_id == 88) || ($cat_id == 89) || ($cat_id == 90) || ($cat_id == 317) || ($cat_id == 91) || ($cat_id == 92) || ($cat_id == 93) || ($cat_id == 330) || ($cat_id == 331) || ($cat_id == 316) || ($cat_id == 332) || ($cat_id == 94) || ($cat_id == 95) || ($cat_id == 96) || ($cat_id == 97) || ($cat_id == 333) || ($cat_id == 334) || ($cat_id == 98) || ($cat_id == 99) || ($cat_id == 100) || ($cat_id == 101) || ($cat_id == 335) || ($cat_id == 103) || ($cat_id == 104) || ($cat_id == 105) || ($cat_id == 106) || ($cat_id == 327) || ($cat_id == 107) || ($cat_id == 108) || ($cat_id == 109) || ($cat_id == 336) || ($cat_id == 110) || ($cat_id == 337) || ($cat_id == 338) || ($cat_id == 339) || ($cat_id == 340) || ($cat_id == 341) || ($cat_id == 342) || ($cat_id == 111) || ($cat_id == 343) || ($cat_id == 112) || ($cat_id == 113) || ($cat_id == 114) || ($cat_id == 115) || ($cat_id == 344) || ($cat_id == 116) || ($cat_id == 346) || ($cat_id == 347) || ($cat_id == 348) || ($cat_id == 117) || ($cat_id == 349) || ($cat_id == 350) || ($cat_id == 351) || ($cat_id == 307) || ($cat_id == 352) || ($cat_id == 619) || ($cat_id == 681) || ($cat_id == 680) || ($cat_id == 232) || ($cat_id == 272) || ($cat_id == 118) || ($cat_id == 273) || ($cat_id == 373) || ($cat_id == 231) || ($cat_id == 306) || ($cat_id == 276) || ($cat_id == 275) || ($cat_id == 274) || ($cat_id == 522) || ($cat_id == 278) || ($cat_id == 526) || ($cat_id == 525) || ($cat_id == 527) || ($cat_id == 377)  || 
 ($cat_id == 442) || ($cat_id == 120) || ($cat_id == 121) || ($cat_id == 399) || ($cat_id == 122) || ($cat_id == 562) || ($cat_id == 538) || ($cat_id == 123) || ($cat_id == 124) || ($cat_id == 523) || ($cat_id == 625) || ($cat_id == 299) || ($cat_id == 572) || ($cat_id == 439) || ($cat_id == 582) || ($cat_id == 438) || ($cat_id == 563) || ($cat_id == 125) || ($cat_id == 743) || ($cat_id == 126) || ($cat_id == 127) || ($cat_id == 416) || 
 ($cat_id == 129) || ($cat_id == 524) || ($cat_id == 277) || ($cat_id == 543) || ($cat_id == 575) || ($cat_id == 537) || ($cat_id == 561) || ($cat_id == 132) || 
 ($cat_id == 133) || ($cat_id == 134) || ($cat_id == 135) || ($cat_id == 136) || ($cat_id == 137) || ($cat_id == 304) || ($cat_id == 569) || ($cat_id == 300) || ($cat_id == 138) || ($cat_id == 301) || ($cat_id == 139) || ($cat_id == 624) || ($cat_id == 140) || ($cat_id == 141) || ($cat_id == 142) || ($cat_id == 622) || ($cat_id == 143) || ($cat_id == 144) || ($cat_id == 620) || ($cat_id == 145) || ($cat_id == 146) || ($cat_id == 147) || ($cat_id == 148) || ($cat_id == 621) || ($cat_id == 623) || ($cat_id == 149) || ($cat_id == 568) || ($cat_id == 150) || ($cat_id == 152) || ($cat_id == 153) || ($cat_id == 664) || ($cat_id == 571) || ($cat_id == 414) || ($cat_id == 302) || ($cat_id == 570) || ($cat_id == 303) || ($cat_id == 233) || ($cat_id == 154) || ($cat_id == 155) || ($cat_id == 156) || ($cat_id == 157) || ($cat_id == 158) || ($cat_id == 229) ||
 ($cat_id == 588)|| ($cat_id == 588)|| ($cat_id == 594)|| ($cat_id == 604)|| ($cat_id == 685)|| ($cat_id == 598)|| ($cat_id == 612)? "":1,
  "msg" => $msg,
  
  
  "clickstream" => $clickstream,
  "page_title" => $page_title // MOD: Dynamic page title 1.7.7
));

$site_template->print_template($site_template->parse_template($main_template));

// MOD: Dynamic page title 1.7.7 BLOCK BEGIN
//-----------------------------------------------------
//--- Parse Header & Footer ---------------------------
//-----------------------------------------------------
if (isset($main_template) && $main_template) {
  $header = $site_template->parse_template("header");
  $footer = $site_template->parse_template("footer");
  $site_template->register_vars(array(
    "header" => $header,
    "footer" => $footer
  ));
  unset($header);
  unset($footer);
}
// MOD: Dynamic page title 1.7.7 BLOCK END

$content = ob_get_contents();
ob_end_clean();

if ($cache_page_categories) {
  // Reset session mode
  $site_sess->mode = $old_session_mode;

  save_cache_file($cache_id, $content);
}

} // end if get_cache_file()

echo $content;

//Update Category Hits
if ($user_info['user_level'] != ADMIN && $page == 1) {
  $sql = "UPDATE ".CATEGORIES_TABLE."
          SET cat_hits = cat_hits + 1
          WHERE cat_id = $cat_id";
  $site_db->query($sql);
}

include(ROOT_PATH.'includes/page_footer.php');
?>