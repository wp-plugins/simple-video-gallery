<?php
class svg_default {

  private $identifier = "default";
  private $title = "Videos";
  private $order = 1;
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
    $this->title = __("Videos", $this->domain);
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
            '<th>'.__('Title', $this->domain).'</th>'.
            '<th>'.__('External URL', $this->domain).'</th>'.
            '<th>'.__('Featured', $this->domain).'</th>'.
            '<th>'.__('Added on', $this->domain).'</th>'.
          '</tr>'.
        '</thead>'.
        '<tfoot>'.
          '<tr>'.
            '<th>'.__('ID', $this->domain).'</th>'.
            '<th>'.__('Title', $this->domain).'</th>'.
            '<th>'.__('External URL', $this->domain).'</th>'.
            '<th>'.__('Featured', $this->domain).'</th>'.
            '<th>'.__('Added on', $this->domain).'</th>'.
          '</tr>'.
        '</tfoot>'.
        '<tbody>';
 
    $data = $wpdb->get_results("SELECT video_id, name, link, featured, date_added FROM ".$wpdb->prefix."svg_video WHERE 1 ORDER BY video_id DESC", ARRAY_A);
    if (count($data) > 0) {
      foreach($data as $item) {
        $output .=
          '<tr>'.
            '<td>'.$item['video_id'].'</td>'.
            '<td class="post-title column-title">'.
              '<strong><a href="'.admin_url($url.'&menu=default/editVideo&video_id='.$item['video_id']).'" title="'.__('Edit this video', $this->domain).'">'.stripslashes($item['name']).'</a></strong>'.
              '<span class="edit"><a title="'.__('Edit this video', $this->domain).'" href="'.admin_url($url.'&menu=default/editVideo&video_id='.$item['video_id']).'">'.__('Edit', $this->domain).'</a></span> | '.
              '<span class="delete">'.
                '<a title="'.__('Delete this video', $this->domain).'" href="'.admin_url($url.'&menu=default/deleteVideo&noheader=true&video_id='.$item['video_id']).'" onclick="javascript:check=confirm(\''.__('Delete this video?', $this->domain).'\');if(check==false) return false;">'.__('Delete', $this->domain).'</a>'.
              '</span>'.
            '</td>'.
            '<td>'.$item['link'].'</td>'.
            '<td>'.(($item['featured'] == 1)?__('Yes', $this->domain):__('No', $this->domain)).'</td>'.
            '<td>'.substr($item['date_added'], 0, 10).'</td>'.
          '</tr>';
      }
    } else {
      $output .= '<tr><td colspan="5">'.__('No videos yet!', $this->domain).'</td></tr>';
    }
  
    $output .= '</tbody>'.'</table>';
    
    return $output;
  }
  
  public function viewEditVideo($action = '')
  {
    global $wpdb;
    $output = '';
    $url = 'admin.php?page='.$_GET['page'];

    if (!preg_match('/^([0-9]+)$/sim', $_REQUEST['video_id']))
      return __("<b>Error:</b> Video was not found!", $this->domain);
    
    if ($action == 'save') {
      $edit = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."svg_video WHERE video_id='".$_REQUEST['video_id']."' LIMIT 1", ARRAY_A);
      $check_name = (strlen($_REQUEST['name'])>1)?1:0;
      $check_playlist = (preg_match("/^([0-9]+)+$/sim", $_REQUEST['playlist']))?1:0;
      $check_id = ($edit)?1:0;
      
      if ($check_name == 1 && $check_playlist == 1 && $check_id == 1) {
        $wpdb->update(
          $wpdb->prefix.'svg_video', 
          array(
            'name' => $_REQUEST['name'],
            'playlist_id' => $_REQUEST['playlist'],
            'featured' => ($_REQUEST['featured'])?1:0,
            'description' => $_REQUEST['description']
          ),
          array('video_id' => $_REQUEST['video_id']),
          array('%s', '%d', '%s', '%s'), array('%d')
        );
        update_option($this->domain.'_message', 'updated:'.__('Video was sucessfully updated!', $this->domain));
      }
      wp_redirect(admin_url($url), 301);
    }
 
    $edit = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."svg_video WHERE video_id='".$_REQUEST['video_id']."' LIMIT 1", ARRAY_A);
    if (!$edit)
      return __("<b>Error:</b> Video was not found!", $this->domain);
    
    $playlist = $wpdb->get_results("SELECT playlist_id, name FROM ".$wpdb->prefix."svg_playlist WHERE 1 ORDER BY name ASC", ARRAY_A);
    
    $featured_checkbox = ($edit['featured'])?'checked':'';
    $output .=
      '<h3>'.__('Edit Video', $this->domain).'</h3>'.
      '<form method="POST" action="'.admin_url($url.'&menu=default/editVideo/save&noheader=true').'">'.
        '<input type="hidden" name="video_id" value="'.$edit['video_id'].'"/>'.
        '<table class="form-table"><tbody>'.
          '<tr valign="top">'.
            '<th scope="row"><label for="name">'.__('Title', $this->domain).'<span> *</span>: </label></th>'.
            '<td><input id="name" name="name" type="text" class="regular-text" value="'.stripslashes($edit['name']).'" required="true"/></td>'.
          '</tr>'.
          '<tr valign="top">'.
            '<th scope="row"><label for="playlist">'.__('Playlist', $this->domain).':</label></th>'.
            '<td>'.
              '<select name="playlist" id="playlist">'.
                '<option value="0"></option>';
    if (count($playlist))
      foreach($playlist as $item) {
        $selected = ($item['playlist_id'] == $edit['playlist_id'])?' selected="selected"':'';
        $output .= '<option value="'.$item['playlist_id'].'"'.$selected.'>'.$item['name'].'</option>';
      }
    $output .= '</select>'.
            '</td>'.
          '</tr>'.
          '<tr valign="top">'.
            '<th scope="row"><label for="featured">'.__('Featured', $this->domain).':</label></th>'.
            '<td><input id="featured" name="featured" type="checkbox" '.$featured_checkbox.'/></td>'.
          '</tr>'.
          '<tr valign="top">'.
            '<th scope="row"><label for="description">'.__('Description', $this->domain).': </label></th>'.
            '<td><textarea id="description" name="description" class="large-text code">'.stripslashes($edit['description']).'</textarea></td>'.
          '</tr>'.
          '<tr><td colspan="2">'.
            '<input type="submit" value="'.__('Save', $this->domain).'" class="button-primary"/> '.
            '<a href="'.admin_url($url).'" title="'.__('Cancel', $this->domain).'" class="button-secondary">'.__('Cancel', $this->domain).'</a>'.
        '</td></tr>';

    $output .= '</table>'.'</form>';
    
    return $output;
  }
  
  public function viewAddVideo($action = '')
  {
    global $wpdb;
    $output = '';
    $url = 'admin.php?page='.$_GET['page'];
    
    $media_libraries = array('youtube.com');
    
    if ($action == 'save') {
      $host = str_replace('www.', '', parse_url($_REQUEST['video_url'], PHP_URL_HOST));
      if (in_array($host, $media_libraries)) {
        $data = $this->get_youtube_data($_REQUEST['video_url']);
        if (count($data) > 0) {  
          $wpdb->insert(
            $wpdb->prefix.'svg_video', 
            array(
              'provider' => 'youtube.com',
              'name' => $data[0]['title'],
              'description' => $data[0]['description'],
              'duration' => $data[0]['duration']['SECONDS'],
              'image' => $data[0]['thumbnail'][0],
              'link' => $_REQUEST['video_url'],
              'playlist_id' => $_REQUEST['playlist'],
              'featured' => '0',
              'user_id' => $this->user->ID
            ), 
            array('%s', '%s', '%s', '%d', '%s', '%s', '%d','%d', '%d') 
          );
          update_option($this->domain.'_message', 'updated:'.__('New video was successfully added!', $this->domain));
          wp_redirect(admin_url($url.'&menu=default/editVideo&video_id='.$wpdb->insert_id), 301);
        } else {
          update_option($this->domain.'_message', 'error:'.__('Invalid link!', $this->domain));
          wp_redirect(admin_url($url.'&menu=default/addVideo'), 301);  
        }
      } else {
        wp_redirect(admin_url($url), 301);
      }
    }

    $playlist = $wpdb->get_results("SELECT playlist_id, name FROM ".$wpdb->prefix."svg_playlist WHERE 1 ORDER BY name ASC", ARRAY_A);
    
    $output .=
      '<h3>'.__('Add Video', $this->domain).'</h3>'.
      '<form method="POST" action="'.admin_url($url.'&menu=default/addVideo/save&noheader=true').'">'.
        '<table class="form-table"><tbody>'.
          '<tr valign="top">'.
            '<th scope="row"><label for="name">'.__('Youtube Video Url', $this->domain).'<span> *</span>: </label></th>'.
            '<td>'.
              '<input id="video_url" name="video_url" type="text" class="regular-text" value="" required="true"/>'.
              '<p class="description">'.__('E.g. http://www.youtube.com/watch?v=ABC', $this->domain).'</p>'.
            '</td>'.
          '</tr>'.
          '<tr valign="top">'.
            '<th scope="row"><label for="playlist">'.__('Playlist', $this->domain).':</label></th>'.
            '<td>'.
              '<select name="playlist" id="playlist">'.
                '<option value="0"></option>';
    if (count($playlist))
      foreach($playlist as $item) {
        $selected = ($item['playlist_id'] == $edit['playlist_id'])?' selected="selected"':'';
        $output .= '<option value="'.$item['playlist_id'].'"'.$selected.'>'.$item['name'].'</option>';
      }
    
      $output .= '</select>'.
            '</td>'.
          '</tr>'.
          '<tr><td colspan="2">'.
            '<input type="submit" value="'.__('Save', $this->domain).'" class="button-primary"/> '.
            '<a href="'.admin_url($url).'" title="'.__('Cancel', $this->domain).'" class="button-secondary">'.__('Cancel', $this->domain).'</a>'.
        '</td></tr>';
        
    $output .= '</table>'.'</form>';
    
    return $output;
  }
  
  public function viewDeleteVideo()
  {
    global $wpdb;
    $url = 'admin.php?page='.$_GET['page'];

    if (preg_match("/^([0-9]+)+$/sim", $_REQUEST['video_id'])) {
      $wpdb->query("DELETE FROM ".$wpdb->prefix."svg_video WHERE video_id='".$_REQUEST['video_id']."' LIMIT 1");
      update_option($this->domain.'_message', 'error:'.__('Video was successfuly removed!', $this->domain)); 
    }
    
    wp_redirect(admin_url($url), 301);
  }
  
  // Quickies ------------------------------------------------------------------
  
  public function quickIndex()
  {
    $url = 'admin.php?page='.$_GET['page'];
    return '<a class="add-new-h2" href="'.admin_url($url.'&menu=default/addVideo').'">'.__('Add New', $this->domain).'</a>';
  }
 
  // Libraries -----------------------------------------------------------------
  
  function get_youtube_data($url = '')
  {    
    $results = array();
    
    $pattern = "@youtube.com\/watch\?v=([0-9a-zA-Z_-]*)@i";
    if (preg_match($pattern, $url, $match)) {
        if (!empty($match)) {
            $xml_url = 'http://gdata.youtube.com/feeds/api/videos/'.$match[1];
            $content = $this->get_url($xml_url);
            $results = $this->parse_youtube_xml($content);
        }
    }
    
    return $results;
  }

  function get_url($url)
  {
    if (function_exists('curl_init')) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $xml = curl_exec($ch);
      curl_close($ch);
    } else {
        $xml = @file_get_contents($url);
    }
    return $xml;
  }

  function parse_youtube_xml($ytVideoXML)
  {
    // Create parser, fill it with xml then delete it
    $yt_xml_parser = xml_parser_create();
    xml_parse_into_struct($yt_xml_parser, $ytVideoXML, $yt_vals);
    xml_parser_free($yt_xml_parser);
    // Init individual entry array and list array
    $yt_video = array();
    $yt_video['thumbnail'] = array();
    $yt_vidlist = array();

    // is_entry tests if an entry is processing
    $is_entry = true;
    // is_author tests if an author tag is processing
    $is_author = false;
    foreach ($yt_vals as $yt_elem) {

        // If no entry is being processed and tag is not start of entry, skip tag
        if (!$is_entry && $yt_elem['tag'] != 'ENTRY')
            continue;

        // Processed tag
        switch ($yt_elem['tag']) {
            case 'ENTRY' :
                if ($yt_elem['type'] == 'open') {
                    $is_entry = true;
                    $yt_video = array();
                } else {
                    $yt_vidlist[] = $yt_video;
                    $is_entry = false;
                }
                break;
            case 'ID' :
                $yt_video['id'] = substr($yt_elem['value'], -11);
                $yt_video['link'] = $yt_elem['value'];
                break;
            case 'PUBLISHED' :
                $yt_video['published'] = substr($yt_elem['value'], 0, 10) . ' ' . substr($yt_elem['value'], 11, 8);
                break;
            case 'UPDATED' :
                $yt_video['updated'] = substr($yt_elem['value'], 0, 10) . ' ' . substr($yt_elem['value'], 11, 8);
                break;
            case 'MEDIA:TITLE' :
                $yt_video['title'] = $yt_elem['value'];
                break;
            case 'MEDIA:KEYWORDS' :
                $yt_video['tags'] = $yt_elem['value'];
                break;
            case 'MEDIA:DESCRIPTION' :
                $yt_video['description'] = $yt_elem['value'];
                break;
            case 'MEDIA:CATEGORY' :
                $yt_video['category'] = $yt_elem['value'];
                break;
            case 'YT:DURATION' :
                $yt_video['duration'] = $yt_elem['attributes'];
                break;
            case 'MEDIA:THUMBNAIL' :
                $yt_video['thumbnail'][] = $yt_elem['attributes']['URL'];
                break;
            case 'YT:STATISTICS' :
                $yt_video['viewed'] = $yt_elem['attributes']['VIEWCOUNT'];
                break;
            case 'GD:RATING' :
                $yt_video['rating'] = $yt_elem['attributes'];
                break;
            case 'AUTHOR' :
                $is_author = ($yt_elem['type'] == 'open');
                break;
            case 'NAME' :
                if ($is_author)
                    $yt_video['author_name'] = $yt_elem['value'];
                break;
            case 'URI' :
                if ($is_author)
                    $yt_video['author_uri'] = $yt_elem['value'];
                break;
            default :
        }
    }
    unset($yt_vals);
    return $yt_vidlist;
  }
}