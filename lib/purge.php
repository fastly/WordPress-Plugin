<?php

/**
 * Fastly Purger - Detects changes in content and automatically sends purge requests.
 * @package Fastly
 * @author Ryan Sandor Richards
 * @copyright 2011 Fastly.com, All Rights Reserved
 */
class FastlyPurge {
  /** 
   * Default constructor - configures purge listeners.
   */
  function FastlyPurge() {
    // Posts and Pages
    add_action('edit_post', array(&$this, 'purgePost'), 99);
    add_action('edit_post', array(&$this, 'purgePostDependencies'), 99);
    add_action('transition_post_status', array(&$this,'purgePostStatus'),99, 3);
    add_action('deleted_post', array(&$this, 'purgePost'), 99);
    add_action('deleted_post', array(&$this, 'purgeCommon'), 99);
    
    // Comments
    add_action('comment_post', array(&$this, 'purgeComments'),99);
    add_action('edit_comment', array(&$this, 'purgeComments'),99);
    add_action('trashed_comment', array(&$this, 'purgeComments'),99);
    add_action('untrashed_comment', array(&$this, 'purgeComments'),99);
    add_action('deleted_comment', array(&$this, 'purgeComments'),99);
    
    // Full purges (theme changes, etc.)
    add_action('switch_theme', array(&$this, 'purgeAll'), 99);
    add_action('update_option_sidebars_widgets', array(&$this, 'purgeAll'), 99);
    add_action('widgets.php', array(&$this, 'purgeAll'), 99);
    add_action("update_option_theme_mods_".get_option('stylesheet'), array(&$this, 'purgeAll'), 99);
    
    // Links
    add_action("deleted_link",array(&$this, 'purgeLinks'), 99);
    add_action("edit_link",array(&$this, 'purgeLinks'), 99);
    add_action("add_link",array(&$this, 'purgeLinks'), 99);
    
    // Categories
    add_action("edit_category",array(&$this, 'purgeCategory'), 99);       
    add_action("edit_link_category",array(&$this, 'purgeLinkCategory'), 99);
    add_action("edit_post_tag",array(&$this, 'purgeTagCategory'), 99);
    
    // Setup API
    $this->api = new FastlyAPI(
      get_option('fastly_api_key'), 
      get_option('fastly_api_hostname'),
      get_option('fastly_api_port')
    );
  }
  
  /**
   * Sends a purge request for the given url.
   * @param $url URL to purge from the cache server.
   */
  function purge($urls) {
    $this->api->purge($urls);   
  }
  
  /**
   * Purges all pages on the site.
   */
  function purgeAll() {
    $this->api->purgeAll( get_option('fastly_service_id') );
  }
  
  /**
   * Purges common pages.
   */
  function purgeCommon($call=true) {
    $urls = array(get_bloginfo('wpurl')."/", get_bloginfo('wpurl')."/feed", get_bloginfo('wpurl')."/feed/atom");

    // TODO Need Regex Support
    /*
      if (get_site_option($this->wpv_update_pagenavi_optname) == 1) {
         $this->WPVarnishPurgerPurgeObject("/page/(.*)");
      }
    */
    return $call ? $this->purge($urls) : $urls;
  }
  
  /**
   * Purges posts and pages on update.
   */
  function purgePost($postId, $call=true) {
    $urls = array( get_permalink($postId) );
    return $call ? $this->purge($urls) : $urls;
  }

  /**
   * Purges objects that depend on the post.
   */
  function purgePostDependencies($postId, $call=true) { 
    $urls = array();
    $urls = array_merge($urls, $this->purgeCommon(false));
    $urls = array_merge($urls, $this->purgeCategories($postId, false));
    $urls = array_merge($urls, $this->purgeArchives($postId, false));
    $urls = array_merge($urls, $this->purgeTags($postId, false));
    return $call ? $this->purge($urls) : $urls;
  }
  
  /**
   * Purges categories associated with a post.
   * @param $postId Id of the post.
   */
  function purgeCategories($postId, $call=true) {
    $urls       = array();
    $categories = get_the_category($postId);
    foreach ($categories as $cat) {
      $urls = array_merge($urls, $this->purgeCategory($cat->cat_ID, false));
    }
    return $call ? $this->purge($urls) : $urls;
  }
  
  /**
   * Purges post comments.
   */
  function purgeComments($commentId, $call=true) {
    $comment  = get_comment($commentId);
    $approved = $comment->comment_approved;
    $urls     = array();

    if ($approved == 1 || $approved == 'trash') {
      $postId = $comment->comment_post_ID;
      #$this->purge('/\\\?comments_popup=' . $postId);
      $urls[] = '/?comments_popup=' . $postId;
      
      // TODO Need Regex Support
      /*
      if (get_site_option($this->wpv_update_commentnavi_optname) == 1) {
        $this->purge('/\\\?comments_popup=' . $postId . '&(.*)');
      }
      */
    }
    return $call ? $this->purge($urls) : $urls;
  }
  
  /**
   * Purges links.
   */
  function purgeLinks() {
    if (is_active_widget(false, false, 'links')) {
        $this->purgeAll();
    }
  }
  
  /**
   * Purges post categories.
   * @param $categoryId Id of the category to purge.
   */
  function purgeCategory($categoryId, $call=true) {
    $urls = array();
    if (is_active_widget(false, false, 'categories')) {
      $this->purgeAll();
    }
    else {
      $urls[] = get_category_link($categoryId);
    }
    return $call ? $this->purge($urls) : $urls;
  }

  /**
   * Purges link categories.
   * @param $categoryId Id of the category to purge.
   */
  function purgeLinkCategory($categoryId) {
    if (is_active_widget(false,false,'links')){
      $this->purgeAll();
    }
  }

  /**
   * Purges a tag category.
   * @param $categoryId Id of the category to purge.
   */
  function purgeTagCategory($categoryId, $call=true) {
    $urls = array( get_tag_link($categoryId) );
    return $call ? $this->purge($urls) : $urls;
  }
  
  /**
   * Purges archives pages.
   * @param $postId Id of the post that triggered the purge.
   */
  function purgeArchives($postId, $call=true) {
    $urls = array(
      get_day_link(get_post_time('Y',false,$postId), get_post_time('m',true,$postId),get_post_time('d',true,$postId)),
      get_month_link(get_post_time('Y',false,$postId), get_post_time('m',true,$postId)),
      get_year_link(get_post_time('Y',false,$postId)),
    );

    return $call ? $this->purge($urls) : $urls;
  }  

  /**
   * Purges tags associated with a post.
   * @param $postId Id of the post being purged.
   */
  function purgeTags($postId, $call=true) {
    $urls = array();
    $tags = wp_get_post_tags($postId);
    foreach ($tags as $tag) {
      $urls = array_merge($urls, $this->purgeTagCategory($tag->term_id, false));
    }
    return $call ? $this->purge($urls) : $urls;
  }

  /**
   * Handles post status purges.
   */
  function purgePostStatus($new_status, $old_status, $post, $call=true) {
    $urls = array();
    $urls = array_merge($urls, $this->purgePost($post->ID, false));
    $urls = array_merge($urls, $this->purgePostDependencies($post->ID, false));
    return $call ? $this->purge($urls) : $urls;
  }
}

// "While mona lisas and mad hatters, sons of bankers, sons of lawyers turn around and say good morning to the night..." -- Elton John

?>