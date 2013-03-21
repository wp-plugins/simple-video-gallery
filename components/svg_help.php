<?php
class svg_help {

  private $identifier = "help";
  private $title = "Help";
  private $order = 90;
  private $hook, $domain;

  // Default methods -----------------------------------------------------------

  public function load_meta_boxes() {
    add_meta_box('about', __('About Simple Video Gallery', $this->domain), array(&$this, 'about_metabox'), $this->hook, 'normal', 'core');
    add_meta_box('shortcode', __('Shortcodes', $this->domain), array(&$this, 'shortcode_metabox'), $this->hook, 'normal', 'core');
  }

  public function getIdentifier() {
    return $this->identifier;
  }

  public function getTitle()
  {
    return $this->title;
  }

  public function getOrder()
  {
    return $this->order;
  }

  public function setHook($hook)
  {
    $this->hook = $hook."-".$this->identifier;
  }

  public function setDomain($domain)
  {
    $this->domain = $domain;
    $this->title = __("Help", $this->domain);
  }

  /* local implementation of do_meta_boxes */
  function do_meta_boxes($page, $context, $object)
  { 
    global $wp_meta_boxes; 
    static $already_sorted = false; 
    $output = '';
 
    $hidden = get_hidden_meta_boxes($page); 

    $output .= "<div id='$context-sortables' class='meta-box-sortables'>\n"; 

    $i = 0; 
    do { 
      // Grab the ones the user has manually sorted. Pull them out of their previous context/priority and into the one the user chose 
      if ( !$already_sorted && $sorted = get_user_option( "meta-box-order_$page" ) ) { 
        foreach ( $sorted as $box_context => $ids ) 
          foreach ( explode(',', $ids) as $id ) 
            if ( $id ) 
              add_meta_box( $id, null, null, $page, $box_context, 'sorted' ); 
      } 

      $already_sorted = true; 
      if ( !isset($wp_meta_boxes) || !isset($wp_meta_boxes[$page]) || !isset($wp_meta_boxes[$page][$context]) ) 
        break; 

      foreach ( array('high', 'sorted', 'core', 'default', 'low') as $priority ) { 
        if ( isset($wp_meta_boxes[$page][$context][$priority]) ) { 
          foreach ( (array) $wp_meta_boxes[$page][$context][$priority] as $box ) { 
            if ( false == $box || ! $box['title'] ) 
              continue; 

            $i++;
            $style = ''; 
            if ( in_array($box['id'], $hidden) ) 
              $style = 'style="display:none;"'; 

            $output .= '<div id="' . $box['id'] . '" class="postbox ' . postbox_classes($box['id'], $page) . '" ' . $style . '>' . "\n"; 
            $output .= '<div class="handlediv" title="' . __('Click to toggle') . '"><br /></div>'; 
            $output .= "<h3 class='hndle'><span>{$box['title']}</span></h3>\n"; 
            $output .= '<div class="inside">' . "\n"; 
            $output .= call_user_func($box['callback'], $object, $box); 
            $output .= "</div>\n"; 
            $output .= "</div>\n"; 
          } 
        } 
      } 
    } while(0); 

    $output .= "</div>"; 
    
    return $output;
  }

  // Views ---------------------------------------------------------------------

  public function viewIndex() {
    $output .= '<div id="metaboxes-general" class="metabox-holder" style="padding-top: 0px;">';
    $output .= $this->do_meta_boxes($this->hook, 'normal', $data);
    $output .= '</div>';
    return $output;
  }

  // Metaboxes -----------------------------------------------------------------

  public function about_metabox ()
  {
    $output .= '<div style="line-height: 1.7;">';
    $output .= '<b>'.__('Plugin Name', $this->domain).':</b> Simple Video Gallery<br/>';
    $output .= '<b>'.__('Version', $this->domain).':</b> '.get_option($this->domain.'_version').'<br/>';
    $output .= '<b>'.__('Languages availble', $this->domain).':</b> English, Lithuanian<br/>';
    $output .= '<b>'.__('Website', $this->domain).':</b> <a href="http://mindomobile.com" target="_new">MindoMobile.com</a><br/>';
    $output .= '<b>'.__('Support email', $this->domain).':</b> support@mindomobile.com<br/>';
    //$output .= '<b>'.__('Support forum', $this->domain).':</b> '.'<a href="http://wordpress.org/support/plugin/wp-finance" target="_new">wordpress.org forum</a><br/>';
    $output .= '<iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2FMindoMobileSolutions&amp;width=400&amp;colorscheme=light&amp;show_faces=false&amp;border_color&amp;stream=false&amp;header=false&amp;height=25" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:400px; height:25px; padding-top: 4px;" allowTransparency="true"></iframe>';
    $output .= "</div>";
    
    return $output;      
  }

  public function shortcode_metabox()
  {
    $output .= '<p>';
    $output .= __('Simple Video Gallery has two different shortcodes to accommodate single video and playlist embedding into the blog post or custom page.',
      $this->domain);
    $output .= '</p>';
    $output .= '<p>';
    $output .= '<b>'.__('Shortcode for single video', $this->domain).':</b> [svgvideo]<br/>';
    $output .= __('Parameters', $this->domain).':<br/>';
    $output .= '&nbsp;&nbsp;&nbsp;&nbsp;<i>id="1"</i> '.__("video id as given in the list", $this->domain).'<br/>'.
               '&nbsp;&nbsp;&nbsp;&nbsp;<i>width="560"</i> '.__("width of the video (560px)", $this->domain).'<br/>'.
               '&nbsp;&nbsp;&nbsp;&nbsp;<i>height="315"</i> '.__("height of the video (315px)", $this->domain).'<br/>';
    $output .= '</p>';
    
    $output .= '<p>';
    $output .= '<b>'.__('Shortcode embed playlist', $this->domain).':</b> [svgplaylist]<br/>';
    $output .= __('Parameters', $this->domain).':<br/>';
    $output .= '&nbsp;&nbsp;&nbsp;&nbsp;<i>id="1"</i> '.__("playlist id as given in the list", $this->domain).'<br/>';
    $output .= '</p>';
    
    $output .= '<p>';
    $output .= '<b>'.__('Examples', $this->domain).':</b><br/>';
    $output .= '[svgvideo id="1" width="560" height="315"]<br/>';
    $output .= '[svgplaylist id="1"]';
    $output .= '</p>';
    
    
    return $output;
  }
}
?>