<?php
/**
 * Created by PhpStorm.
 * User: garin
 * Date: 17.03.2017
 * Time: 23:58
 */

namespace Sitemap;

class Sitemap
{
    use \Cetera\DbConnection;
    // Здесь ссылки, которые не должны попасть в sitemap.xml
    protected $nofollow = array('/cms/');
    protected $info = Array();

    public static function getTreeList($dirs = "", $nodeId, $root = false)
    {
        $p = new self();
        $p->setDirs($dirs);

        $mainNode = $p->process_child(\Cetera\Catalog::getById($nodeId));
        $mainNode["expanded"] = false;
        $mainNode["elements"] = "";
        $mainNode["children"] = $p->getFullTree($nodeId);
        if ($root) {
            return array(
                'text' => 'root',
                'id' => 'root',
                'iconCls' => 'tree-folder-visible',
                'qtip' => '',
                'mtype' => 0,
                'disabled' => false,
                "children" => $mainNode,
                'expanded' => false
            );
        } else {
            return $mainNode;
        }
    }

    public function setDirs($dirs)
    {
        $this->info["dirs"] = explode(",", $dirs);
    }

    protected function process_child(\Cetera\Catalog $child, $exclude = false, $nocatselect = false)
    {
        if (!self::prepareLink($child->getFullUrl()))
            return false;

        if ($child->id == $exclude) return false;

        if ($child->hidden)
            return false;

        $cls = 'tree-folder-visible';
        if ($child instanceof \Cetera\Server) $cls = 'tree-server';
        if ($child->isLink()) $cls = 'tree-folder-link';

        return array(
            'text' => $child->name,
            'alias' => $child->alias,
            'fullUrl' => $child->getFullUrl(),
            'id' => $child->id,
            'qtip' => $child->describ,
            'link' => $child->isLink(),
            'mtype' => $child->materialsType,
            'hidden' => $child->hidden,
            'date' => $child->dat,
            'iconCls' => $cls,
            'disabled' => !$nocatselect ? false : true,
        );
    }

    protected function prepareLink($link)
    {

        //Убираем якори у ссылок
        $link = preg_replace("/#.*/X", "", $link);

        //Узнаём информацию о ссылке
        $urlinfo = @parse_url($link);
        if (!isset($urlinfo['path'])) {
            $urlinfo['path'] = null;
        }

        //Если ссылка в нашем запрещающем списке, то также прекращаем с ней работать
        $nofoll = 0;
        if ($this->nofollow != null) {
            foreach ($this->nofollow as $of) {
                if (strstr($link, $of)) {
                    $nofoll = 1;
                    break;
                }
            }
        }

        if ($nofoll == 1) {
            return false;
        }

        return true;
    }

    public function getFullTree($nodeId)
    {
        return !empty($nodeId) ? self::getTree($nodeId, -1) : null;
    }

    function getTree($id, $level = 0)
    {
        $exclude = -1;
        $nocatselect = true;
        $nodes = array();
        $level++;

        $t = \Cetera\Application::getInstance()->getTranslator();
        $c = \Cetera\Catalog::getById($id);
        if ($c) {
            foreach ($c->children as $child) {
                if (!empty($child->hidden))
                    continue;

                $a = self::process_child($child, $exclude, $nocatselect);
                if (is_array($a)) {
                    $a["children"] = self::getTree($a["id"], $level);
                    $a["children"] = self::array_delete($a["children"], Array('', 0, false, null));

                    if (is_array($this->info["dirs"]) && count($this->info["dirs"])) {
                        $a['checked'] = in_array("s-" . $a["id"], $this->info["dirs"]) ? true : false;
                        $hide = in_array("e-" . $a["id"], $this->info["dirs"]) ? false : true;
                        $a['elements'] = "<a href='#' data-id='" . $a["id"] . "' data-status='" . ($hide ? 'N' : 'Y') . "' class='js-element-hide'>" . $t->_(($hide ? 'Нет' : 'Да')) . "</a>";
                    } else {
                        $a['checked'] = true;
                        $hide = false;
                        $a['elements'] = "<a href='#' data-id='" . $a["id"] . "' data-status='" . ($hide ? 'N' : 'Y') . "' class='js-element-hide'>" . $t->_(($hide ? 'Нет' : 'Да')) . "</a>";
                    }
                    $nodes[] = $a;
                }
            }
            if ($c->isLink()) {
                foreach ($c->prototype->children as $child) {
                    $a = self::process_child($child, $exclude, $nocatselect);
                    if (is_array($a)) {
                        $a["children"] = self::getTree($a["id"], $level);
                        $a["children"] = self::array_delete($a["children"], Array('', 0, false, null));

                        if (is_array($this->info["dirs"]) && count($this->info["dirs"])) {
                            $a['checked'] = in_array("s-" . $a["id"], $this->info["dirs"]) ? true : false;
                            $hide = in_array("e-" . $a["id"], $this->info["dirs"]) ? false : true;
                            $a["parseElements"] = $hide ? false : true;
                            $a['elements'] = "<a href='#' data-id='" . $a["id"] . "' data-status='" . $hide . "' class='js-element-hide'>" . $t->_(($hide ? 'Нет' : 'Да')) . "</a>";
                        } else {
                            $a['checked'] = true;
                            $hide = false;
                            $a["parseElements"] = $hide ? false : true;
                            $a['elements'] = "<a href='#' data-id='" . $a["id"] . "' data-status='" . $hide . "' class='js-element-hide'>" . $t->_(($hide ? 'Нет' : 'Да')) . "</a>";
                        }

                        $nodes[] = $a;
                    }
                }
            }
        }
        $nodes = self::array_delete($nodes, Array('', 0, false, null));

        return $nodes;
    }

    /**
     * Удалить пустые элементы из массива
     *
     * @param array $array
     * @param array $symbols удаляемые значения
     *
     * @return array
     */
    public static function array_delete(array $array = Array(), array $symbols = array(''))
    {
        return array_diff($array, $symbols);
    }

    public function getSitemap()
    {
        $dirs = Array();
        $materialDirs = Array();
        foreach ($this->info['dirs'] as $dir) {
            if (preg_match("#s#is", $dir)) {
                $dir = preg_replace("#s-#is", "", $dir);
                $dirs[] = $dir;
            } elseif (preg_match("#e#is", $dir)) {
                $dir = preg_replace("#e-#is", "", $dir);
                $materialDirs[] = $dir;
            }
        }
        $filter = Array(
            "hidden" => 0
        );
        if (count($dirs)) {
            $filter["id"] = $dirs;
        }
        $list = self::getList(Array("id" => "ASC"), $filter, Array("LIMIT" => 5000));
        $nodeArray = Array();
        $tables = Array();
        while ($el = $list->fetch()) {
            $c = \Cetera\Catalog::getById($el["id"]);
            if ($c && !$c->isHidden()) {
                try {
                    if (!empty($c->fields["materialsType"]) && ($table = \Cetera\ObjectDefinition::getById($c->fields["materialsType"])->table) !== null) {
                        $tables[$table] = $table;
                    }
                } catch (\Exception $e) {
                }

                $item = Array(
                    "id" => $c->id,
                    "url" => preg_replace("#([^:])//#is", "$1/", $c->getFullUrl()),
                    "name" => $c->name
                );

                try {
                    $item["parent"] = $c->getParent()->id;
                } catch (\Exception $e) {
                }

                if (!empty($item["parent"]) && !empty($item["url"]) && in_array($item["parent"], $dirs) && in_array($item["id"], $dirs))
                    $nodeArray[] = $item;
            }
        }
        foreach ($tables as $table) {
            $filter = Array(
                "table" => $table,
                "!alias" => "'index'",
            );
            if (count($materialDirs)) {
                $filter["idcat"] = $materialDirs;
            }
            $materials = self::getList(Array("id" => "ASC"), $filter, Array("LIMIT" => 5000));
            while ($el = $materials->fetch()) {
                try {
                    $c = \Cetera\Material::getById($el["id"], $el["type"], $table);
                    if ($c && empty($c->hidden) && $c->alias !== "index" && $c->published) {
                        $item = Array(
                            "id" => $c->id,
                            "url" => preg_replace("#([^:])//#is", "$1/", $c->getFullUrl()),
                            "name" => $c->name,
                            "material" => "Y",
                        );

                        try {
                            $item["parent"] = $c->getCatalog()->id;
                        } catch (\Exception $e) {
                        }

                        if (!empty($item["parent"]) && !empty($item["url"]) && in_array($item["parent"], $materialDirs)/* && in_array($item["id"], $materialDirs)*/)
                            $nodeArray[] = $item;
                    }
                } catch (\Exception $e) {
                }
            }
        }

        $parentSort = Array();
        foreach ($nodeArray as $key => $val) {
            $parentSort[$key] = $val["parent"];
        }

        array_multisort($parentSort, SORT_ASC, $nodeArray);

        $nodes = Array();
        foreach ($nodeArray as $item) {
            $nodes = self::recursiveItems($item, $nodes);
            $nodes = $nodes["items"];
        }

        return $nodes;
    }

    public static function getList($arSort = Array(), $arFilter = Array(), $arLimit = Array(), $arSelect = Array())
    {
        $qb = self::getDbConnection()->createQueryBuilder();
        if (empty($arSelect))
            $arSelect = "*";
        else
            $arSelect = implode(",", $arSelect);

        $qb = $qb
            ->select($arSelect)
            ->from(!empty($arFilter["table"]) ? $arFilter["table"] : \Cetera\Catalog::TABLE);

        $first = true;
        if (count($arFilter)) {
            foreach ($arFilter as $key => $val) {
                if ($key == "table")
                    continue;

                if (strpos($key, "!") !== false) {
                    $key = preg_replace("#^!#is", "", $key) . (is_array($val) ? " NOT IN (" : " <> ");
                } elseif (strpos($key, ">=") !== false) {
                    $key = preg_replace("#^>=#is", "", $key) . " >= ";
                } elseif (strpos($key, "<=") !== false) {
                    $key = preg_replace("#^<=#is", "", $key) . " <= ";
                } elseif (strpos($key, ">") !== false) {
                    $key = preg_replace("#^>#is", "", $key) . " > ";
                } elseif (strpos($key, "<") !== false) {
                    $key = preg_replace("#^<#is", "", $key) . " < ";
                } else {
                    $key = $key . (is_array($val) ? " IN (" : " = ");
                }

                if ($first) {
                    $value = $val;
                    if (is_array($val)) {
                        $value = "";
                        foreach ($val as $v) {
                            $value .= $qb->createNamedParameter($v) . ",";
                        }
                        $value = trim($value, ",") . ")";
                    }
                    $qb = $qb->where($key . $value);
                } else {
                    $value = $val;
                    if (is_array($val)) {
                        $value = "";
                        foreach ($val as $v) {
                            $value .= $qb->createNamedParameter($v) . ",";
                        }
                        $value = trim($value, ",") . ")";
                    }
                    $qb = $qb->andWhere($key . $value);
                }

                $first = false;
            }
        }

        $first = true;
        if (count($arSort)) {
            foreach ($arSort as $key => $val) {
                if ($first)
                    $qb = $qb->orderBy($key, $val);
                else
                    $qb = $qb->addOrderBy($key, $val);

                $first = false;
            }
        }

        if (count($arLimit)) {
            if (intval($arLimit["LIMIT"]) > 0) {
                $qb = $qb->setMaxResults(intval($arLimit["LIMIT"]));
            }

            if (intval($arLimit["TOP"]) > 0) {
                $qb = $qb->setFirstResult(intval($arLimit["TOP"]));
            }

            if (intval($arLimit["PAGE"]) > 0 && intval($arLimit["PAGE_COUNT"]) > 0) {
                $qb = $qb->setFirstResult((intval($arLimit["PAGE"]) - 1) * intval($arLimit["PAGE_COUNT"]));
                $qb = $qb->setMaxResults(intval($arLimit["PAGE_COUNT"]));
            }
        }

        $r = $qb->execute();

        return $r;
    }

    protected function recursiveItems($search, $items = Array(), $level = 0)
    {
        $add = false;
        foreach ($items as $key => $item) {
            if (empty($item["material"])) {
                if ($search["parent"] === $item["id"]) {
                    $items[$key]["children"][] = $search;

                    return Array("items" => $items, "add" => true);
                } elseif (isset($item["children"])) {
                    $data = self::recursiveItems($search, $item["children"], $level + 1);
                    $items[$key]["children"] = $data["items"];
                    if ($data["add"]) {
                        return Array("items" => $items, "add" => true);
                    }
                }
            } else {
                return Array("items" => $items, "add" => false);
            }
        }
        if ($level === 0 && empty($search["material"]))
            $items[] = $search;

        return Array("items" => $items, "add" => $add);
    }
}