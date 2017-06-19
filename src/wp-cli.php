<?php
if (!class_exists('Purgely_Command')) :

    /**
     * Define the "fast" WP CLI command.
     */
    class Purgely_Command extends WP_CLI_Command
    {
        /**
         * Purge a URL, post ID(s), or purge-all.
         *
         * ## EXAMPLES
         *
         * Purge all
         *  wp fastly purge all
         *
         * Purge ID-s
         *  wp fastly purge id 56
         *
         * Purge URL
         *  wp fastly purge url http://www.wired.com/category/design/
         *
         * @param  array $args
         * @return void
         */
        public function purge($args)
        {

            // Collect arguments
            $type = !empty($args[0]) ? $args[0] : false;
            $thing = !empty($args[1]) ? $args[1] : false;

            if ($type === 'id') {
                $type = Purgely_Purge::KEY_COLLECTION;
            }

            // Check supported purge types, add ids to supported types
            if (!in_array($type, Purgely_Purge::get_purge_types())) {
                WP_CLI::error(__('Missing or invalid purge type.', 'purgely'));
                return;
            }

            if ($type === Purgely_Purge::ALL && !Purgely_Settings::get_setting('allow_purge_all')) {
                WP_CLI::error(__('Allow Full Cache Purges first.', 'purgely'));
                return;
            }

            // Check data to be purged
            if (!$thing && $type !== Purgely_Purge::ALL) {
                WP_CLI::error(__('Missing thing that needs to be purged.', 'purgely'));
                return;
            } elseif ($thing && $type === Purgely_Purge::URL) {
                if (!is_url($thing)) {
                    WP_CLI::error(__('Invalid URL.', 'purgely'));
                    return;
                }
            }

            // Find related IDs for inputed ID
            if ($type === Purgely_Purge::KEY_COLLECTION) {
                $related_collection_object = new Purgely_Related_Surrogate_Keys($thing);
                $thing = $related_collection_object->locate_all();
            }

            // Set wp original certificate instead of wp-cli certificate
            Requests::set_certificate_path(ABSPATH . WPINC . '/certificates/ca-bundle.crt');

            // Issue purge request
            $purgely = new Purgely_Purge();
            $result = $purgely->purge($type, $thing);

            if ($type === Purgely_Purge::ALL) {
                $message = 'all';
            } elseif ($type === Purgely_Purge::KEY_COLLECTION) {
                $message = 'ID:' . $args[1];
            } elseif ($type === Purgely_Purge::URL) {
                $message = esc_url($thing);
            } else {
                $message = $thing;
            }

            if ($result) {
                WP_CLI::success(sprintf(__('Successfully purged - %s', 'purgely'), $message));
            } else {
                WP_CLI::error(sprintf(__('Purge failed - %s - (enable and check logging for more information)'), $message));
            }
        }
    }
endif;

WP_CLI::add_command('fastly', 'Purgely_Command');
