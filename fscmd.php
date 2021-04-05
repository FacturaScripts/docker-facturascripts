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
    die("Please use command line: php fscmd.php");
}

class fscmd
{
    const TRANSLATIONS = 'ca_ES,de_DE,en_EN,es_CL,es_CO,es_CR,es_DO,es_EC,es_ES,es_GT,es_MX,es_PE,es_UY,eu_ES,fr_FR,gl_ES,it_IT,pt_PT,va_ES';
    const VERSION = 0.1;

    public function __construct($argv)
    {
        if(count($argv) < 2) {
            $this->help();
        } elseif($argv[1] === 'create:plugin') {
            $this->createPlugin();
        } elseif($argv[1] === 'create:model') {
            $this->createModel();
        } elseif($argv[1] === 'create:model-extension') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'create:controller') {
            $this->createController();
        } elseif($argv[1] === 'create:controller-extension') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'update:translations') {
            $this->updateTranslations();
        } elseif($argv[1] === 'zip:core') {
            die("Not implemented yet\n");
        } elseif($argv[1] === 'zip:plugin') {
            die("Not implemented yet\n");
        } else {
            $this->help();
        }
    }

    private function createController()
    {
        $option = (int) $this->prompt('1=Controller, 2=ListController, 3=EditController');
        if($option === 2) {
            die("Not implemented yet\n");
        } elseif($option === 3) {
            die("Not implemented yet\n");
        } elseif($option < 1 || $option > 3) {
            die("Invalid\n");
        }

        $name = $this->prompt('Controller name');
        $filename = $this->isCoreFolder() ? 'Core/Controller/'.$name.'.php' : 'Controller/'.$name.'.php';
        if(file_exists($filename)) {
            die("The controller already exists\n");
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Controller;

class '.$name.' extends \\FacturaScripts\\Core\\Base\\Controller {
    
    public function getPageData() {
        $pageData = parent::getPageData();
        $pageData["title"] = "'.$name.'";
        $pageData["menu"] = "admin";
        $pageData["icon"] = "fas fa-page";
        return $pageData;
    }
    
    public function privateCore(&$response, $user, $permissions) {
        parent::privateCore($response, $user, $permissions);
        /// tu código aquí
    }
}');
        $viewFilename = $this->isCoreFolder() ? 'Core/View/'.$name.'.html.twig' : 'View/'.$name.'.html.twig';
        if(file_exists($viewFilename)) {
            return;
        }

        echo '* '.$viewFilename."\n";
        file_put_contents($viewFilename, '{% extends "Master/MenuTemplate.html.twig" %}

{% block body %}
    
{% endblock %}

{% block css %}
    {{ parent() }}
{% endblock %}

{% block javascripts %}
    {{ parent() }}
{% endblock %}');
    }

    private function createCron($folder)
    {
        echo '* '.$folder."/Cron.php\n";
        file_put_contents($folder.'/Cron.php', "<?php
namespace FacturaScripts\\Plugins\\".$folder.';

class Cron extends \\FacturaScripts\\Core\\Base\\CronClass {

    public function run() {
        /*
        if ($this->isTimeForJob("my-job-name", "6 hours")) {
            /// su código aquí
            $this->jobDone("my-job-name");
        }
        */
    }
}');
    }

    private function createGitIgnore($folder)
    {
        echo '* '.$folder."/.gitignore\n";
        file_put_contents($folder.'/.gitignore', "/.idea/\n/nbproject/\n/node_modules/\n"
            ."/vendor/\n.DS_Store\n.htaccess\n*.cache\n*.lock\n.vscode\n*.code-workspace");
    }

    private function createIni($folder)
    {
        echo '* '.$folder."/facturascripts.ini\n";
        file_put_contents($folder.'/facturascripts.ini', "description = '".$folder."'
min_version = 2021
name = ".$folder."
version = 0.1");
    }

    private function createInit($folder)
    {
        echo '* '.$folder."/Init.php\n";
        file_put_contents($folder.'/Init.php', "<?php
namespace FacturaScripts\\Plugins\\".$folder.";

class Init extends \\FacturaScripts\\Core\\Base\\InitClass {

    public function init() {
        /// se ejecutar cada vez que carga FacturaScripts (si este plugin está activado).
    }

    public function update() {
        /// se ejecutar cada vez que se instala o actualiza el plugin
    }
}");
    }

    private function createModel()
    {
        $name = $this->prompt('Model name (singular)');
        $tableName = $this->prompt('Table name (plural)');
        if(empty($name) || empty($tableName)) {
            die("Empty\n");
        } elseif(false === $this->isCoreFolder() && false === $this->isPluginFolder()) {
            die("This folder is not the root of the Plugin or Core\n");
        }

        $filename = $this->isCoreFolder() ? 'Core/Model/'.$name.'.php' : 'Model/'.$name.'.php';
        if(file_exists($filename)) {
            die("The model already exists\n");
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Model;

class '.$name.' extends \\FacturaScripts\\Core\\Model\\Base\\ModelClass {
    use \\FacturaScripts\\Core\\Model\\Base\\ModelTrait;

    public $id;

    public static function primaryColumn() {
        return "id";
    }

    public static function tableName() {
        return "'.$tableName.'";
    }
}');
        $tableFilename = $this->isCoreFolder() ? 'Core/Table/'.$tableName.'.xml' : 'Table/'.$tableName.'.xml';
        if(file_exists($tableFilename)) {
            return;
        }

        echo '* '.$tableFilename."\n";
        file_put_contents($tableFilename, '<?xml version="1.0" encoding="UTF-8"?>
<table>
    <column>
        <name>id</name>
        <type>serial</type>
    </column>
    <constraint>
        <name>'.$tableName.'_pkey</name>
        <type>PRIMARY KEY (id)</type>
    </constraint>
</table>');
    }

    private function createPlugin()
    {
        $name = $this->prompt('Plugin name');
        if(empty($name)) {
            die("Empty name\n");
        } elseif(file_exists('.git') || file_exists('.gitignore') || file_exists('facturascripts.ini')) {
            die("Can't create a plugin here\n");
        } elseif(file_exists($name)) {
            die("Plugin ".$name." already exists\n");
        }
        
        mkdir($name);
        $this->createIni($name);
        $this->createGitIgnore($name);
        $this->createCron($name);
        $this->createInit($name);
        
        $folders = [
            'Assets/Images','Controller','Data/Lang/ES','Extension/Controller','Extension/Model',
            'Extension/Table','Extension/XMLView','Model','Table','Translation','View','XMLView'
        ];
        foreach($folders as $folder) {
            mkdir($name.DIRECTORY_SEPARATOR.$folder, 0777, true);
        }

        foreach(explode(',', self::TRANSLATIONS) as $filename) {
            file_put_contents($name.'/Translation/'.$filename.'.json', '{
    "'.$name.'": "'.$name.'"
}');
        }
    }

    private function getNamespace()
    {
        if($this->isCoreFolder()) {
            return 'Core';
        }

        $ini = parse_ini_file('facturascripts.ini');
        return 'Plugins\\'.$ini['name'];
    }

    private function help()
    {
        echo 'FacturaScripts command line utility v' . self::VERSION . "

create:
$ fscmd create:plugin
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

    private function isCoreFolder()
    {
        return file_exists('Core/Translation') && false === file_exists('facturascripts.ini');
    }

    private function isPluginFolder()
    {
        return file_exists('facturascripts.ini');
    }

    private function prompt($label)
    {
        echo $label . ': ';
        return trim(fgets(STDIN));
    }

    private function updateTranslations()
    {
        $folder = '';
        $project = '';
        if($this->isPluginFolder()) {
            $folder = 'Translation/';
            $ini = parse_ini_file('facturascripts.ini');
            $project = $ini['name'] ?? '';
        } elseif($this->isCoreFolder()) {
            $folder = 'Core/Translation/';
            $project = 'CORE-2018';
        } else {
            die("This folder is not the root of the Plugin or Core\n");
        }

        if(empty($project)) {
            die("Unknown project\n");
        }

        /// download json from facturascripts.com
        foreach (explode(',', self::TRANSLATIONS) as $filename) {
            echo "D ".$folder.$filename.".json";
            $url = "https://facturascripts.com/EditLanguage?action=json&project=".$project."&code=".$filename;
            $json = file_get_contents($url);
            if(!empty($json) && strlen($json) > 10) {
                file_put_contents($folder.$filename.'.json', $json);
                echo "\n";
                continue;
            }

            echo " - empty\n";
        }
    }
}

new fscmd($argv);