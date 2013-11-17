<?php
class svg_configure {

  private $identifier = "configure";
  private $title = "Configure";
  private $permissions = array('administrator');
  private $order = 30;
  private $hook;
  private $domain;

  // Default methods -----------------------------------------------------------

  public function getIdentifier()
  {
    return $this->identifier;
  }

  public function getTitle()
  {
    return $this->title;
  }

  public function getPermissions()
  {
    return $this->permissions;
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
    $this->title = __("Configure", $this->domain);
  }

  // Views ---------------------------------------------------------------------

  public function viewIndex($action = '')
  {
    global $wpdb, $wp_roles;
    $output = '';
    $url = 'admin.php?page='.$_GET['page'];
    
    $embed = array(
      'iframe' => __('IFrame (default)', $this->domain),  // default
      'embed' => __('Embed', $this->domain)
    );
    
    $playlist_line = array(
      '2' => '2 '.__('videos', $this->domain),
      '3' => '3 '.__('videos', $this->domain),
      '4' => '4 '.__('videos', $this->domain), // default
      '5' => '5 '.__('videos', $this->domain),
      '6' => '6 '.__('videos', $this->domain)
    );
    
    $playlist_page = array(
      '6' => '6 '.__('videos', $this->domain),
      '8' => '8 '.__('videos', $this->domain),
      '9' => '9 '.__('videos', $this->domain),
      '10' => '10 '.__('videos', $this->domain),
      '12' => '12 '.__('videos', $this->domain),
      '15' => '15 '.__('videos', $this->domain),
      '20' => '20 '.__('videos', $this->domain), // default
      '21' => '21 '.__('videos', $this->domain),
      '24' => '24 '.__('videos', $this->domain),
      '25' => '25 '.__('videos', $this->domain),
      '30' => '30 '.__('videos', $this->domain),
      '50' => '50 '.__('videos', $this->domain)
    );
    
    $playlist_title = array(
      'yes' => __('Yes', $this->domain),
      'no' => __('No', $this->domain)
    );
    
    $playlist_playback = array(
      'javascript' => __('Javascript Popup') // default
    );
    
    if ($action == 'save') {
      $check_embed = (preg_match("/^(iframe|embed)$/sim", $_REQUEST['embed']))?1:0;
      $check_playlistline = (preg_match("/^([0-9]+)$/sim", $_REQUEST['playlistline']))?1:0;
      $check_playlistpage = (preg_match("/^([0-9]+)$/sim", $_REQUEST['playlistpage']))?1:0;
      $check_playlistthumbnailsize = (preg_match("/^([0-9]+x[0-9]+)$/sim", $_REQUEST['playlistthumbnailsize']))?1:0;
      $check_playlisttitle = (preg_match("/^(yes|no)$/sim", $_REQUEST['playlisttitle']))?1:0;
      $check_playlistplayback = (preg_match("/^(javascript)$/sim", $_REQUEST['playlistplayback']))?1:0;
      
      if ($check_embed && $check_playlistline && $check_playlistpage && $check_playlistthumbnailsize && $check_playlistplayback) {
        update_option($this->domain.'_message', 'updated:'.__("Settings were updated successfully!", $this->domain));
        update_option('svg_playlistline', $_REQUEST['playlistline']);
        update_option('svg_playlistpage', $_REQUEST['playlistpage']);
        update_option('svg_embed', $_REQUEST['embed']);
        update_option('svg_playlistthumbnailsize', $_REQUEST['playlistthumbnailsize']);
        update_option('svg_playlisttitle', $_REQUEST['playlisttitle']);
        update_option('svg_playlistplayback', $_REQUEST['playlistplayback']);

        wp_redirect(admin_url($url), 301);
      } else {
        update_option($this->domain.'_message', 'error:'.__("Settings were not updated! Please check configuration!", $this->domain)); 
        wp_redirect(admin_url($url.'&menu='.$_GET['menu']), 301);
      }
   }

    $output .= '<form method="POST" action="'.admin_url($url.'&menu=configure/index/save&noheader=true').'">'.
      '<h3>'.__('General settings', $this->domain).'</h3>'.
      '<table class="form-table"><tbody>'.
        '<tr valign="top">'.
          '<th scope="row"><label for="embed">'.__('Embedding type', $this->domain).': </label></th>'.
          '<td><select id="embed" name="embed">';
      foreach($embed as $key=>$value) {
        $selected = (get_option('svg_embed', 'iframe') == $key)?' selected="selected"':'';
        $output .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
      }

    $output .= '</select></td>'.
        '</tr>'.
      '</table>'.
      '<h3>'.__('Playlist settings', $this->domain).'</h3>'.
      '<table class="form-table"><tbody>'.
        '<tr valign="top">'.
          '<th scope="row"><label for="playlistline">'.__('Videos per line', $this->domain).': </label></th>'.
          '<td><select id="playlistline" name="playlistline">';
            foreach($playlist_line as $key=>$value) {
              $selected = (get_option('svg_playlistline', '4') == $key)?' selected="selected"':'';
              $output .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
            }
    $output .=
          '</select></td>'.
        '</tr>'.     
        '<tr valign="top">'.
          '<th scope="row"><label for="playlistpage">'.__('Videos per page', $this->domain).': </label></th>'.
          '<td><select id="playlistpage" name="playlistpage">';     
      foreach ($playlist_page as $key => $value) {
        $selected = (get_option('svg_playlistpage', '20') == $key)?' selected="selected"':'';
        $output .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';        
      }
    $output .= '</select>'. 
          '</td>'.
        '</tr>'.
        '<tr valign="top">'.
          '<th scope="row"><label for="playlistthumbnailsize">'.__('Thumbnail size', $this->domain).': </label></th>'.
          '<td>'.
            '<input id="playlistthumbnailsize" name="playlistthumbnailsize" value="'.get_option('svg_playlistthumbnailsize', '150x90').'" type="text" class="regular-text" required="true"/>'.
            '<p class="description">'.__('For example: 150x90', $this->domain).'</p>'.
          '</td>'.
        '</tr>'.
        '<tr valign="top">'.
          '<th scope="row"><label for="playlisttitle">'.__('Show video title', $this->domain).': </label></th>'.
          '<td><select id="playlisttitle" name="playlisttitle">';     
      foreach ($playlist_title as $key => $value) {
        $selected = (get_option('svg_playlisttitle', 'yes') == $key)?' selected="selected"':'';
        $output .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';        
      }
    $output .= '</select>'. 
          '</td>'.
        '</tr>'.
        '<tr valign="top">'.
          '<th scope="row"><label for="playlistplayback">'.__('Video playback', $this->domain).': </label></th>'.
          '<td><select id="playlistplayback" name="playlistplayback">';     
      foreach ($playlist_playback as $key => $value) {
        $selected = (get_option('svg_playlistplayback', 'javascript') == $key)?' selected="selected"':'';
        $output .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';        
      }
    $output .= '</select>'. 
          '</td>'.
        '</tr>'.
        
      '<tr><td colspan="2">'.
        '<input type="submit" value="'.__('Save Settings', $this->domain).'" class="button-primary"/> '.
        '<a href="'.$url.'" title="'.__('Cancel', $this->domain).'" class="button-secondary">'.__('Cancel', $this->domain).'</a>'.
      '</td></tr>'.
      '</table>'.
    '</form>';

    return $output;
  }
}