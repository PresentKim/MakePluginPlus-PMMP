<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\tool\dev;

use blugin\lib\translator\traits\MultilingualConfigTrait;
use blugin\tool\dev\builder\AdvancedBuilder;
use blugin\tool\dev\folderloader\FolderPluginLoader;
use blugin\tool\dev\virion\VirionLoader;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoadOrder;
use pocketmine\Server;

class BluginTools extends PluginBase{
    use MultilingualConfigTrait;

    /** @var BluginTools */
    private static $instance;

    /** @return BluginTools */
    public static function getInstance() : BluginTools{
        return self::$instance;
    }

    public function onLoad(){
        self::$instance = $this;

        AdvancedBuilder::getInstance();
        VirionLoader::getInstance();
        $this->getServer()->getPluginManager()->registerInterface(new FolderPluginLoader($this->getServer()->getLoader()));
    }

    public function onEnable(){
        $this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), [FolderPluginLoader::class]);
        $this->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
        AdvancedBuilder::getInstance()->init();
    }

    public static function clearDirectory(string $dir) : bool{
        foreach(self::readDirectory($dir) as $file){
            $path = "{$dir}/{$file}";
            if(is_dir($path)){
                self::clearDirectory($path);
                rmdir($path);
            }else{
                unlink($path);
            }
        }
        return (count(scandir($dir)) == 2);
    }

    public static function readDirectory(string $dir, bool $recursive = false, array $result = []) : array{
        $dir = self::cleanDirName($dir);
        if(!file_exists($dir))
            mkdir($dir, 0777, true);

        $files = array_diff(scandir($dir), [".", ".."]);
        if(!$recursive)
            return $files;

        foreach($files as $filename){
            $path = $dir . $filename;
            if(is_file($path)){
                $result[] = $path;
            }elseif(is_dir($path)){
                $result = self::readDirectory($path, true, $result);
            }
        }
        return $result;
    }

    public static function cleanDirName(string $path) : string{
        return rtrim(str_replace("\\", "/", $path), "/") . "/";
    }

    public static function getPlugin(string $name) : ?Plugin{
        $plugins = Server::getInstance()->getPluginManager()->getPlugins();
        if(isset($plugins[$name]))
            return $plugins[$name];

        $found = null;
        $length = strlen($name);
        $minDiff = PHP_INT_MAX;
        foreach($plugins as $pluginName => $plugin){
            if(stripos($pluginName, $name) === 0){
                $diff = strlen($pluginName) - $length;
                if($diff < $minDiff){
                    $found = $plugin;
                    if($diff === 0)
                        break;

                    $minDiff = $diff;
                }
            }
        }
        return $found;
    }
}