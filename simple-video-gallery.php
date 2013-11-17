<?php
/*
Plugin Name: Simple Video Gallery
Plugin URI: http://mindomobile.com
Description: Simple video gallery plugin for your blog.
Version: 1.0.1
Author: MindoMobile
License: GPL2
*/

if (!function_exists ('is_admin')) {
   header('Status: 403 Forbidden');
   header('HTTP/1.1 403 Forbidden');
   exit();
}

class SimpleVideoGalleryPlugin
{
   var $name = "Video Gallery";
   var $ver = "1.0.1";
   var $domain = "simplevideogallery";
   var $c_path = "components/";        // components dir
   var $components = array();
   var $hook;
   var $page, $view, $action;
   var $wp_user;

   function SimpleVideoGalleryPlugin ()
   {
      //register_activation_hook(__FILE__, array($this, 'admin_install'));
      
      $this->loadComponents();
      add_action('admin_menu', array(&$this, 'admin_menu'));
      add_action('admin_init', array(&$this, 'admin_install'));

      // Shortcodes
      add_shortcode('svgvideo', array(&$this, 'getVideoShortcode'));
      add_shortcode('svgplaylist', array(&$this, 'getPlaylistShortcode'));

      // Ajax request handling
      add_action('wp_ajax_nopriv_svg-request', array(&$this, 'ajax_request'));
      add_action('wp_ajax_svg-request', array(&$this, 'ajax_request'));
      
      $menu = explode("/", $_GET['menu']);
      $this->page = (preg_match("/^([a-z\-]+)+$/sim", $menu[0]))?$menu[0]:'default';
      $this->view = (preg_match("/^([a-z\-]+)+$/sim", $menu[1]))?$menu[1]:'index';
      $this->action = (preg_match("/^([a-z\-]+)+$/sim", $menu[2]))?$menu[2]:'';

      load_plugin_textdomain($this->domain, false, basename(dirname( __FILE__ )).'/languages');
   }
   
   function admin_install()
   {
      global $wpdb;
      
      if (get_option($this->domain.'_version') < $this->ver) {
         $sql_file = file_get_contents('simple-video-gallery.sql', true);
         $sql_file = str_replace("[video]", $wpdb->prefix."svg_video", $sql_file);
         $sql_file = str_replace('[playlist]', $wpdb->prefix.'svg_playlist', $sql_file);
         $sql_bits = explode(";\n", $sql_file);
         foreach($sql_bits as $bit) $wpdb->query($bit);

         update_option($this->domain.'_version', $this->ver);
      }
   }

   function admin_menu()
   {
      $this->wp_user = get_userdata(get_current_user_id());
      $this->hook = add_menu_page($this->name, $this->name, $this->getPluginPermissionStatus(), $this->domain, array(&$this, 'admin_index'));
      add_action('load-'.$this->hook, array(&$this, 'on_load_page'));
      wp_enqueue_style($this->domain.'-style', plugins_url('css/style.css', __FILE__));
   }

   function on_load_page() {
      wp_enqueue_script('common');
      wp_enqueue_script('wp-lists');
      wp_enqueue_script('postbox');

      /* load metaboxes */
      foreach ($this->components as $item) {
         $item->setHook($this->hook);        // Set hook
         $item->setDomain($this->domain);    // Set domain
         if (method_exists($item, "load_meta_boxes")) {
            $item->load_meta_boxes();
         }
      }
   }

   function admin_index()
   {
      $page_title = $this->getPageTitle($this->page);
      $message = get_option($this->domain.'_message', '');
      $output = "";
      if ($message) {
         list ($type, $msg) = explode(":", $message);
         $message = '<div id="message" class="'.$type.'">'.
            '<p>'.$msg.'</p>'.
         '</div>';
         update_option($this->domain.'_message', '');
      }

      /* load page, view */
      $content = $quick_action = '';
      if (array_key_exists($this->page, $this->components)) {
         $permission_error = 0;
         if (method_exists($this->components[$this->page], 'getPermissions')) {
            $class_at_hand = $this->components[$this->page];
            if (!$this->getPermissionStatus($this->wp_user->roles, $class_at_hand->getPermissions()))
               $permission_error = 1;
         }
         if (method_exists($this->components[$this->page], "view".ucfirst($this->view)) && $permission_error == 0) {
            if (method_exists($this->components[$this->page], "quick".ucfirst($this->view))) {
               $quick_action = call_user_func(array($this->components[$this->page], "quick".ucfirst($this->view)), $this->action);
            }
            $content .= call_user_func(array($this->components[$this->page], "view".ucfirst($this->view)), $this->action);
         } else {
            $content .= __("<b>Error:</b> View is not found!", $this->domain);
         }
      } else {
         $content .= __("<b>Error:</b> Component is not found!", $this->domain);
      }
      
      $output .=
         '<div class="wrap">'.
            '<div id="icon-edit" class="icon32"><br></div>'.
            '<h2>'.$page_title.$quick_action.'</h2>'.
            $message.
            $this->createMenu($this->page);
      $output .= $content;
      $output .= '</div>';

      echo $output;  // Print output
   }

   function getVideoShortcode($atts, $content = null) {
      /*
       atts:
         id="1" video id as given in the list
         width="560" width of the video (560px)
         height="315" height of the video (315px)
      */

      global $wpdb;
      $width = (preg_match("/^([0-9]+)$/", $atts['width']))?$atts['width']:560;
      $height = (preg_match("/^([0-9]+)$/", $atts['width']))?$atts['height']:315;
      
      $embed = $iframe = '';
      
      $video = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."svg_video WHERE video_id='".$atts['id']."' LIMIT 1", ARRAY_A);  
      
      
      $output .= '<div id="svg-video">'.$this->getVideoPayload($video, $width, $height).'</div>';
      
      return $output;
   }
   
   // --------------------------------------------------------------------------
   
   function getPlaylistShortcode($atts, $content = null)
   {
      /*
       atts:
         id="1" video id as given in the list
      */
      global $wpdb;
      
      // CSS
      wp_enqueue_style('wp-jquery-ui-dialog');
      $css_file = get_option("svg_css", 'default');
      
      wp_enqueue_style($css_file, plugins_url( '/css/'.$css_file.'.css', __FILE__ ));
      // Script
      wp_enqueue_script('svg-video', plugins_url('/js/svg-video.js', __FILE__), array('jquery-ui-dialog'));
      wp_localize_script('svg-video', 'svgAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
      
      // Other options
      list ($width, $height) = explode("x", get_option('svg_playlistthumbnailsize', '150x90'));
      
      $row_limit = get_option('svg_playlistline', '4');
      $title = get_option('svg_playlisttitle', 'yes');
      
      // Paging
      $pagenum = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
      $limit = get_option('svg_playlistpage', '20');
      $offset = ( $pagenum - 1 ) * $limit;
      
      $playlist = '';
      
      $videos = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."svg_playlist AS playlist LEFT JOIN ".$wpdb->prefix."svg_video AS video USING(playlist_id) WHERE playlist_id='".$atts['id']."' LIMIT $offset, $limit", ARRAY_A);
      if (count($videos) > 0) {
         $counter = 1;
         foreach ($videos as $video ) {
            $video_title = ($title == 'yes')?'<div class="svg-video-item-title" style="width: '.$width.'px;"><a href="#" class="playVideo" title="'.stripslashes($video['name']).'" video="'.$video['video_id'].'">'.stripslashes($video['name']).'</a></div>':'';
            $playlist .= '<div class="svg-video-item'.(($counter != $row_limit)?' svg-video-item-padding':'').'">'.
                           '<img src="'.$video['image'].'" alt="'.$video['name'].'" title="'.$video['name'].'" width="'.$width.'" height="'.$height.'"/>'.
                           $video_title.
                         '</div>';
            if ($counter == $row_limit) {
               $playlist .= '<div style="clear:both"></div>';
               $counter = 1;
            } else {
               $counter++;
            }
         }
      }
      
      $total = $wpdb->get_var("SELECT COUNT(video_id) FROM ".$wpdb->prefix."svg_video WHERE playlist_id='".$atts['id']."'");
      $pages = ceil($total/$limit);

      $page_links = paginate_links(array(
         'base' => add_query_arg( 'page', '%#%' ),
         'format' => '',
         'prev_text' => __( '&laquo;', 'aag' ),
         'next_text' => __( '&raquo;', 'aag' ),
         'total' => $pages,
         'current' => $pagenum
      ) );
      
      
      
      $output .=
         '<div id="svg-playlist">'.
            $playlist.
            '<div id="svg-page">'.$page_links.'</div>'.
         '</div>';     
      return $output;
   }
   
   // --------------------------------------------------------------------------
   
   function getVideoPayload($video, $width, $height) {
      switch ($video['provider']) {
         case 'youtube.com':
            preg_match('/v\=(.*)?/sim', $video['link'], $m);
            
            $embed =
               '<object width="'.$width.'" height="'.$height.'" type="application/x-shockwave-flash">'.
                  '<param name="movie" value="http://www.youtube.com/v/'.$m[1].'" />'.
                  '<!--[if IE]>'.
                  '<param name="wmode" value="transparent" />'.
                  '<embed src="http://www.youtube.com/v/'.$m[1].'" type="application/x-shockwave-flash" wmode="transparent" width="'.$width.'" height="'.$height.'" />'.
                  '<![endif]-->'.
               '</object>';
               
            $iframe = '<iframe width="'.$width.'" height="'.$height.'" src="http://www.youtube.com/embed/'.$m[1].'" frameborder="0" allowfullscreen></iframe>';
            
            break;
      }
      
      $embedding_options = get_option('svg_embed', 'iframe');
      $code = ($embedding_options == 'iframe')?$iframe:$embed;
      
      return '<div class="svg-video-object">'.$code.'</div>';
   }
   
   // --------------------------------------------------------------------------
   
   function ajax_request()
   {
      global $wpdb;
      
      // Exit if video_id is not int
      if (!preg_match("/^(\d+)$/sim", $_REQUEST['video_id'])) exit;
      
      $video = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."svg_video WHERE video_id='".$_REQUEST['video_id']."' LIMIT 1", ARRAY_A);
      echo $this->getVideoPayload($video, 560, 315);
      echo '<div class="svg-video-description">'.stripslashes($video['description']).'</div>';
      exit;
   }
   
   // --------------------------------------------------------------------------

   function loadComponents()
   {
      $files = scandir(dirname( __FILE__ )."/".$this->c_path);
      foreach ($files as $file) {
         if ($file != "." && $file != "..") {
            include_once $this->c_path.$file;
            $class_name = substr($file, 0, -4);
            $class = new $class_name;
            $this->components[$class->getIdentifier()] = $class;
         }
      }
   }

   function createMenu($selected = 'default')
   {
      $url_print = 'admin.php?page='.$_GET['page'];
      $selected = ($selected == '')?'default':$selected;
      $count = 1;
      
      /* order menu first */
      $ordered = array();      
      foreach ($this->components as $item) {
         if (method_exists($item, 'getPermissions')) {
            if ($this->getPermissionStatus($this->wp_user->roles, $item->getPermissions()))
               $ordered[$item->getOrder()] = $item;
         } else {
            $ordered[$item->getOrder()] = $item;
         }
      }
      ksort($ordered);

      /* create menu */
      $menu = '<div class="'.$this->domain.'_menu"><ul>';
      foreach ($ordered as $item) {
         $separator = ($count < count($ordered))?' | ':'';
         
         if ($item->getIdentifier() == $selected) {
            $menu .= '<li><strong>'.$item->getTitle().'</strong>'.$separator.'</li>';
         } else {
            $url = 'admin.php?page='.$this->domain;
            $url .= ($item->getIdentifier() != 'default')?'&menu='.$item->getIdentifier():'';
            $menu .= '<li><a href="'.$url.'" title="'.$item->getTitle().'">'.$item->getTitle().'</a>'.$separator.'</li>';
         }
         
         $count++;
      }
      $menu .= '</ul>';
      $menu .= '</div>';

      return $menu;
   }

   function getPageTitle($identifier)
   {
      $title = '';
      if (array_key_exists($identifier, $this->components) && $identifier != 'default') 
         $title = ': '.$this->components[$identifier]->getTitle();

      return $this->name.$title;
   }

   function getPermissionStatus($user_roles, $role_haystack)
   {
      foreach ($user_roles as $role) {
         if (in_array($role, $role_haystack))
            return true;
      }
      
      return false;
   }

   function getPluginPermissionStatus()
   {
      foreach ($this->wp_user->roles as $role) {
         if (get_option('wpf_role_'.$role, 0) == 1) {
            return $role;
         }
      }
      
      return 'administrator';
   }
   
}

$simplevideogalleryplugin = new SimpleVideoGalleryPlugin();
?>
