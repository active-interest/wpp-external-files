<?php namespace WPP\External_Files;

use WP_CLI;
//use WP_CLI_Command;

class CLI_Command extends \WP_CLI_Command
{
  /**
   * Runs wp_update_post on selected posts to trigger the external file import process
   *
   * ## OPTIONS
   *
   * [--post_id=<post_id>]
   * : Update a specific post(s).  Pass multiple ID's comma-delimited.
   *
   * [--post_type=<post_type>]
   * : Use this post_type (default: any)
   *
   * [--include-syndicated]
   * : Include posts syndicated with FeedWordPress
   *
   * [--limit=<number>]
   * : limit to this number of posts

   * ## EXAMPLES
   *
   *    wp external import --post_id=<post_id> --post_type=photo-gallery --include-syndicated --limit=10
   *
   * @synopsis [--post_id=<post_id>] [--post_type=<post_type>] [--include-syndicated] [--limit=<number>]
   * @when after_wp_load
   * @subcommand import
   */
  public function import($args, $assoc_args) {
    WP_CLI::line('Preparing to import external files ...');
    $options = array(
      'post_type' => 'any',
      'posts_per_page' => 5,
      'status' => 'publish',
      'offset' => 0,
    );

    if(!empty($assoc_args['post_id'])) {
      $options['include'] = $assoc_args['post_id'];
    }

    if(!empty($assoc_args['limit'])) {
      if((int)$assoc_args['limit'] < $options['posts_per_page']) {
        $options['posts_per_page'] = (int)$assoc_args['limit'];
      }
    }

    if(!empty($assoc_args['post_type'])) {
      $options['post_type'] = $assoc_args['post_type'];
    }

    //$exclude_list = array();
    $total = 0;
    do {
      $query = array(
        'meta_query' => array(
        ),
      );

      if(empty($assoc_args['include-syndicated']) || !$assoc_args['include-syndicated'] ) {
        $query['meta_query'][] = array(
          // ignore syndicated content from FeedWordPress
          'key' => 'syndication_source_uri',
          'value' => 'bug #23268',
          'compare' => 'NOT EXISTS',
        );
      }

      $posts = get_posts(array_merge($options, $query));
      if(count($posts) <= 0) break;
      WP_CLI::line('Number of Posts: ' . count($posts));

      foreach($posts as $post) {
        WP_CLI::line('Post ID: ' . $post->ID . ', ' . $post->post_title);
        //do_action('save_post', $post->ID, $post, true);
        $o = wp_update_post($post, true);
        if(is_wp_error($o)) {
          WP_CLI::error($o->get_error_message());
        }
      }
      $options['offset'] += $options['posts_per_page'];
      $total += count($posts);
    } while(!empty($posts) && count($posts) >0 && (!empty($assoc_args['limit']) ? $total < (int)$assoc_args['limit'] : true));
    WP_CLI::line('Total Processed: ' . $total);
  }
}
