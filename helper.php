<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_filteredfeed
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

#use Joomla\String\StringHelper;

function url_exists($url) {
    if (!$fp = curl_init($url)) return false;
    return true;
}

/**
 * Helper for mod_filteredfeed
 */
class ModFilteredfeedHelper
{
    /**
     * Filters an RSS feed to include or exclude user-defined terms.
     *
     * @param   \Joomla\Registry\Registry  &$params  module parameters object
     *
     * @return  mixed
     */
    static function getFeed(&$params)
    {

        $feed_url = $params->get('url');

        $feed_url_encoded    = md5($feed_url);
        $feed_cache_filename = $feed_url_encoded . '.xml';
        $feed_cache_dir      = $_SERVER['DOCUMENT_ROOT'] . '/datastore/feeds/';
        $feed_cache_filepath = $feed_cache_dir . $feed_cache_filename;

        $feed_cache_expiry   = 60 * 60 * 24;

        $processed_cache_filename = $feed_url_encoded . '-processed.json';
        $processed_cache_filepath = $feed_cache_dir . $processed_cache_filename;

        // If the processed cache file doesn't exist or has expired, fetch it the feed.
        $reprocess_feed = false;
        // If the cache file doesn't exist or has expired, fetch it the feed.
        if (!file_exists($processed_cache_filepath)) {
             $reprocess_feed = true;
        } else {
            $feed_cache_expired = ((filemtime($processed_cache_filepath) + $feed_cache_expiry) < time());
            if ($feed_cache_expired) {
                $reprocess_feed = true;
            }
        }

        if ($reprocess_feed == false) {
            $json = file_get_contents($processed_cache_filepath);
            return json_decode($json, true);
        }

        $refetch_feed = false;
        // If the cache file doesn't exist or has expired, fetch it the feed.
        if (!file_exists($feed_cache_filepath)) {
             $refetch_feed = true;
        } else {
            $feed_cache_expired = ((filemtime($feed_cache_filepath) + $feed_cache_expiry) < time());
            if ($feed_cache_expired) {
                $refetch_feed = true;
            }
        }

        if ($refetch_feed == true) {
            if (!url_exists($feed_url)) {
                // URL doesn't exist an there's no cache.
                // @TODO - really need to log an error of some kind here.
                return false;
            }
            $feed_content = file_get_contents($feed_url);
            file_put_contents($feed_cache_filepath, $feed_content);
        } else {
            $feed_content = file_get_contents($feed_cache_filepath);
        }

        $include_terms = explode("\n", preg_replace('#\n{2,}#', "\n", str_replace("\r", "\n", $params->get('include'))));
        $exclude_terms = explode("\n", preg_replace('#\n{2,}#', "\n", str_replace("\r", "\n", $params->get('exclude'))));
        $limit         = $params->get('limit');


        $items = array();
        $stubs = array();

        // Fix dodgy feeds:
        #$feed_content = self::fix_feed($feed_content);
        $tidy = new Tidy();
        $feed_content = $tidy->repairString($feed_content, array(
            'output-xml' => true,
            'input-xml' => true
        ), 'utf8');

        $feed = simplexml_load_string($feed_content);

        foreach ($feed->channel->item as $i)
        {
            if ($limit && count($items) == $limit) {
                break;
            }

            if (
               self::has_terms($i->title, $include_terms, $exclude_terms)
            || self::has_terms($i->description, $include_terms, $exclude_terms)
            ) {
                // Attempt to identify ans skip duplicates:
                $stub = substr(trim(str_replace('&nbsp;', ' ', strip_tags($i->description))), 0, 80);
                if (in_array($stub, $stubs)) {
                    continue;
                }
                $stubs[] = $stub;

                $data = array(
                    "title"       => trim($i->title),
                    "description" => trim($i->description),
                    "link"        => trim($i->link)
                );

                // Look for the first image in the description and add it as a thumbnail if found:
                if (preg_match('#<img.*?src="(.*?)".*?>#', $i->description, $matches)) {
                    $data['thumb'] = $matches[1];
                } else {
                    // If it can't be found, scrape it from the original URL:
                    // @TODO: move this to the config so it's agnostic:
                    $selector = 'id="imgBanner"';

                    $page = file_get_contents($data['link']);
                    if (preg_match('#<img\s[^>]*' . $selector . '[^>]*>#', $page, $matches)) {
                        $tag = $matches[0];
                        if (preg_match('#<img.*?src="(.*?)".*?>#', $tag, $src_matches)) {
                            $data['thumb'] = $src_matches[1];
                        }
                    }

                }
                $items[] = $data;
            }
        }

        // Store the processed items:
        $json = json_encode($items);
        file_put_contents($processed_cache_filepath, $json);

        return $items;
    }

    /**
     * Performs oprations to tidy / fix a feed's content.
     *
     * @param   string  $feed_content  Content string
     *
     * @return  string
     */
    static function fix_feed($feed_content)
    {
        // Remove script tags:
        $feed_content = preg_replace('#<script[^>]*>(.*</script>)?#', '', $feed_content);
        // Remove incomplete tags:
        $feed_content = preg_replace('#<[a-z]+.*\]\]#', ']]', $feed_content);

        return $feed_content;
    }

    /**
     * Checks content for terms.
     *
     * @param   string  $content  Content string
     * @param   array   $include  Terms that must be included
     * @param   array   $exclude  Terms that must NOT be included
     *
     * @return  bool
     */
    static function has_terms($content, $include, $exclude)
    {
        $found = false;
        // Check for include list:
        foreach ($include as $term) {
            if (preg_match('/\b' . $term . '\b/', $content) == 1) {
                $found = true;
            }
        }
        // Check for exclude list:
        foreach ($exclude as $term) {
            if (stristr($content, $term) !== false) {
                $found = false;
            }
        }

        return $found;
    }
}
