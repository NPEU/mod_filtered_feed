<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_filteredfeed
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;


$doc = JFactory::getDocument();

?>
<?php if (count($feed) > 0): ?>
<section>
    <h2<?php if (!$params->get('show_title')): echo ' style="display: none;"'; endif; ?>><?php echo $params->get('title'); ?></h2>

    <?php foreach ($feed as $item): ?>
    <article>
        <?php if (!empty($item['thumb'])): ?>
        <img src="<?php echo $item['thumb']; ?>" style="width: 60px; float: left; margin: 10px 10px 10px 0;" />
        <?php endif; ?>
        <h2 id="<?php echo strtolower(preg_replace('/\W/', '-', $item['title'])); ?>"><a href="<?php echo $item['link']; ?>"><?php echo $item['title']; ?></a></h2>
        <?php if ($params->get('show_description')): ?>
        <?php echo $item['description']; ?>
        <p>
            <a href="<?php echo $item['link']; ?>"><?php echo JText::_('COM_CONTENT_READ_MORE'); ?></a>
        </p>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>
