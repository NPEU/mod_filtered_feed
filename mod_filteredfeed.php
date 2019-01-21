<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_filteredfeed
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

// Include the filteredfeed functions only once
JLoader::register('ModFilteredfeedHelper', __DIR__ . '/helper.php');

$feed = modFilteredFeedHelper::getFeed($params);
if ($feed) {
    require(JModuleHelper::getLayoutPath('mod_filteredfeed'));
}
