<?php

include_once('common_bo.php');
$nodes = \Sitemap\Sitemap::getTreeList(0, $_REQUEST["dirs"], false);

echo json_encode($nodes);

?>
