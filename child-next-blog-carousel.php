<?php

    class Child_Next_Blog_Slider extends ET_Builder_Module_Type_PostBased {
        
        public $slug       = 'child_next_blog_slider';
        public $vb_support = 'on';

        protected $module_credits = array(
            'module_uri' => 'https://shorifullislamratan.me/projects/mro-events',
            'author'     => 'Ratan Mia',
            'author_uri' => 'https://shorifullislamratan.me',
        );

        public function init() {
            $this->name = esc_html__( 'Child Next Blog Slider', 'your-child-theme-domain' );
        }

        // Override or add additional methods here
        // Example: Override the get_blog_posts method
        public static function get_blog_posts($args = array(), $conditional_tags = array(), $current_page = array()) {

            $defaults = array(
                'posts_number'                  => '',
                'offset'                        => '',
                'include_categories'            => '',
                'post_type'                     => '',
                'order_by'                      => '',
                'order'                         => '',
                'meta_date'                     => '',
                'show_thumbnail'                => '',
                'image_clickable'               => '',
                'show_content'                  => '',
                'show_author'                   => '',
                'date_position'                 => '',
                'conditinal_content_position'   => '',
                'show_date'                     => '',
                'show_categories'               => '',
                'show_excerpt'                  => '',
                'excerpt_length'                => '',
                'header_level'                  => 'h2',
                'show_more'                     => '',
                'button_use_icon'               => '',
                'button_icon'                   => '',
            );
    
            $args = wp_parse_args($args, $defaults);
    
    
            $processed_header_level = et_pb_process_header_level($args['header_level'], 'h2');
            $processed_header_level = esc_html($processed_header_level);
    
            $query_args = array(
                'posts_per_page' => intval($args['posts_number']), //phpcs:ignore
                'post_status'    => 'publish',
                'post_type'      => $args['post_type'],
                'orderby'        => $args['order_by'],
                'order'          => $args['order'],
                'offset'         => $args['offset']
            );
    
            $post_id = isset($current_page['id']) ? (int) $current_page['id'] : 0;
            $query_args['cat'] = implode(',', self::filter_include_categories($args['include_categories'], $post_id));
            
    
            // Get query
            $query = new WP_Query($query_args);
    
            ob_start();
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    include dirname(__FILE__) . '/template-parts/blog-post.php';
                }
            }
            wp_reset_postdata();
            if (!$posts = ob_get_clean()) {
                $posts = self::get_no_results_template(et_core_esc_previously($processed_header_level));
            }
    
            return $posts;
        }
    }

    new Child_Next_Blog_Slider;

