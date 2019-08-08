<?php namespace Wandypurnomo\Modularity\Popo;

use Eventy;

class Menu
{
    public $id;
    public $name;
    public $icon;
    public $visible;
    public $active;
    public $url;

    public function __construct(string $id = null, string $name = null, string $icon = null, bool $visible = false, bool $active = false, string $url = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->icon = $icon;
        $this->visible = $visible;
        $this->active = $active;
        $this->url = $url;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "icon" => $this->icon,
            "visible" => $this->visible,
            "active" => $this->active,
            "url" => $this->url,
        ];
    }

    public function addMenu(): Menu
    {
        Eventy::addFilter("admin-menu", function ($old) {
            if ($old == "") {
                $old = [];
            }

            $old[] = $this->toArray();
            return $old;
        }, 20, 1);

        return $this;
    }

    public function subMenuOf(string $parentId): Menu
    {
        Eventy::addFilter("admin-menu", function ($old) use ($parentId) {
            $all = $old;

            foreach ($all as $k => $v) {
                if ($all[$k]["id"] == $parentId) {
                    $all[$k]["children"][] = $this->toArray();
                }

                if ($this->toArray()["active"]) {
                    $all[$k]["active"] = true;
                }
            }

            return $all;
        }, 20, 1);

        return $this;
    }

    public static function setActive($id)
    {
        $allMenuReference = Eventy::filter("admin-menu");
        $container = [];
        foreach ($allMenuReference as $k => $menu) {
            $container[$k] = $menu;

            if ($container[$k]["id"] == $id) {
                $container[$k]["active"] = true;
            }

            if (isset($container[$k]["children"])) {
                foreach ($container[$k]["children"] as $k2 => $children) {
                    if ($id == $container[$k]["children"][$k2]["id"]) {
                        $container[$k]["active"] = true;
                        $container[$k]["children"][$k2]["active"] = true;
                    }
                }
            }
        }

        Eventy::addFilter("admin-menu", function ($old) use ($container) {
            return $container;
        });
    }

    public static function setVisible(array $ids)
    {
        $allMenuReference = Eventy::filter("admin-menu");
        $container = [];
        foreach ($allMenuReference as $k => $menu) {
            $container[$k] = $menu;

            if (in_array($container[$k]["id"], $ids)) {
                $container[$k]["visible"] = true;
            }

            if (isset($container[$k]["children"])) {
                foreach ($container[$k]["children"] as $k2 => $children) {
                    if (in_array($container[$k]["children"][$k2]["id"], $ids)) {
                        $container[$k]["visible"] = true;
                        $container[$k]["children"][$k2]["visible"] = true;
                    }
                }
            }
        }

        Eventy::addFilter("admin-menu", function ($old) use ($container) {
            return $container;
        });
    }

    public static function setInvisible(array $ids)
    {
        $allMenuReference = Eventy::filter("admin-menu");
        $container = [];
        foreach ($allMenuReference as $k => $menu) {
            $container[$k] = $menu;

            if (in_array($container[$k]["id"], $ids)) {
                $container[$k]["visible"] = false;
            }

            if (isset($container[$k]["children"])) {
                if ($container[$k]["visible"]) {
                    foreach ($container[$k]["children"] as $k2 => $children) {
                        if (in_array($container[$k]["children"][$k2]["id"], $ids)) {
                            $container[$k]["visible"] = false;
                            $container[$k]["children"][$k2]["visible"] = false;
                        }
                    }
                } else {
                    foreach ($container[$k]["children"] as $k2 => $children) {
                        $container[$k]["visible"] = false;
                        $container[$k]["children"][$k2]["visible"] = false;
                    }
                }

            }
        }

        Eventy::addFilter("admin-menu", function ($old) use ($container) {
            return $container;
        });
    }
}