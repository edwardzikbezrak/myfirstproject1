<?php
/**************************************************************************
 *                                                                        *
 *    4images - A Web Based Image Gallery Management System               *
 *    ----------------------------------------------------------------    *
 *                                                                        *
 *             File: details.php                                          *
 *        Copyright: (C) 2002-2015 4homepages.de                           *
 *            Email: jan@4homepages.de                                    *
 *              Web: http://www.4homepages.de                             *
 *    Scriptversion: 1.7.12                                               *
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
 
// <<<< ------ WKLEJ KOD TUTAJ ------ >>>>
function odmienWyswietlenia($liczba) {
    if ($liczba == 1) return 'wyświetlenie';
    $j = $liczba % 10; $d = floor(($liczba % 100) / 10);
    if ($d != 1 && $j >= 2 && $j <= 4) return 'wyświetlenia';
    return 'wyświetleń';
}
// <<<< ------ KONIEC KODU DO WKLEJENIA ------ >>>>

if (isset($_GET['big']) || isset($_POST['big'])) {
$templates_used = 'big,header';
$main_template = 'big';
}else{
$templates_used = 'details,header';
$main_template = 'details';
}

define('GET_CACHES', 1);
define('ROOT_PATH', './');
define('MAIN_SCRIPT', __FILE__);





include(ROOT_PATH.'global.php');
require(ROOT_PATH.'includes/sessions.php');

// Wyczyść poprzedni mode gdy user robi nowe wyszukiwanie
// $site_sess->drop_session_var("image_mode");

// Obsługa SEO URL
if (!isset($image_id) || !$image_id) {
    $image_id = (isset($_GET['image_id'])) ? intval($_GET['image_id']) : 0;
}

// ========== OBSŁUGA MODE (search/lightbox) ==========

// Jeśli przychodzi z linku z ?mode= - zapisz w sesji
if (isset($_GET['mode']) && !empty($_GET['mode'])) {
    $mode = trim($_GET['mode']);
    $site_sess->set_session_var("image_mode", $mode);
    $site_sess->set_session_var("image_mode_active", 1);
}
// Jeśli nie ma ?mode= ale jest w sesji I jest aktywny - użyj z sesji
elseif ($site_sess->get_session_var("image_mode_active")) {
    $mode = $site_sess->get_session_var("image_mode");
    if (!$mode) {
        $mode = "";
    }
}
// W przeciwnym razie - brak mode
else {
    $mode = "";
}

// ========== KONIEC OBSŁUGI MODE ==========


// ========== 301 REDIRECT - Stary URL → Nowy SEO URL ==========
// Sprawdź czy to jest STARY URL (bezpośredni request do details.php)
if ($image_id > 0 && strpos($_SERVER['REQUEST_URI'], 'details.php') !== false) {
    // To jest stary URL - pobierz nazwę i zrób 301
    $sql = "SELECT image_name FROM ".IMAGES_TABLE." WHERE image_id = ".$image_id." AND image_active = 1";
    $temp_row = $site_db->query_firstrow($sql);
    
    if ($temp_row && !empty($temp_row['image_name'])) {
        require_once(ROOT_PATH.'includes/seo_urls.php');
        
        // Generuj nowy URL
        $redirect_url = get_image_seo_url($image_id, $temp_row['image_name'], $mode);
        
        // Usuń session ID z URL (jeśli został dodany)
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

if (!$image_id) {
    redirect($url);
}






$additional_sql = "";
if (!empty($additional_image_fields)) {
  foreach ($additional_image_fields as $key => $val) {
    $additional_sql .= ", i.".$key;
  }
}

$sql = "SELECT i.image_id, i.cat_id, i.user_id, i.image_name, i.image_description, i.image_auto_description, i.image_keywords, i.image_date, i.image_active, i.image_media_file, i.image_thumb_file, i.image_download_url, i.image_allow_comments, i.image_comments, i.image_downloads, i.image_votes, i.image_rating, i.image_hits".$additional_sql.", c.cat_name".get_user_table_field(", u.", "user_name").get_user_table_field(", u.", "user_email").", c.multi_download
        FROM (".IMAGES_TABLE." i,  ".CATEGORIES_TABLE." c)
        LEFT JOIN ".USERS_TABLE." u ON (".get_user_table_field("u.", "user_id")." = i.user_id)
        WHERE i.image_id = $image_id AND i.image_active = 1 AND c.cat_id = i.cat_id";
$image_row = $site_db->query_firstrow($sql);

// <<<< ------ WKLEJ KOD TUTAJ ------ >>>>
// --- POCZĄTEK MODYFIKACJI: Dynamiczne HITY w opisie SEO ---
if (isset($image_row['image_auto_description']) && !empty($image_row['image_auto_description'])) {
    // Sprawdzamy, czy w opisie jest nasz specjalny znacznik
    if (strpos($image_row['image_auto_description'], '{{HITS_PLACEHOLDER}}') !== false) {
        
        // Pobieramy aktualną liczbę wyświetleń
        $current_hits = $image_row['image_hits'];
        
        // Tworzymy poprawny tekst (np. "1 234 wyświetleń")
        $dynamic_hits_text = number_format($current_hits, 0, ',', ' ') . ' ' . odmienWyswietlenia($current_hits);
        
        // Podmieniamy nasz znacznik w opisie na dynamicznie wygenerowany tekst
        $image_row['image_auto_description'] = str_replace(
            '{{HITS_PLACEHOLDER}}',
            $dynamic_hits_text,
            $image_row['image_auto_description']
        );
    }
    // Nadpisujemy standardowy opis naszym nowym, dynamicznym opisem SEO
    // Dzięki temu nie trzeba modyfikować szablonu (.tpl) - wystarczy, że używa on zmiennej {image_description}
    // $image_row['image_description'] = $image_row['image_auto_description'];
}
// --- KONIEC MODYFIKACJI ---
// <<<< ------ KONIEC KODU DO WKLEJENIA ------ >>>>

$cat_id = (isset($image_row['cat_id'])) ? $image_row['cat_id'] : 0;
$is_image_owner = ($image_row['user_id'] > USER_AWAITING && $user_info['user_id'] == $image_row['user_id']) ? 1 : 0;




// MOD multi download
$multi_download = $image_row['multi_download'];
if($multi_download){
	if(!strpos(",jpg,jpeg,JPG,JPEG,png,gif",pathinfo($image_row['image_media_file'], PATHINFO_EXTENSION))){
	$multi_download=0;
	}
}
// END MOD multi download





if (!check_permission("auth_viewcat", $cat_id) || !check_permission("auth_viewimage", $cat_id) || !$image_row) {
  redirect($url);
}

$random_cat_image = (defined("SHOW_RANDOM_IMAGE") && SHOW_RANDOM_IMAGE == 0) ? "" : get_random_image($cat_id);
$site_template->register_vars("random_cat_image", $random_cat_image);
unset($random_cat_image);





//------------------------------------
//------- MOD Similar Images
//------------------------------------
$image_keywords  = substr($image_row['image_keywords'], 0, 80); //keywords of actual image
$image_description  = $image_row['image_description'];          //description of actual image
$image_id_self  = $image_row['image_id'];                       //id of actual image
$i_ids = 0;
$i_ids_max = 12;                                                 //max number of thumbs displayed
$image_ids = "";
$percent_limit = 80;                                            //limit of similarity; vary to fit to your database!
$percent_minimum = 40;                                          //minimum of similarity; vary to fit to your database!

//-- select all images and identify similar images
//-- write a string $image_ids with the set of id's, comma seperated
//-- if there are no similar images, descend $percent_limit and try once more
$sql = "SELECT image_id, image_name, image_description, image_keywords, image_active
        FROM ".IMAGES_TABLE."
        WHERE image_active = 1";
$result_allimages = $site_db->query($sql);

while (($i_ids == 0) && ($percent_limit > $percent_minimum)) {
        while ($image_row_allimages = $site_db->fetch_array($result_allimages)){
              similar_text ( $image_keywords, substr($image_row_allimages['image_keywords'], 0, 80), $percent );
//              similar_text ( $image_description, substr($image_row_allimages['image_description'], 0, 50), $percent );
              if (($percent > $percent_limit) && ($image_row_allimages['image_id'] != $image_id_self)) {
                 $i_ids = $i_ids + 1;
                 $image_ids .= $image_row_allimages['image_id'] . ", ";
              }
        }
        $percent_limit = $percent_limit - 5;
}

//-- remove the last comma in $image_ids
if (strlen($image_ids) > 0) {
  $image_ids = substr($image_ids, 0, strlen($image_ids)-2);
}
//-- set $i_ids to its maximum, for correct mysql statement below
if ($i_ids > 4) $i_ids = $i_ids_max;

//-- build table with thumbs of similar images (only if there are some, of course)
if ($i_ids == 0) {
  $similar_images = "<table width=\"".$config['image_table_width']."\" border=\"0\" cellpadding=\"".$config['image_table_cellpadding']."\" cellspacing=\"".$config['image_table_cellspacing']."\"><tr class=\"imagerow1\"><td>";
  $similar_images .= "</td></tr></table>";
}
else  {

//---- select similar images, there id's are in the set $image_ids now
//---- randomized and limited
$sql = "SELECT *
        FROM ".IMAGES_TABLE."
        WHERE image_id IN (" . $image_ids . ")
        ORDER BY RAND()
        LIMIT ".$i_ids;
$result_similarimages = $site_db->query($sql);
$num_rows_similarimages = $site_db->get_numrows($result_similarimages);

//---- build table and table-cells
if (!$num_rows_similarimages)  {
  $similar_images = "<table width=\"".$config['image_table_width']."\" border=\"0\" cellpadding=\"".$config['image_table_cellpadding']."\" cellspacing=\"".$config['image_table_cellspacing']."\"><tr class=\"imagerow1\"><td>";
  $similar_images .= "</td></tr></table>";
}



else  {
  $similar_images = "<div id=\"thumb_resp\" width=\"".$config['image_table_width']."\" border=\"6\" cellpadding=\"".$config['image_table_cellpadding']."\" cellspacing=\"".$config['image_table_cellspacing']."\">";
  $count = 0;
  $bgcounter = 0;
  while ($image_row_similarimages = $site_db->fetch_array($result_similarimages)){
    if ($count == 0) {
      $row_bg_number = ($bgcounter++ % 2 == 0) ? 1 : 2;

    }
    $similar_images .= "<div class=\"thumb_resp_box\" align=\"center\"><div class=\"thumb_resp_content\">\n";

    show_image($image_row_similarimages);
    $similar_images .= $site_template->parse_template("thumbnail_bit");
    $similar_images .= "\n</div></div>\n";
    $count++;
    if ($count == $config['image_cells']) {

      $count = 0;
    }
  }

  if ($count > 0)  {
    $leftover = ($config['image_cells'] - $count);
    if ($leftover >= 1) {
      for ($f = 0; $f < $leftover; $f++) {

      }

    }
  }
  $similar_images .= "</div>\n";
}
}



//-- register template-keys
$site_template->register_vars(array(
    "similar_images"        => $similar_images,
    "lang_similar_images"   => $lang['lang_similar_images']
    ));
unset($similar_images);
//------- End similar images---------
//------------------------------------





//-----------------------------------------------------
//--- Show Image --------------------------------------
//-----------------------------------------------------
$image_allow_comments = (check_permission("auth_readcomment", $cat_id)) ? $image_row['image_allow_comments'] : 0;
$image_name = format_text($image_row['image_name'], 2);
show_image($image_row, $mode, 0, 1);


    //--- SEO variables -------------------------------
    
$meta_keywords  = !empty($image_row['image_keywords']) ? strip_tags(implode(", ", explode(",", $image_row['image_keywords']))) : "";
$meta_description = !empty($image_row['image_description']) ? strip_tags($image_row['image_description']) . ". " : "";
    
    $site_template->register_vars(array(
            "detail_meta_description"   => addslashes($meta_description),
            "detail_meta_keywords"      => addslashes($meta_keywords),
            "prepend_head_title"        => $image_name . " - ",
            ));


$in_mode = 0;
$sql = "";

$page_title_szukaj = "";

if ($mode == "lightbox") {
  if (!empty($user_info['lightbox_image_ids'])) {
    $image_id_sql = str_replace(" ", ", ", trim($user_info['lightbox_image_ids']));
    $sql = "SELECT image_id, cat_id, image_name, image_media_file, image_thumb_file
            FROM ".IMAGES_TABLE."
            WHERE image_active = 1 AND image_id IN ($image_id_sql) AND (cat_id NOT IN (".get_auth_cat_sql("auth_viewimage", "NOTIN").", ".get_auth_cat_sql("auth_viewcat", "NOTIN")."))
            ORDER BY ".$config['image_order']." ".$config['image_sort'].", image_id ".$config['image_sort'];
    $in_mode = 1;
  }
}
elseif ($mode == "search") {
  if (!isset($session_info['searchid']) || empty($session_info['searchid'])) {
    $session_info['search_id'] = $site_sess->get_session_var("search_id");
  }

  if (!empty($session_info['search_id'])) {
    $search_id = unserialize($session_info['search_id']);
  }

  $sql_where_query = "";

  if (!empty($search_id['image_ids'])) {
    $sql_where_query .= "AND image_id IN (".$search_id['image_ids'].") ";
  }

  if (!empty($search_id['user_ids'])) {
    $sql_where_query .= "AND user_id IN (".$search_id['user_ids'].") ";
  }

  if (!empty($search_id['search_new_images']) && $search_id['search_new_images'] == 1) {
    $new_cutoff = time() - 60 * 60 * 24 * $config['new_cutoff'];
    $sql_where_query .= "AND image_date >= $new_cutoff ";
  }

  if (!empty($search_id['search_cat']) && $search_id['search_cat'] != 0) {
    $cat_id_sql = 0;
    if (check_permission("auth_viewcat", $search_id['search_cat'])) {
      $sub_cat_ids = get_subcat_ids($search_id['search_cat'], $search_id['search_cat'], $cat_parent_cache);
      $cat_id_sql .= ", ".$search_id['search_cat'];
      if (!empty($sub_cat_ids[$search_id['search_cat']])) {
        foreach ($sub_cat_ids[$search_id['search_cat']] as $val) {
          if (check_permission("auth_viewcat", $val)) {
            $cat_id_sql .= ", ".$val;
          }
        }
      }
    }
    $cat_id_sql = $cat_id_sql !== 0 ? "AND cat_id IN ($cat_id_sql)" : "";
  }
  else {
    $cat_id_sql = get_auth_cat_sql("auth_viewcat", "NOTIN");
    $cat_id_sql = $cat_id_sql !== 0 ? "AND cat_id NOT IN (".$cat_id_sql.")" : "";
  }

  if (!empty($sql_where_query)) {
    $sql = "SELECT image_id, cat_id, image_name, image_media_file, image_thumb_file
            FROM ".IMAGES_TABLE."
            WHERE image_active = 1
            $sql_where_query
            $cat_id_sql
            ORDER BY ".$config['image_order']." ".$config['image_sort'].", image_id ".$config['image_sort'];
    $in_mode = 1;
  }
}
if (!$in_mode || empty($sql)) {
  $sql = "SELECT image_id, cat_id, image_name, image_media_file, image_thumb_file
          FROM ".IMAGES_TABLE."
          WHERE image_active = 1 AND cat_id = $cat_id
          ORDER BY ".$config['image_order']." ".$config['image_sort'].", image_id ".$config['image_sort'];
}
$result = $site_db->query($sql);

$image_id_cache = array();
$next_prev_cache = array();
$break = 0;
$prev_id = 0;
while($row = $site_db->fetch_array($result)) {
  $image_id_cache[] = $row['image_id'];
  $next_prev_cache[$row['image_id']] = $row;
  if ($break) {
    break;
  }
  if ($prev_id == $image_id) {
    $break = 1;
  }
  $prev_id = $row['image_id'];
}
$site_db->free_result();

if (!function_exists("array_search")) {
  function array_search($needle, $haystack) {
    $match = false;
    foreach ($haystack as $key => $value) {
      if ($value == $needle) {
        $match = $key;
      }
    }
    return $match;
  }
}

$act_key = array_search($image_id, $image_id_cache);
$next_image_id = (isset($image_id_cache[$act_key + 1])) ? $image_id_cache[$act_key + 1] : 0;
$prev_image_id = (isset($image_id_cache[$act_key - 1])) ? $image_id_cache[$act_key - 1] : 0;
unset($image_id_cache);

// Get next and previous image
if (!empty($next_prev_cache[$next_image_id])) {
  $next_image_name = format_text($next_prev_cache[$next_image_id]['image_name'], 2);
  $next_image_url = $site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$next_image_id.((!empty($mode)) ? "&amp;mode=".$mode : ""));
  if (!get_file_path($next_prev_cache[$next_image_id]['image_media_file'], "media", $next_prev_cache[$next_image_id]['cat_id'], 0, 0)) {
    $next_image_file = ICON_PATH."/404.gif";
  }
  else {
    $next_image_file = get_file_path($next_prev_cache[$next_image_id]['image_media_file'], "media", $next_prev_cache[$next_image_id]['cat_id'], 0, 1);
  }
  if (!get_file_path($next_prev_cache[$next_image_id]['image_thumb_file'], "thumb", $next_prev_cache[$next_image_id]['cat_id'], 0, 0)) {
    $next_thumb_file = ICON_PATH."/".get_file_extension($next_prev_cache[$next_image_id]['image_media_file']).".gif";
  }
  else {
    $next_thumb_file = get_file_path($next_prev_cache[$next_image_id]['image_thumb_file'], "thumb", $next_prev_cache[$next_image_id]['cat_id'], 0, 1);
  }
}
else {
  $next_image_name = REPLACE_EMPTY;
  $next_image_url = REPLACE_EMPTY;
  $next_image_file = REPLACE_EMPTY;
  $next_thumb_file = REPLACE_EMPTY;
}

if (!empty($next_prev_cache[$prev_image_id])) {
  $prev_image_name = format_text($next_prev_cache[$prev_image_id]['image_name'], 2);
  $prev_image_url = $site_sess->url(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$prev_image_id.((!empty($mode)) ? "&amp;mode=".$mode : ""));
  if (!get_file_path($next_prev_cache[$prev_image_id]['image_media_file'], "media", $next_prev_cache[$prev_image_id]['cat_id'], 0, 0)) {
    $prev_image_file = ICON_PATH."/404.gif";
  }
  else {
    $prev_image_file = get_file_path($next_prev_cache[$prev_image_id]['image_media_file'], "media", $next_prev_cache[$prev_image_id]['cat_id'], 0, 1);
  }
  if (!get_file_path($next_prev_cache[$prev_image_id]['image_thumb_file'], "thumb", $next_prev_cache[$prev_image_id]['cat_id'], 0, 0)) {
    $prev_thumb_file = ICON_PATH."/".get_file_extension($next_prev_cache[$prev_image_id]['image_media_file']).".gif";
  }
  else {
    $prev_thumb_file = get_file_path($next_prev_cache[$prev_image_id]['image_thumb_file'], "thumb", $next_prev_cache[$prev_image_id]['cat_id'], 0, 1);
  }
}
else {
  $prev_image_name = REPLACE_EMPTY;
  $prev_image_url = REPLACE_EMPTY;
  $prev_image_file = REPLACE_EMPTY;
  $prev_thumb_file = REPLACE_EMPTY;
}

$site_template->register_vars(array(
  "next_image_id" => $next_image_id,
  "next_image_name" => $next_image_name,
  "next_image_url" => $next_image_url,
  "next_image_file" => $next_image_file,
  "next_thumb_file" => $next_thumb_file,
  "prev_image_id" => $prev_image_id,
  "prev_image_name" => $prev_image_name,
  "prev_image_url" => $prev_image_url,
  "prev_image_file" => $prev_image_file,
  "prev_thumb_file" => $prev_thumb_file
));
unset($next_prev_cache);








//##################################### Start MOD: Photo Preview Hack ###################################

$total = "5"; // always an odd number e.g. 5,7,9,11... e.t.c
$center = 1; // for table-width 100% set 0;

  $result = $site_db->query($sql);
    while($row = $site_db->fetch_array($result)){
      $image_preview[] = $row['image_id'];
      $preview_row[$row['image_id']] = $row;
    }

    $lastPage = count($image_preview);

      if ($center == 1){
        $t_template = "<table align=\"center\" width=\"10%;\">\n";
      }
      else{
        $t_template = "<table width=\"100%;\">\n";
      }  
        $t_template .= "<tr>";

      if($lastPage < ($total + 1)){ 
        $start = 0;
        $end = $lastPage -1;
      }
      elseif ($act_key <= (($total-1)/2 -1)){
        $start = 0;
        $end   = ($total - 1);
      }
      elseif ($act_key >= $lastPage - (($total-1)/2)){
        $start = $lastPage - $total;
        $end = $lastPage - 1;
      } 
      else {
        $start = $act_key - ($total-1)/2;
        $end   = $act_key + ($total-1)/2;
      }

      for($i=$start; $i<=$end; $i++){
        if ($preview_row[$image_preview[$i]]['image_id'] == $image_row['image_id']) {
          $t_template .= "<td class=\"minirow1\">\n";
        }
        else {
          $t_template .= "<td class=\"minirow2\">\n";
        } 
    
        $t_template .= get_thumbnail_small_code($preview_row[$image_preview[$i]]['image_media_file'], $preview_row[$image_preview[$i]]['image_thumb_file'], $preview_row[$image_preview[$i]]['image_id'], $preview_row[$image_preview[$i]]['cat_id'], format_text(trim($preview_row[$image_preview[$i]]['image_name']), 2), $mode, 1);
        $t_template .= "</td>";
      }

      $t_template .= "</tr>";
      $t_template .= "</table>\n";
      $site_template->register_vars("preview_box", $t_template);
	  
unset($image_preview);
//######################################## End MOD: Photo Preview Hack  #######################################











//-----------------------------------------------------
//--- Save Comment ------------------------------------
//-----------------------------------------------------
$error = 0;
if ($action == "postcomment" && isset($HTTP_POST_VARS[URL_ID])) {
  $id = intval($HTTP_POST_VARS[URL_ID]);
  $sql = "SELECT cat_id, image_allow_comments
          FROM ".IMAGES_TABLE."
          WHERE image_id = $id";
  $row = $site_db->query_firstrow($sql);

  if ($row['image_allow_comments'] == 0 || !check_permission("auth_postcomment", $row['cat_id']) || !$row) {
    $msg = $lang['comments_deactivated'];
  }
  else {
    $user_name = un_htmlspecialchars(trim($HTTP_POST_VARS['user_name']));
    $comment_headline = un_htmlspecialchars(trim($HTTP_POST_VARS['comment_headline']));
    $comment_text = un_htmlspecialchars(trim($HTTP_POST_VARS['comment_text']));

    $captcha = (isset($HTTP_POST_VARS['captcha'])) ? un_htmlspecialchars(trim($HTTP_POST_VARS['captcha'])) : "";

    // Flood Check
    $sql = "SELECT comment_ip, comment_date
            FROM ".COMMENTS_TABLE."
            WHERE image_id = $id
            ORDER BY comment_date DESC
            LIMIT 1";
    $spam_row = $site_db->query_firstrow($sql);
    $spamtime = $spam_row['comment_date'] + 180;

    if ($session_info['session_ip'] == $spam_row['comment_ip'] && time() <= $spamtime && $user_info['user_level'] != ADMIN)  {
      $msg .= (($msg != "") ? "<br />" : "").$lang['spamming'];
      $error = 1;
    }

    $user_name_field = get_user_table_field("", "user_name");
    if (!empty($user_name_field)) {
      if ($site_db->not_empty("SELECT $user_name_field FROM ".USERS_TABLE." WHERE $user_name_field = '".strtolower($user_name)."' AND ".get_user_table_field("", "user_id")." <> '".$user_info['user_id']."'")) {
        $msg .= (($msg != "") ? "<br />" : "").$lang['username_exists'];
        $error = 1;
      }
    }
    if ($user_name == "")  {
      $msg .= (($msg != "") ? "<br />" : "").$lang['name_required'];
      $error = 1;
    }
    if ($comment_headline == "")  {
      $msg .= (($msg != "") ? "<br />" : "").$lang['headline_required'];
      $error = 0;
    }
    if ($comment_text == "")  {
      $msg .= (($msg != "") ? "<br />" : "").$lang['comment_required'];
      $error = 1;
    }

    if ($captcha_enable_comments && !captcha_validate($captcha)) {
      $msg .= (($msg != "") ? "<br />" : "").$lang['captcha_required'];
      $error = 1;
    }

    if (!$error)  {
      $sql = "INSERT INTO ".COMMENTS_TABLE."
              (image_id, user_id, user_name, comment_headline, comment_text, comment_ip, comment_date)
              VALUES
              ($id, ".$user_info['user_id'].", '$user_name', '$comment_headline', '$comment_text', '".$session_info['session_ip']."', ".time().")";
      $site_db->query($sql);
      $commentid = $site_db->get_insert_id();
      update_comment_count($id, $user_info['user_id']);
      $msg = $lang['comment_success'];
      $site_sess->set_session_var("msgdetails", $msg);
      redirect(ROOT_PATH."details.php?".URL_IMAGE_ID."=".$image_id.((!empty($mode)) ? "&mode=".$mode : "").(($page > 1) ? "&page=".$page : ""));
    }
  }
  unset($row);
  unset($spam_row);
}

//-----------------------------------------------------
//--- Show Comments -----------------------------------
//-----------------------------------------------------
if ($msgdetails = $site_sess->get_session_var("msgdetails"))
{
  $msg .= ($msg !== "" ? "<br />" : "").$msgdetails;
  unset($msgdetails);
  $site_sess->drop_session_var("msgdetails");
}

if ($image_allow_comments == 1) {
  $site_template->register_vars(array(
      "has_rss"   => true,
      "rss_title" => "RSS Feed: ".$image_name." (".str_replace(':', '', $lang['comments']).")",
      "rss_url"   => $script_url."/rss.php?action=comments&amp;".URL_IMAGE_ID."=".$image_id
  ));

  $sql = "SELECT c.comment_id, c.image_id, c.user_id, c.user_name AS comment_user_name, c.comment_headline, c.comment_text, c.comment_ip, c.comment_date".get_user_table_field(", u.", "user_level").get_user_table_field(", u.", "user_name").get_user_table_field(", u.", "user_email").get_user_table_field(", u.", "user_showemail").get_user_table_field(", u.", "user_invisible").get_user_table_field(", u.", "user_joindate").get_user_table_field(", u.", "user_lastaction").get_user_table_field(", u.", "user_comments").get_user_table_field(", u.", "user_homepage").get_user_table_field(", u.", "user_icq")."
          FROM ".COMMENTS_TABLE." c
          LEFT JOIN ".USERS_TABLE." u ON (".get_user_table_field("u.", "user_id")." = c.user_id)
          WHERE c.image_id = $image_id
          ORDER BY c.comment_date ASC";
  $result = $site_db->query($sql);

  $comment_row = array();
  while ($row = $site_db->fetch_array($result)) {
    $comment_row[] = $row;
  }
  $site_db->free_result($result);
  $num_comments = sizeof($comment_row);

  if (!$num_comments) {
    $comments = "<tr><td class=\"commentrow1\" colspan=\"2\">".$lang['no_comments']."</td></tr>";
  }
  else {
    $comments = "";
    $bgcounter = 0;
    for ($i = 0; $i < $num_comments; $i++) {
      $row_bg_number = ($bgcounter++ % 2 == 0) ? 1 : 2;

      $comment_user_email = "";
      $comment_user_email_save = "";
      $comment_user_mailform_link = "";
      $comment_user_email_button = "";
      $comment_user_homepage_button = "";
      $comment_user_icq_button = "";
      $comment_user_profile_button = "";
      $comment_user_status_img = REPLACE_EMPTY;
      $comment_user_name = format_text($comment_row[$i]['comment_user_name'], 2);
      $comment_user_info = $lang['userlevel_guest'];

      $comment_user_id = $comment_row[$i]['user_id'];

      if (isset($comment_row[$i][$user_table_fields['user_name']]) && $comment_user_id != GUEST) {
        $comment_user_name = format_text($comment_row[$i][$user_table_fields['user_name']], 2);

        $comment_user_profile_link = !empty($url_show_profile) ? $site_sess->url(preg_replace("/{user_id}/", $comment_user_id, $url_show_profile)) : $site_sess->url(ROOT_PATH."member.php?action=showprofile&amp;".URL_USER_ID."=".$comment_user_id);
        $comment_user_profile_button = "<a href=\"".$comment_user_profile_link."\"><img src=\"".get_gallery_image("profile.gif")."\" border=\"0\" alt=\"".$comment_user_name."\" /></a>";

        $comment_user_status_img = ($comment_row[$i][$user_table_fields['user_lastaction']] >= (time() - 300) && ((isset($comment_row[$i][$user_table_fields['user_invisible']]) && $comment_row[$i][$user_table_fields['user_invisible']] == 0) || $user_info['user_level'] == ADMIN)) ? "<img src=\"".get_gallery_image("user_online.gif")."\" border=\"0\" alt=\"Online\" />" : "<img src=\"".get_gallery_image("user_offline.gif")."\" border=\"0\" alt=\"Offline\" />";

        $comment_user_homepage = (isset($comment_row[$i][$user_table_fields['user_homepage']])) ? format_url($comment_row[$i][$user_table_fields['user_homepage']]) : "";
        if (!empty($comment_user_homepage)) {
          $comment_user_homepage_button = "<a href=\"".$comment_user_homepage."\" target=\"_blank\"><img src=\"".get_gallery_image("homepage.gif")."\" border=\"0\" alt=\"".$comment_user_homepage."\" /></a>";
        }

        $comment_user_icq = (isset($comment_row[$i][$user_table_fields['user_icq']])) ? format_text($comment_row[$i][$user_table_fields['user_icq']]) : "";
        if (!empty($comment_user_icq)) {
          $comment_user_icq_button = "<a href=\"http://www.icq.com/people/about_me.php?uin=".$comment_user_icq."\" target=\"_blank\"><img src=\"http://status.icq.com/online.gif?icq=".$comment_user_icq."&img=5\" width=\"18\" height=\"18\" border=\"0\" alt=\"".$comment_user_icq."\" /></a>";
        }

        if (!empty($comment_row[$i][$user_table_fields['user_email']]) && (!isset($comment_row[$i][$user_table_fields['user_showemail']]) || (isset($comment_row[$i][$user_table_fields['user_showemail']]) && $comment_row[$i][$user_table_fields['user_showemail']] == 1))) {
          $comment_user_email = format_text($comment_row[$i][$user_table_fields['user_email']]);
          $comment_user_email_save = format_text(str_replace("@", " at ", $comment_row[$i][$user_table_fields['user_email']]));
          if (!empty($url_mailform)) {
            $comment_user_mailform_link = $site_sess->url(preg_replace("/{user_id}/", $comment_user_id, $url_mailform));
          }
          else {
            $comment_user_mailform_link = $site_sess->url(ROOT_PATH."member.php?action=mailform&amp;".URL_USER_ID."=".$comment_user_id);
          }
          $comment_user_email_button = "<a href=\"".$comment_user_mailform_link."\"><img src=\"".get_gallery_image("email.gif")."\" border=\"0\" alt=\"".$comment_user_email_save."\" /></a>";
        }

        if (!isset($comment_row[$i][$user_table_fields['user_level']]) || (isset($comment_row[$i][$user_table_fields['user_level']]) && $comment_row[$i][$user_table_fields['user_level']] == USER)) {
          $comment_user_info = $lang['userlevel_user'];
        }
        elseif ($comment_row[$i][$user_table_fields['user_level']] == ADMIN) {
          $comment_user_info = $lang['userlevel_admin'];
        }

        $comment_user_info .= "<br />";
        $comment_user_info .= (isset($comment_row[$i][$user_table_fields['user_joindate']])) ? "<br />".$lang['join_date']." ".format_date($config['date_format'], $comment_row[$i][$user_table_fields['user_joindate']]) : "";
        $comment_user_info .= (isset($comment_row[$i][$user_table_fields['user_comments']])) ? "<br />".$lang['comments']." ".$comment_row[$i][$user_table_fields['user_comments']] : "";
      }

      $comment_user_ip = ($user_info['user_level'] == ADMIN) ? $comment_row[$i]['comment_ip'] : "";

      $admin_links = "";
      if ($user_info['user_level'] == ADMIN) {
        $admin_links .= "<a href=\"".$site_sess->url(ROOT_PATH."admin/index.php?goto=".urlencode("comments.php?action=editcomment&amp;comment_id=".$comment_row[$i]['comment_id']))."\" target=\"_blank\">".$lang['edit']."</a>&nbsp;";
        $admin_links .= "<a href=\"".$site_sess->url(ROOT_PATH."admin/index.php?goto=".urlencode("comments.php?action=removecomment&amp;comment_id=".$comment_row[$i]['comment_id']))."\" target=\"_blank\">".$lang['delete']."</a>";
      }
      elseif ($is_image_owner) {
        $admin_links .= ($config['user_edit_comments'] != 1) ? "" : "<a href=\"".$site_sess->url(ROOT_PATH."member.php?action=editcomment&amp;".URL_COMMENT_ID."=".$comment_row[$i]['comment_id'])."\">".$lang['edit']."</a>&nbsp;";
        $admin_links .= ($config['user_delete_comments'] != 1) ? "" : "<a href=\"".$site_sess->url(ROOT_PATH."member.php?action=removecomment&amp;".URL_COMMENT_ID."=".$comment_row[$i]['comment_id'])."\">".$lang['delete']."</a>";
      }

      $site_template->register_vars(array(
        "comment_id" => $comment_row[$i]['comment_id'],
        "comment_user_id" => $comment_user_id,
        "comment_user_status_img" => $comment_user_status_img,
        "comment_user_name" => $comment_user_name,
        "comment_user_info" => $comment_user_info,
        "comment_user_profile_button" => $comment_user_profile_button,
        "comment_user_email" => $comment_user_email,
        "comment_user_email_save" => $comment_user_email_save,
        "comment_user_mailform_link" => $comment_user_mailform_link,
        "comment_user_email_button" => $comment_user_email_button,
        "comment_user_homepage_button" => $comment_user_homepage_button,
        "comment_user_icq_button" => $comment_user_icq_button,
        "comment_user_ip" => $comment_user_ip,
        "comment_headline" => format_text($comment_row[$i]['comment_headline'], 0, $config['wordwrap_comments'], 0, 0),
        "comment_text" => format_text($comment_row[$i]['comment_text'], $config['html_comments'], $config['wordwrap_comments'], $config['bb_comments'], $config['bb_img_comments']),
        "comment_date" => format_date($config['date_format']." ".$config['time_format'], $comment_row[$i]['comment_date']),
        "row_bg_number" => $row_bg_number,
        "admin_links" => $admin_links
      ));
      $comments .= $site_template->parse_template("comment_bit");
    } // end while
  } //end else
  $site_template->register_vars("comments", $comments);
  unset($comments);

  //-----------------------------------------------------
  //--- BBCode & Form -----------------------------------
  //-----------------------------------------------------
  $allow_posting = check_permission("auth_postcomment", $cat_id);
  $bbcode = "";
  if ($config['bb_comments'] == 1 && $allow_posting) {
    $site_template->register_vars(array(
      "lang_bbcode" => $lang['bbcode'],
      "lang_tag_prompt" => $lang['tag_prompt'],
      "lang_link_text_prompt" => $lang['link_text_prompt'],
      "lang_link_url_prompt" => $lang['link_url_prompt'],
      "lang_link_email_prompt" => $lang['link_email_prompt'],
      "lang_list_type_prompt" => $lang['list_type_prompt'],
      "lang_list_item_prompt" => $lang['list_item_prompt']
    ));
    $bbcode = $site_template->parse_template("bbcode");
  }

  if (!$allow_posting) {
    $comment_form = "";
  }
  else {
    $user_name = (isset($HTTP_POST_VARS['user_name']) && $error) ? format_text(trim(stripslashes($HTTP_POST_VARS['user_name'])), 2) : (($user_info['user_level'] != GUEST) ? format_text($user_info['user_name'], 2) : "");
    $comment_headline = (isset($HTTP_POST_VARS['comment_headline']) && $error) ? format_text(trim(stripslashes($HTTP_POST_VARS['comment_headline'])), 2) : "";
    $comment_text = (isset($HTTP_POST_VARS['comment_text']) && $error) ? format_text(trim(stripslashes($HTTP_POST_VARS['comment_text'])), 2) : "";

    $site_template->register_vars(array(
      "bbcode" => $bbcode,
      "user_name" => $user_name,
      "comment_headline" => $comment_headline,
      "comment_text" => $comment_text,
      "lang_post_comment" => $lang['post_comment'],
      "lang_name" => $lang['name'],
      "lang_headline" => $lang['headline'],
      "lang_comment" => $lang['comment'],
      "lang_captcha" => $lang['captcha'],
      "lang_captcha_desc" => $lang['captcha_desc'],
      "captcha_comments" => (bool)$captcha_enable_comments
    ));
    $comment_form = $site_template->parse_template("comment_form");
  }
  $site_template->register_vars("comment_form", $comment_form);
  unset($comment_form);
} // end if allow_comments

// Admin Links
$admin_links = "";
if ($user_info['user_level'] == ADMIN) {
  $admin_links .= "<a href=\"".$site_sess->url(ROOT_PATH."admin/images.php?action=editimage&image_id=".$image_id)."\" target=\"_blank\">".$lang['edit']."</a>&nbsp;";
  $admin_links .= "<a href=\"".$site_sess->url(ROOT_PATH."admin/images.php?action=removeimage&image_id=".$image_id)."\" target=\"_blank\">".$lang['delete']."</a>";
}
elseif ($is_image_owner) {
  $admin_links .= ($config['user_edit_image'] != 1) ? "" : "<a href=\"".$site_sess->url(ROOT_PATH."member.php?action=editimage&amp;".URL_IMAGE_ID."=".$image_id)."\">".$lang['edit']."</a>&nbsp;";
  $admin_links .= ($config['user_delete_image'] != 1) ? "" : "<a href=\"".$site_sess->url(ROOT_PATH."member.php?action=removeimage&amp;".URL_IMAGE_ID."=".$image_id)."\">".$lang['delete']."</a>";
}
$site_template->register_vars("admin_links", $admin_links);

// Update Hits
if ($user_info['user_level'] != ADMIN) {
  $sql = "UPDATE ".IMAGES_TABLE."
          SET image_hits = image_hits + 1
          WHERE image_id = $image_id";
  $site_db->query($sql);
}





//MOD multi download 
if (!$multi_download){
	$site_template->register_vars("yes_multi_download", 0);
	$site_template->register_vars("not_multi_download", 1);
	}
else{
	$site_template->register_vars("yes_multi_download", 1);
	$site_template->register_vars("not_multi_download", 0);
}
//END MOD multi download







//-----------------------------------------------------
//---Clickstream---------------------------------------
//-----------------------------------------------------
$clickstream = "<li><a href=\"".$site_sess->url(ROOT_PATH."")."\">".$lang['home']."</a></li>";

$page_title = $config['category_separator'].$lang['home'].$config['category_separator']; // MOD: Dynamic page title 1.7.7

// Sprawdź czy jesteśmy w trybie wyszukiwania (z sesji lub GET)
$is_search_mode = false;
if ($mode == "search" || $site_sess->get_session_var("image_mode") == "search") {
    if ($in_mode || $site_sess->get_session_var("image_mode_active") == 1) {
        $is_search_mode = true;
        $mode = "search"; // Upewnij się że $mode jest ustawione
    }
}

$is_lightbox_mode = ($mode == "lightbox" && $in_mode);

if ($is_lightbox_mode) {
  $page_url = "";
  if (preg_match("/".URL_PAGE."=([0-9]+)/", $url, $regs)) {
    if (!empty($regs[1]) && $regs[1] != 1) {
      $page_url = "?".URL_PAGE."=".$regs[1];
    }
  }
  $clickstream .= "<li><a href=\"".$site_sess->url(ROOT_PATH."lightbox.php".$page_url)."\">".$lang['lightbox']."</a></li>";
  
  $page_title = $config['category_separator'].$lang['lightbox'].$config['category_separator']; // MOD: Dynamic page title 1.7.7
}


elseif ($is_search_mode) {
  // W trybie wyszukiwania - pokaż "Szukaj" + słowa kluczowe w breadcrumbs
  $search_keywords_from_session = $site_sess->get_session_var("search_keywords");
  
  if ($search_keywords_from_session) {
    $clickstream .= "<li><a href=\"".get_search_seo_url($search_keywords_from_session)."\">".$lang['search']." (".$search_keywords_from_session.")</a></li>";
  } else {
    $clickstream .= "<li><a href=\"".get_search_seo_url()."\">".$lang['search']."</a></li>";
  }

  $page_title = $config['category_separator'].$lang['search'].$config['category_separator']; // MOD: Dynamic page title 1.7.7
  $page_title_szukaj = $lang['search'].$config['category_separator']; // MOD: Dynamic page title 1.7.7
}


else {
  // Normalny widok - pokaż ścieżkę kategorii
  $clickstream .= get_category_path($cat_id, 1);
  
  $page_title = $config['category_separator'].get_category_path_nohtml($cat_id).$config['category_separator']; // MOD: Dynamic page title 1.7.7
}

$clickstream .= "<li><a href=\"".get_image_seo_url($image_id, $image_row['image_name'])."\">".$image_row['image_name']."</a></li>";

$page_title .= $image_name; // MOD: Dynamic page title 1.7.7




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
  
  
  "image_id2" => $image_id,
  "clickstream" => $clickstream,
  "page_title" => $page_title, // MOD: Dynamic page title 1.7.7
  "page_title_szukaj" => $page_title_szukaj, // MOD: Dynamic page title 1.7.7
  "lang_category" => $lang['category'],
  "lang_added_by" => $lang['added_by'],
  "lang_description" => $lang['description'],
  "lang_keywords" => $lang['keywords'],
  "lang_date" => $lang['date'],
  "lang_hits" => $lang['hits'],
  "lang_downloads" => $lang['downloads'],
  "lang_rating" => $lang['rating'],
  "lang_votes" => $lang['votes'],
  "lang_author" => $lang['author'],
  "lang_comment" => $lang['comment'],
  "lang_prev_image" => $lang['prev_image'],
  "lang_next_image" => $lang['next_image'],
  "lang_file_size" => $lang['file_size']
));

$uploadinfo = "";

//-----------------------------------------------------
//--- ImageCodes v1.0 Begins --------------------------
//-----------------------------------------------------

// Mod: ImageCodes v1.0
// Version: 1.0
// Description : Get image path, link and bbcode on the details page
// Contact: arjoon@gmail.com
// Last update: June 30 2007

$sql = "SELECT image_media_file FROM ".IMAGES_TABLE." WHERE image_id= $image_id";
$image_codes = $site_db->query_firstrow($sql);

$new_name = $image_codes['image_media_file'];

      $uploaded_image_path = $script_url."/".MEDIA_DIR."/".$cat_id."/".$new_name;
      $uploaded_thumb_path = $script_url."/".THUMB_DIR."/".$cat_id."/".$new_name;
      $uploaded_image_link = $script_url."/details.php?image_id=".$image_id;
      $uploaded_thumb_hotlink = "<a href=\"".$uploaded_image_link."\"><img src=\"".$uploaded_thumb_path."\" border=\"0\" alt=\"".$new_name."\" /></a>";
      $uploaded_image_hotlink = "<a href=\"".$script_url."\"><img src=\"".$uploaded_image_path."\" border=\"0\" alt=\"".$new_name."\" /></a>";
      $uploaded_image_bbcode = "[URL=".$script_url."][IMG]".$uploaded_image_path."[/IMG][/URL]";
      $uploaded_thumb_bbcode = "[URL=".$uploaded_image_link."][IMG]".$uploaded_thumb_path."[/IMG][/URL]";
      $uploadinfo .= "<input onclick='highlight(this);' size='70' value='".$uploaded_thumb_hotlink."' type='text' name='image' /> <p>Thumbnail for websites</p>";
      $uploadinfo .= "<input onclick='highlight(this);' size='70' value='".$uploaded_thumb_bbcode."' type='text' name='image' /> <p>Thumbnail for forums<br /></p>";
      $uploadinfo .= "<p class=\"code\">Use the below codes to post the full sized image on other websites or forums</p>";
      $uploadinfo .= "<input onclick='highlight(this);' size='70' value='".$uploaded_image_hotlink."' type='text' name='image' /> <p>Hotlink for websites</p>";
      $uploadinfo .= "<input onclick='highlight(this);' size='70' value='".$uploaded_image_bbcode."' type='text' name='image' /> <p>Hotlink for forums<br /></p>";
      $uploadinfo .= "<p class=\"code\">Share this image with your friends</p>";
      $uploadinfo .= "<input onclick='highlight(this);' size='70' value='".$uploaded_image_link."' type='text' name='image' /> <p>Share this image</p>";
      $uploadinfo .= "<input onclick='highlight(this);' size='70' value='".$uploaded_image_path."' type='text' name='image' /> <p>Direct path to image<br /></p>";
      $icodes = "<div class=\"image_codes\">".(isset($uploadinfo) ? $uploadinfo : "")."</div>";

      $site_template->register_vars("image_codes", $icodes);

//-----------------------------------------------------
//--- end of ImageCodes v1.0 --------------------------
//-----------------------------------------------------

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


include(ROOT_PATH.'includes/page_footer.php');
?>