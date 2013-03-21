<?php
class svg_playlists {

  private $identifier = "playlists";
  private $title = "Playlists";
  private $order = 20;
  private $hook, $domain, $user;

  // Default methods -----------------------------------------------------------

  public function getIdentifier()
  {
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
    $this->user = get_userdata(get_current_user_id());
  }

  public function setDomain($domain)
  {
    $this->domain = $domain;
    $this->title = __("Playlists", $this->domain);
  }
  
  // Views ---------------------------------------------------------------------
  
  public function viewIndex()
  {
    global $wpdb;
    $url = 'admin.php?page='.$_GET['page'];
    $output = '';
    
    $output .=
      '<table class="widefat">'.
        '<thead>'.
          '<tr>'.
            '<th>'.__('ID', $this->domain).'</th>'.
            '<th>'.__('Playlist name', $this->domain).'</th>'.
            '<th>'.__('# of Videos', $this->domain).'</th>'.
          '</tr>'.
        '</thead>'.
        '<tfoot>'.
          '<tr>'.
            '<th>'.__('ID', $this->domain).'</th>'.
            '<th>'.__('Playlist name', $this->domain).'</th>'.
            '<th>'.__('# of Videos', $this->domain).'</th>'.
          '</tr>'.
        '</tfoot>'.
        '<tbody>';
 
    $data = $wpdb->get_results("SELECT p.*, COUNT(v.video_id) AS total FROM ".$wpdb->prefix."svg_playlist AS p LEFT JOIN ".$wpdb->prefix."svg_video AS v USING(playlist_id) WHERE 1 GROUP BY p.playlist_id ORDER BY p.name", ARRAY_A);
    if (count($data) > 0) {
      foreach($data as $item) {
        $output .=
          '<tr>'.
            '<td>'.$item['playlist_id'].'</td>'.
            '<td class="post-title column-title">'.
              '<strong><a href="'.admin_url($url.'&menu=playlists/editPlaylist&playlist_id='.$item['playlist_id']).'" title="'.__('Edit this playlist', $this->domain).'">'.$item['name'].'</a></strong>'.
              '<span class="edit"><a title="'.__('Edit this playlist', $this->domain).'" href="'.admin_url($url.'&menu=playlists/editPlaylist&playlist_id='.$item['playlist_id']).'">'.__('Edit', $this->domain).'</a></span> | '.
              '<span class="delete">'.
                '<a title="'.__('Delete this playlist', $this->domain).'" href="'.admin_url($url.'&menu=playlists/deletePlaylist&noheader=true&playlist_id='.$item['playlist_id']).'" onclick="javascript:check=confirm(\''.__('Delete this playlist?', $this->domain).'\');if(check==false) return false;">'.__('Delete', $this->domain).'</a>'.
              '</span>'.
            '</td>'.
            '<td>'.$item['total'].'</td>'.
          '</tr>';
      }
    } else {
      $output .= '<tr><td colspan="3">'.__('No playlists yet!', $this->domain).'</td></tr>';
    }
  
    $output .= '</tbody>'.'</table>';
    
    return $output;
  }
  
  public function viewEditPlaylist($action = '')
  {
    global $wpdb;
    $output = '';
    $url = 'admin.php?page='.$_GET['page'];
    
    if (!preg_match('/^([0-9]*)$/sim', $_REQUEST['playlist_id']))
      return __("<b>Error:</b> Video was not found!", $this->domain);
    
    if ($action == 'save') {
      $edit = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."svg_playlist WHERE playlist_id='".$_REQUEST['playlist_id']."' LIMIT 1", ARRAY_A);
      $check_name = (strlen($_REQUEST['name'])>1)?1:0;
      
      if ($check_name) {
        if ($edit) {
          $wpdb->update(
            $wpdb->prefix.'svg_playlist', 
            array(
              'name' => $_REQUEST['name'],
            ),
            array('playlist_id' => $_REQUEST['playlist_id']),
            array('%s'), array('%d')
          );
          update_option($this->domain.'_message', 'updated:'.__('Playlist was sucessfully updated!', $this->domain));
        } else {
          $wpdb->insert(
            $wpdb->prefix.'svg_playlist', 
            array(
              'name' => $_REQUEST['name']
            ), 
            array('%s') 
          );
          update_option($this->domain.'_message', 'updated:'.__('New playlist was added successfully!', $this->domain));
        }
        wp_redirect(admin_url($url.'&menu=playlists'), 301);
      } else {
        update_option($this->domain.'_message', 'error:'.__('Name field was not filled in properly!', $this->domain));
        wp_redirect(admin_url($url.'&menu=playlists/editPlaylist&playlist_id='.$_REQUEST['playlist_id']), 301);
      }
    }

    $edit = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."svg_playlist WHERE playlist_id='".$_REQUEST['playlist_id']."' LIMIT 1", ARRAY_A);
    $playlist_h3 = ($edit)?__('Edit Playlist', $this->domain):__('Add Playlist', $this->domain);
    $output .= 
      '<h3>'.$playlist_h3.'</h3>'.
      '<form method="POST" action="'.admin_url($url.'&menu=playlists/editPlaylist/save&noheader=true').'">'.
        '<input type="hidden" name="playlist_id" value="'.(($edit)?$edit['playlist_id']:'').'"/>'.
        '<table class="form-table"><tbody>'.
          '<tr valign="top">'.
            '<th scope="row"><label for="name">'.__('Playlist name', $this->domain).'<span> *</span>: </label></th>'.
            '<td><input id="name" name="name" type="text" class="regular-text" value="'.(($edit)?$edit['name']:'').'" required="true"/></td>'.
          '</tr>'.
          '<tr><td colspan="2">'.
            '<input type="submit" value="'.__('Save', $this->domain).'" class="button-primary"/> '.
            '<a href="'.admin_url($url.'&menu=playlists').'" title="'.__('Cancel', $this->domain).'" class="button-secondary">'.__('Cancel', $this->domain).'</a>'.
        '</td></tr>';

    $output .= '</table>'.'</form>';
    
    return $output;
  }
  
  public function viewDeletePlaylist()
  {
    global $wpdb;
    $url = 'admin.php?page='.$_GET['page'];

    if (preg_match("/^([0-9]+)+$/sim", $_REQUEST['playlist_id'])) {
      $wpdb->query("DELETE FROM ".$wpdb->prefix."svg_playlist WHERE playlist_id='".$_REQUEST['playlist_id']."' LIMIT 1");
      $wpdb->query("UPDATE ".$wpdb->prefix."svg_video SET playlist_id='0' WHERE playlist_id='".$_REQUEST['playlist_id']."'");
      update_option($this->domain.'_message', 'error:'.__('Playlist was successfuly removed!', $this->domain)); 
    }
    wp_redirect(admin_url($url.'&menu=playlists'), 301);
  }
  
  // Quickies ------------------------------------------------------------------
  
  public function quickIndex()
  {
    $url = 'admin.php?page='.$_GET['page'];
    return '<a class="add-new-h2" href="'.admin_url($url.'&menu=playlists/editPlaylist').'">'.__('Add New', $this->domain).'</a>';
  }
}