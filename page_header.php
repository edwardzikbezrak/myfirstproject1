<?php
/**************************************************************************
 *                                                                        *
 *    4images - A Web Based Image Gallery Management System               *
 *    ----------------------------------------------------------------    *
 *                                                                        *
 *             File: page_header.php                                      *
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
if (!defined('ROOT_PATH')) {
  die("Security violation");
}

// Cache Templates
/*$template_list = 'header,footer,category_dropdown_form,user_logininfo,user_loginform';
if (isset($templates_used) && $templates_used != "") {
  $template_list = $template_list.",".$templates_used;
}
$site_template->cache_templates($template_list);*/

//-----------------------------------------------------
//--- Register Global Vars ----------------------------
//-----------------------------------------------------
$total_images = 0;
$total_categories = 0;
$auth_cat_sql['auth_viewcat']['IN'] = 0;
$auth_cat_sql['auth_viewcat']['NOTIN'] = 0;

$cache_id = create_cache_id(
  'data.auth_and_info',
  array($user_info[$user_table_fields['user_id']])
);

if (!$data = get_cache_file($cache_id, null)) {
  if (!empty($cat_cache)) {
    foreach ($cat_cache as $key => $val) {
      if (check_permission("auth_viewcat", $key)) {
        $total_categories++;
        if (isset($val['num_images'])) {
          $total_images += $val['num_images'];
        }
        else {
          $cat_cache[$key]['num_images'] = 0;
        }
        $auth_cat_sql['auth_viewcat']['IN'] .= ", ".$key;
      }
      else {
        $auth_cat_sql['auth_viewcat']['NOTIN'] .= ", ".$key;
      }
    }
  }

  $data = array();

  $data['total_images'] = $total_images;
  $data['total_categories'] = $total_categories;
  $data['auth_viewcat']['IN'] = $auth_cat_sql['auth_viewcat']['IN'];
  $data['auth_viewcat']['NOTIN'] = $auth_cat_sql['auth_viewcat']['NOTIN'];

  save_cache_file($cache_id, serialize($data));

} else {
  $data = unserialize($data);

  $total_images = $data['total_images'];
  $total_categories = $data['total_categories'];
  $auth_cat_sql['auth_viewcat']['IN'] = $data['auth_viewcat']['IN'];
  $auth_cat_sql['auth_viewcat']['NOTIN'] = $data['auth_viewcat']['NOTIN'];
}

$file = get_file_name(basename(MAIN_SCRIPT));
$array = array(
    "categories" => false,
    "details"    => false,
    "index"      => false,
    "member"     => false,
    "postcards"  => false,
    "register"   => false,
    "search"     => false,
    "top"        => false
);
if ( isset($array[$file]) ) {
  $array[$file] = true;
  }
if (isset($array["page_" . $file])) {
  $array["page_" . $file] = true;
}
$site_template->register_vars($array);

$site_template->register_vars(array(
  "url_custom_login" => $site_sess->url(ROOT_PATH."index.php?template=login"),
  "url_user_logininfo" => $site_sess->url(ROOT_PATH."index.php?template=user_logininfo"),
  "home_url"  => ROOT_PATH,
  "media_url" => MEDIA_PATH,
  "thumb_url" => THUMB_PATH,
  "icon_url" => ICON_PATH,
  "template_url" => TEMPLATE_PATH,
  "template_image_url" => TEMPLATE_PATH."/images",
  "template_lang_image_url" => TEMPLATE_PATH."/images_".$config['language_dir'],
  "site_name" => $config['site_name'],
  "site_email" => $config['site_email'],
  "user_loggedin" => ($user_info['user_level'] == GUEST || $user_info['user_level'] == USER_AWAITING) ? 0 : 1,
  "user_loggedout" => ($user_info['user_level'] == GUEST || $user_info['user_level'] == USER_AWAITING) ? 1 : 0,
  "is_admin" => ($user_info['user_level'] == ADMIN) ? 1 : 0,
  "self" => $site_sess->url($self_url),
  "self_full" => $site_sess->url($script_url."/".$self_url),
  "script_version" => SCRIPT_VERSION,
  "cp_link" => ($user_info['user_level'] != ADMIN) ? "" : "\n [<a href=\"".$site_sess->url(ROOT_PATH."admin/index.php")."\">Admin Control Panel</a>] \n",
  "total_categories" => number_format($total_categories),
  "total_images" => number_format($total_images),
  "url_new_images" => $site_sess->url(ROOT_PATH."search.php?search_new_images=1"),
  "url_top_images" => $site_sess->url(ROOT_PATH."top.php"),








  "url_top_cat_images" => $site_sess->url(ROOT_PATH."top.php?cat_id=".$cat_id),
  "lang_top_cat_images" => $lang['lang_top_cat_images'],









  "url_top_cat_images" => $site_sess->url(ROOT_PATH."top.php".(($cat_id && preg_match("/categories.php/", $self_url)) ? "?".URL_CAT_ID."=".$cat_id : "")),
  "url_register" => (!empty($url_register)) ? $site_sess->url($url_register) : $site_sess->url(ROOT_PATH."register.php"),
  "url_search" => get_search_seo_url(),
  "url_lightbox" => $site_sess->url(ROOT_PATH."lightbox.php"),
  "url_control_panel" => (!empty($url_control_panel)) ? $site_sess->url($url_control_panel) : $site_sess->url(ROOT_PATH."member.php?action=editprofile"),
  "url_categories" => $site_sess->url(ROOT_PATH."categories.php"),
  "url_home" => $site_sess->url(ROOT_PATH."index.php"),
  "url_login" => (!empty($url_login)) ? $site_sess->url($url_login) : $site_sess->url(ROOT_PATH."login.php"),
  "url_logout" => (!empty($url_logout)) ? $site_sess->url($url_logout) : $site_sess->url(ROOT_PATH."logout.php"),
  "url_member" => (!empty($url_member)) ? $site_sess->url($url_member) : $site_sess->url(ROOT_PATH."member.php"),
  "url_upload" => (!empty($url_upload)) ? $site_sess->url($url_upload) : $site_sess->url(ROOT_PATH."member.php?action=uploadform"),
  "url_lost_password" => (!empty($url_lost_password)) ? $site_sess->url($url_lost_password) : $site_sess->url(ROOT_PATH."member.php?action=lostpassword"),
  "url_captcha_image" => $site_sess->url(ROOT_PATH."captcha.php"),
  "thumbnails" => "",
  "paging" => "",
  "paging_stats" => "",
  "has_rss" => false,
  "rss_title" => "",
  "rss_url" => "",
  "copyright" => '




<span class="footer">Powered by 4images '.SCRIPT_VERSION.'

;

  Copyright &copy; 2002-'.date('Y').' <a href="http://www.4homepages.de" title="4images" >4homepages.de</a></span>



',
  "base_href" => $script_url
));

if (!empty($additional_urls)) {
  $register_array = array();
  foreach ($additional_urls as $key => $val) {
    $register_array[$key] = $site_sess->url($val);
  }
  $site_template->register_vars($register_array);
}

// Replace Globals in $lang
$lang = $site_template->parse_array($lang);

$site_template->register_vars(array(
  "lang_site_stats" => $lang['site_stats'],
  "lang_registered_user" => $lang['registered_user'],
  "lang_random_image" => $lang['random_image'],
  "lang_categories" => $lang['categories'],
  "lang_sub_categories" => $lang['sub_categories'],
  "lang_new_images" => $lang['new_images'],
  "lang_top_images" => $lang['top_images'],
  "lang_search" => $lang['search'],
  "lang_advanced_search" => $lang['advanced_search'],
  "lang_lightbox" => $lang['lightbox'],
  "lang_register" => $lang['register'],
  "lang_control_panel" => $lang['control_panel'],
  "lang_login" => $lang['login'],
  "lang_auto_login" => $lang['lang_auto_login'],
  "lang_logout" => $lang['logout'],
  "lang_lost_password" => $lang['lost_password'],
  "lang_user_name" => $lang['user_name'],
  "lang_password" => $lang['password'],
  "lang_go" => $lang['go'],
  "lang_images_per_page" => $lang['images_per_page'],
  "charset" => $lang['charset'],
  
  "image_order" => $lang['image_order'],
  "image_sort" => $lang['image_sort'],
  
  "direction" => $lang['direction']
));

//-----------------------------------------------------
//--- Category Dropdown -------------------------------
//-----------------------------------------------------

if (!$cache_enable) {
    $category_dropdown_selfjump = get_category_dropdown($cat_id, 1);
} else {
  $cache_id = create_cache_id(
    'data.dropdown_selfjump',
    array(
      $user_info[$user_table_fields['user_id']],
      $config['template_dir'],
      $config['language_dir']
    )
  );

  if (!$category_dropdown_selfjump = get_cache_file($cache_id)) {
    // Always append session id if cache is enabled
    $old_session_mode = $site_sess->mode;
    $site_sess->mode = 'get';

    // Set $cat_id to 0 to ensure that no category is selected
    $category_dropdown_selfjump = get_category_dropdown(0, 1);

    $site_sess->mode = $old_session_mode;

    save_cache_file($cache_id, $category_dropdown_selfjump);
  }
}

$site_template->register_vars("category_dropdown_selfjump", $category_dropdown_selfjump);
unset($category_dropdown_selfjump);

// -------------------------------------
if (!$cache_enable) {
    $category_dropdown_form = $site_template->parse_template("category_dropdown_form");
} else {
  $cache_id = create_cache_id(
    'data.dropdown_form',
    array(
      $user_info[$user_table_fields['user_id']],
      // $cat_id, // uncomment if the current category should be selected, will increase the number of cache files
      $config['template_dir'],
      $config['language_dir']
    )
  );

  if (!$category_dropdown_form = get_cache_file($cache_id)) {
    // Always append session id if cache is enabled
    $old_session_mode = $site_sess->mode;
    $site_sess->mode = 'get';

    $category_dropdown_form = $site_template->parse_template("category_dropdown_form");

    $site_sess->mode = $old_session_mode;

    save_cache_file($cache_id, $category_dropdown_form);
  }
}

$site_template->register_vars("category_dropdown_form", $category_dropdown_form);
unset($category_dropdown_form);

//-----------------------------------------------------
//--- Random Image ------------------------------------
//-----------------------------------------------------
$random_image = (defined("SHOW_RANDOM_IMAGE") && SHOW_RANDOM_IMAGE == 0) ? "" : get_random_image();
$site_template->register_vars("random_image", $random_image);
unset($random_image);









//-----------------------------------------------------
//--- Set Paging Vars ---------------------------------
//-----------------------------------------------------
if (isset($HTTP_POST_VARS['setperpage'])) {
  $setperpage = intval($HTTP_POST_VARS['setperpage']);
  if ($setperpage) {
    $site_sess->set_session_var("perpage", $setperpage);
    $session_info['perpage'] = $setperpage;
  }
}

if (isset($session_info['perpage'])) {
  $perpage = $session_info['perpage'];
}
else {
  $perpage = ceil($config['default_image_rows'] * $config['image_cells']);
}

//-----------------------------------------------------
//--- Set Perpage Dropdown ----------------------------
//-----------------------------------------------------
$setperpage_dropdown = "\n<select onchange=\"if (this.options[this.selectedIndex].value != 0 && typeof forms['perpagebox'] != 'undefined'){ forms['perpagebox'].submit() }\" name=\"setperpage\" class=\"setperpageselect\">\n";
for($i = 1; $i <= $config['custom_row_steps']; $i++) {
  $setvalue = $config['image_cells'] * $i;
  $setperpage_dropdown .= "<option value=\"".$setvalue."\"";
    if ($setvalue == $perpage) {
    $setperpage_dropdown .= " selected=\"selected\"";
  }
  $setperpage_dropdown .= ">";
  $setperpage_dropdown .= $setvalue;
  $setperpage_dropdown .= "</option>\n";
}
$setperpage_dropdown .= "</select>\n";
if ($cat_id != 0) {
  $setperpage_dropdown .= "<input type=\"hidden\" name=\"cat_id\" value=\"".$cat_id."\" />\n";
}
if (isset($show_result) && $show_result == 1) {
  $setperpage_dropdown .= "<input type=\"hidden\" name=\"show_result\" value=\"1\" />\n";
}
$site_template->register_vars("setperpage_dropdown", $setperpage_dropdown);
$setperpage_dropdown_form = $site_template->parse_template("setperpage_dropdown_form");
$site_template->register_vars("setperpage_dropdown_form", $setperpage_dropdown_form);

$site_template->un_register_vars("setperpage_dropdown");
unset($setperpage_dropdown);
unset($setperpage_dropdown_form);

//-----------------------------------------------------
//--- Add & Delete from Lists -------------------------
//-----------------------------------------------------
if ($action == "addtolightbox" && $id) {

  if ($user_info['user_level'] >= USER) {

    $msg = (add_to_lightbox($id)) ? $lang['lightbox_add_success'] : $lang['lightbox_add_error'];
  }
  else {
    $msg = $lang['lightbox_register'];
  }
}
if ($action == "removefromlightbox" && $id) {
$usergroups = array();
  if ($user_info['user_level'] >= USER) {
  $current_time = time();
  $sql = "SELECT group_id  
          FROM ".GROUP_MATCH_TABLE."
          WHERE user_id = ".$user_info['user_id']." 
          AND groupmatch_startdate <= $current_time 
          AND (groupmatch_enddate > $current_time OR groupmatch_enddate = 0)";
  $result = $site_db->query($sql);

  while ($row = $site_db->fetch_array($result)) {
    $usergroups[$row['group_id']] = true;
  }
    $msg = (remove_from_lightbox($id)) ? $lang['lightbox_remove_success'] : $lang['lightbox_remove_error'];
  }
  else {
    $msg = $lang['lightbox_register'];
  }
}
if ($action == "clearlightbox") {
$usergroups = array();
  if ($user_info['user_level'] >= USER) {
  $current_time = time();
  $sql = "SELECT group_id  
          FROM ".GROUP_MATCH_TABLE."
          WHERE user_id = ".$user_info['user_id']." 
          AND groupmatch_startdate <= $current_time 
          AND (groupmatch_enddate > $current_time OR groupmatch_enddate = 0)";
  $result = $site_db->query($sql);

  while ($row = $site_db->fetch_array($result)) {
    $usergroups[$row['group_id']] = true;
  }
    $msg = (clear_lightbox()) ? $lang['lightbox_delete_success'] : $lang['lightbox_delete_error'];
  }
  else {
    $msg = $lang['lightbox_register'];
  }
}

//-----------------------------------------------------
//--- Save Rating -------------------------------------
//-----------------------------------------------------
if ($action == "rateimage" && $id) {
  $rating = intval($HTTP_POST_VARS['rating']);
  $cookie_name = (defined("COOKIE_NAME")) ? COOKIE_NAME : "4images_";
  $cookie_rated = isset($HTTP_COOKIE_VARS[$cookie_name.'rated']) ? explode(" ", stripslashes((string)$HTTP_COOKIE_VARS[$cookie_name.'rated'])) : array();
  if ($rating && $rating <= MAX_RATING && $id) {
    if (!isset($session_info['rated_imgs'])) {
      $session_info['rated_imgs'] = $site_sess->get_session_var("rated_imgs");
    }
    $split_list = array();
    if (!empty($session_info['rated_imgs'])) {
      $split_list = explode(" ", $session_info['rated_imgs']);
    }
    if (!in_array($id, $split_list) && !in_array($id, $cookie_rated)) {
      $session_info['rated_imgs'] .= " ".$id;
      $session_info['rated_imgs'] = trim($session_info['rated_imgs']);
      $site_sess->set_session_var("rated_imgs", $session_info['rated_imgs']);
      $cookie_rated[] = $id;
      $cookie_expire = time() + 60 * 60 * 24 * 4;
      setcookie($cookie_name.'rated', implode(" ", $cookie_rated), $cookie_expire, COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE);
      update_image_rating($id, $rating);
      $msg = $lang['voting_success'];
    }
    else {
      $msg = $lang['already_voted'];
    }
  }
  else {
    $msg = $lang['voting_error'];
  }
}

//-----------------------------------------------------
//--- User Box ----------------------------------------
//-----------------------------------------------------
$usergroups = array();
if ($user_info['user_level'] >= USER) {
$current_time = time();
  $sql = "SELECT group_id  
          FROM ".GROUP_MATCH_TABLE."
          WHERE user_id = ".$user_info['user_id']." 
          AND groupmatch_startdate <= $current_time 
          AND (groupmatch_enddate > $current_time OR groupmatch_enddate = 0)";
  $result = $site_db->query($sql);

  while ($row = $site_db->fetch_array($result)) {
    $usergroups[$row['group_id']] = true;
  }
  $site_template->register_vars("lang_loggedin_msg", preg_replace("/".$site_template->start."loggedin_user_name".$site_template->end."/siU", format_text($user_info['user_name'], 2), $lang['lang_loggedin_msg']));
  $user_box = $site_template->parse_template("user_logininfo");
  $site_template->register_vars(array(
    "user_box" => $user_box,
    "user_loggedin" => 1,
    "user_loggedout" => 0,
    "is_admin" => ($user_info['user_level'] == ADMIN) ? 1 : 0
  ));
  $site_template->un_register_vars("user_logininfo");
  unset($user_box);
}
else {
  $user_box = $site_template->parse_template("user_loginform");
  $site_template->register_vars(array(
    "user_box" => $user_box,
    "user_loggedin" => 0,
    "user_loggedout" => 1,
    "is_admin" => 0
  ));
  $site_template->un_register_vars("user_loginform");
  unset($user_box);
}

if ($csrf_protection_enable && $csrf_protection_frontend) {
    csrf_start(true);
}



//-----------------------------------------------------
//--- Set Images Sort ---------------------------------
//-----------------------------------------------------
if (isset($HTTP_POST_VARS['imagesorder'])) {
  $imagesorder = $HTTP_POST_VARS['imagesorder'];
  if ($imagesorder) {
    $site_sess->set_session_var("imgs_order", $imagesorder);
    $session_info['imgs_order'] = $imagesorder;
  }
}

if (isset($session_info['imgs_order'])) {
  $config['image_order'] = $session_info['imgs_order'];
}

if (isset($HTTP_POST_VARS['imagessort'])) {
 // $imagessort = intval($HTTP_POST_VARS['imagessort']);
 $imagessort = $HTTP_POST_VARS['imagessort']; //Added 03-11-2003
  if ($imagessort) {
    $site_sess->set_session_var("imgs_sort", $imagessort);
    $session_info['imgs_sort'] = $imagessort;
  }
}

if (isset($session_info['imgs_sort'])) {
  $config['image_sort'] = $session_info['imgs_sort'];
}
//----End Images Sort----------------------------------

//-----------------------------------------------------
//--- Set Images Sort Dropdown ------------------------
//-----------------------------------------------------
  $imgsort_dropdown="\n<div class=\"tx-custom-select\" style=\"width:240px;\"><select name=\"imagesorder\" class=\"\">\n";
  foreach ($image_order_optionlist as $key => $val) {
    $imgsort_dropdown .= "<option value=\"$key\"";
    if ($config['image_order'] == $key) {
      $imgsort_dropdown .= " selected=\"selected\"";
    }
    $imgsort_dropdown .= ">".$val."</option>\n";
  }
  $imgsort_dropdown .= "</select></div>\n";
  foreach ($image_sort_optionlist as $key => $val) {
  $imgsort_dropdown .= "<INPUT class=\"form-radio\" type=\"radio\" name=\"imagessort\" value=\"$key\"";
  if ($config['image_sort'] == $key) {
      $imgsort_dropdown .= " checked";
  }
  $imgsort_dropdown .= ">".$val."\n";
  }
if ($cat_id != 0) {
  $imgsort_dropdown .= "<input type=\"hidden\" name=\"cat_id\" value=\"".$cat_id."\" />\n";
}
if (isset($show_result) && $show_result == 1) {
  $imgsort_dropdown .= "<input type=\"hidden\" name=\"show_result\" value=\"1\" />\n";
}
$site_template->register_vars("imgsort_dropdown", $imgsort_dropdown);
$imgsort_dropdown_form = $site_template->parse_template("imgsort_dropdown_form");
$site_template->register_vars("imgsort_dropdown_form", $imgsort_dropdown_form);

$site_template->un_register_vars("imgsort_dropdown");
unset($imgsort_dropdown);
unset($imgsort_dropdown_form);
//-----End Images Sort Dropdown------------------------



//-----------------------------------------------------
//--- MOD Left Quicklist -------------------------------
//-----------------------------------------------------
$left_quicklist = get_left_quicklist($cat_id);
$site_template->register_vars(array(
		"left_quicklist"   => $left_quicklist,
		"lang_left_quicklist" => $lang['left_quicklist']
));
unset($left_quicklist);
// END MOD Left quicklist



?>