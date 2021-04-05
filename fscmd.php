<?php
/**
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if (php_sapi_name() !== 'cli') {
    die();
}

class fscmd
{
    const VERSION = 0.1;

    public function __construct($argv)
    {
        if(count($argv) < 2) {
            $this->help();
        } elseif($argv[1] === 'create:plugin') {
            $this->createPlugin();
        } elseif($argv[1] === 'create:table') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'create:model') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'create:model-extension') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'create:controller') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'create:controller-extension') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'update:translations') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'zip:core') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'zip:plugin') {
            die("Not implemented yet\n");
        }
    }

    private function createGitIgnore($folder)
    {
        file_put_contents($folder.'/.gitignore', "/.idea/\n/nbproject/\n/node_modules/\n"
            ."/vendor/\n.DS_Store\n.htaccess\n*.cache\n*.lock\n.vscode\n*.code-workspace");
    }

    private function createIni($folder)
    {
        file_put_contents($folder.'/facturascripts.ini', "description = '".$folder."'
min_version = 2021
name = ".$folder."
version = 0.1");
    }

    private function createInit($folder)
    {
        file_put_contents($folder.'/Init.php', "<?php
namespace FacturaScripts\\Plugins\\".$folder.";

class Init extends \\FacturaScripts\\Core\\Base\\InitClass {
    public function init() {
    }

    public function update() {
    }
}");
    }

    private function createPlugin()
    {
        $name = $this->prompt('Plugin name');
        if(empty($name)) {
            return;
        } elseif(file_exists('.git') || file_exists('.gitignore')) {
            die("Can't create a plugin here");
        } elseif(file_exists($name)) {
            die("Plugin ".$name." exists");
        }
        
        mkdir($name);
        $this->createIni($name);
        $this->createInit($name);
        $this->createGitIgnore($name);
        
        $folders = [
            'Assets/Images','Controller','Data/Lang/ES','Extension/Controller','Extension/Model',
            'Extension/Table','Extension/XMLView','Lib','Model','Table','Translation','View','XMLView'
        ];
        foreach($folders as $folder) {
            mkdir($name.DIRECTORY_SEPARATOR.$folder, 0777, true);
        }

        file_put_contents($name.'/Translation/es_ES.json', '{}');
        file_put_contents($name.'/Translation/en_EN.json', '{}');

        echo 'Created plugin '.$name."\n";
    }

    private function help()
    {
        echo 'FacturaScripts command line utility v' . self::VERSION . "

create:
$ fscmd create:plugin
$ fscmd create:table
$ fscmd create:model
$ fscmd create:model-extension
$ fscmd create:controller
$ fscmd create:controller-extension

update:
$ fscmd update:translations

zip:
$ fscmd zip:core
$ fscmd zip:plugin\n";
    }

    private function prompt($label)
    {
        echo $label . ': ';
        return trim(fgets(STDIN));
    }
}

new fscmd($argv);