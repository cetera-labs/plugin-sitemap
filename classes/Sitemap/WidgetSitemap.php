<?php
/**
 * Created by PhpStorm.
 * User: garin
 * Date: 17.03.2017
 * Time: 23:58
 */

namespace Sitemap;

class WidgetSitemap extends \Cetera\Widget\Templateable
{
    protected $_params = array(
        'template' => 'default.twig',
        'sections' => ''
    );

    public function getSitemap()
    {
        $dirs = $this->getParam("sections");
        $p = new \Sitemap\Sitemap();
        $p->setDirs($dirs);
        $items = $p->getSitemap();

        return $items;
    }

    protected function init()
    {
        parent::init();
    }
}