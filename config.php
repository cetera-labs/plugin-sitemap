<?php
/*
Скрипт подключается в методе Application::initPlugins() и позволяет плагину встроить себя в интерфейс Back Office, зарегистрировать фильтр вывода и т.д.
*/

$t = $this->getTranslator();
$t->addTranslation(__DIR__.'/lang');

$this->registerWidget(array(
    'name' => 'Sitemap',
    'class' => '\\Sitemap\\WidgetSitemap',
    'describ' => $t->_('Карта сайта'),
    'icon' => '/cms/plugins/sitemap/images/icon.gif',
    'ui' => 'Plugin.sitemap.Widget',
));