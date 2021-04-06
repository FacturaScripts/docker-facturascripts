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
    die("Usar: php fsmaker.php");
}

class fsmaker
{
    const TRANSLATIONS = 'ca_ES,de_DE,en_EN,es_CL,es_CO,es_CR,es_DO,es_EC,es_ES,es_GT,es_MX,es_PE,es_UY,eu_ES,fr_FR,gl_ES,it_IT,pt_PT,va_ES';
    const VERSION = 0.2;

    public function __construct($argv)
    {
        if(count($argv) < 2) {
            echo $this->help();
        } elseif($argv[1] === 'plugin') {
            echo $this->createPlugin();
        } elseif($argv[1] === 'model') {
            echo $this->createModel();
        } elseif($argv[1] === 'controller') {
            echo $this->createController();
        } elseif($argv[1] === 'translations') {
            echo $this->updateTranslations();
        } else {
            echo $this->help();
        }
    }

    private function createController()
    {
        $option = (int) $this->prompt('1=Controller, 2=ListController, 3=EditController');
        if(false === $this->isCoreFolder() && false === $this->isPluginFolder()) {
            return "Esta no es la carpeta raíz del plugin.\n";
        } elseif($option === 2) {
            return $this->createListController();
        } elseif($option === 3) {
            return $this->createEditController();
        } elseif($option < 1 || $option > 3) {
            return "Opción no válida.\n";
        }

        $name = $this->prompt('Nombre del controlador', '/^[A-Z][a-zA-Z0-9_]*$/');
        $filename = $this->isCoreFolder() ? 'Core/Controller/'.$name.'.php' : 'Controller/'.$name.'.php';
        if(file_exists($filename)) {
            return "El controlador ".$name." ya existe.\n";
        } elseif(empty($name)) {
            return '';
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Controller;

class '.$name.' extends \\FacturaScripts\\Core\\Base\\Controller
{
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
    {{ parent() }}
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

class Cron extends \\FacturaScripts\\Core\\Base\\CronClass
{
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

    private function createEditController()
    {
        $name = $this->prompt('Nombre del modelo a utilizar', '/^[A-Z][a-zA-Z0-9_]*$/');
        $filename = $this->isCoreFolder() ? 'Core/Controller/Edit'.$name.'.php' : 'Controller/Edit'.$name.'.php';
        if(file_exists($filename)) {
            return "El controlador Edit".$name." ya existe.\n";
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Controller;

class Edit'.$name.' extends \\FacturaScripts\\Core\\Lib\\ExtendedController\\EditController
{
    public function getModelClassName() {
        return "'.$name.'";
    }
}');
        $xmlviewFilename = $this->isCoreFolder() ? 'Core/XMLView/Edit'.$name.'.xml' : 'XMLView/Edit'.$name.'.xml';
        if(file_exists($xmlviewFilename)) {
            return '';
        }

        echo '* '.$xmlviewFilename."\n";
        file_put_contents($xmlviewFilename, '<?xml version="1.0" encoding="UTF-8"?>
<view>
    <columns>
        <group name="data" numcolumns="12">
            <column name="code" order="100">
                <widget type="text" fieldname="id" />
            </column>
            <column name="creation-date" order="110">
                <widget type="datetime" fieldname="creationdate" readonly="dinamic" />
            </column>
        </group>
    </columns>
</view>');
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

class Init extends \\FacturaScripts\\Core\\Base\\InitClass
{
    public function init() {
        /// se ejecutar cada vez que carga FacturaScripts (si este plugin está activado).
    }

    public function update() {
        /// se ejecutar cada vez que se instala o actualiza el plugin
    }
}");
    }

    private function createListController()
    {
        $menu = $this->prompt('Menú');
        $title = $this->prompt('Título');
        $name = $this->prompt('Nombre del modelo a utilizar', '/^[A-Z][a-zA-Z0-9_]*$/');
        $filename = $this->isCoreFolder() ? 'Core/Controller/List'.$name.'.php' : 'Controller/List'.$name.'.php';
        if(file_exists($filename)) {
            return "El controlador List".$name." ya existe.\n";
        } elseif(empty($name)) {
            return '';
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Controller;

class List'.$name.' extends \\FacturaScripts\\Core\\Lib\\ExtendedController\\ListController
{
    public function getPageData() {
        $pageData = parent::getPageData();
        $pageData["title"] = "'.$title.'";
        $pageData["menu"] = "'.$menu.'";
        $pageData["icon"] = "fas fa-search";
        return $pageData;
    }

    protected function createViews() {
        $this->createViews'.$name.'();
    }

    protected function createViews'.$name.'(string $viewName = "List'.$name.'") {
        $this->addView($viewName, "'.$name.'", "'.$title.'");
    }
}');
        $xmlviewFilename = $this->isCoreFolder() ? 'Core/XMLView/List'.$name.'.xml' : 'XMLView/List'.$name.'.xml';
        if(file_exists($xmlviewFilename)) {
            return '';
        }

        echo '* '.$xmlviewFilename."\n";
        file_put_contents($xmlviewFilename, '<?xml version="1.0" encoding="UTF-8"?>
<view>
    <columns>
        <column name="code" order="100">
            <widget type="text" fieldname="id" />
        </column>
        <column name="creation-date" display="right" order="110">
            <widget type="datetime" fieldname="creationdate" />
        </column>
    </columns>
</view>');
    }

    private function createModel()
    {
        $name = $this->prompt('Nombre del modelo (singular)', '/^[A-Z][a-zA-Z0-9_]*$/');
        $tableName = strtolower($this->prompt('Nombre de la tabla (plural)', '/^[a-zA-Z][a-zA-Z0-9_]*$/'));
        if(empty($name) || empty($tableName)) {
            return '';
        } elseif(false === $this->isCoreFolder() && false === $this->isPluginFolder()) {
            return "Esta no es la carpeta raíz del plugin.\n";
        }

        $filename = $this->isCoreFolder() ? 'Core/Model/'.$name.'.php' : 'Model/'.$name.'.php';
        if(file_exists($filename)) {
            return "El modelo ".$name." ya existe.\n";
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Model;

class '.$name.' extends \\FacturaScripts\\Core\\Model\\Base\\ModelClass
{
    use \\FacturaScripts\\Core\\Model\\Base\\ModelTrait;

    public $creationdate;
    public $id;

    public function clear() {
        $this->creationdate = \date(self::DATETIME_STYLE);
    }

    public static function primaryColumn() {
        return "id";
    }

    public static function tableName() {
        return "'.$tableName.'";
    }
}');
        $tableFilename = $this->isCoreFolder() ? 'Core/Table/'.$tableName.'.xml' : 'Table/'.$tableName.'.xml';
        if(file_exists($tableFilename)) {
            return '';
        }

        echo '* '.$tableFilename."\n";
        file_put_contents($tableFilename, '<?xml version="1.0" encoding="UTF-8"?>
<table>
    <column>
        <name>id</name>
        <type>serial</type>
    </column>
    <column>
        <name>creationdate</name>
        <type>timestamp</type>
    </column>
    <constraint>
        <name>'.$tableName.'_pkey</name>
        <type>PRIMARY KEY (id)</type>
    </constraint>
</table>');
    }

    private function createPlugin()
    {
        $name = $this->prompt('Nombre del plugin', '/^[A-Z][a-zA-Z0-9_]*$/');
        if(empty($name)) {
            return '';
        } elseif(file_exists('.git') || file_exists('.gitignore') || file_exists('facturascripts.ini')) {
            return "No se puede crear un plugin en esta carpeta.\n";
        } elseif(file_exists($name)) {
            return "El plugin ".$name." ya existe.\n";
        }
        
        mkdir($name, 0755);
        $folders = [
            'Assets/CSS','Assets/Images','Assets/JS','Controller','Data/Codpais/ESP','Data/Lang/ES','Extension/Controller',
            'Extension/Model','Extension/Table','Extension/XMLView','Model/Join','Table','Translation','View','XMLView'
        ];
        foreach($folders as $folder) {
            echo '* '.$name.'/'.$folder."\n";
            mkdir($name.'/'.$folder, 0755, true);
        }

        foreach(explode(',', self::TRANSLATIONS) as $filename) {
            echo '* '.$name.'/Translation/'.$filename.".json\n";
            file_put_contents($name.'/Translation/'.$filename.'.json', '{
    "'.$name.'": "'.$name.'"
}');
        }

        $this->createGitIgnore($name);
        $this->createCron($name);
        $this->createIni($name);
        $this->createInit($name);
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
        return 'FacturaScripts Maker v' . self::VERSION . "

create:
$ fsmaker plugin
$ fsmaker model
$ fsmaker controller

download:
$ fsmaker translations\n";
    }

    private function isCoreFolder()
    {
        return file_exists('Core/Translation') && false === file_exists('facturascripts.ini');
    }

    private function isPluginFolder()
    {
        return file_exists('facturascripts.ini');
    }

    private function prompt($label, $pattern = '')
    {
        echo $label . ': ';
        $matches = [];
        $value = trim(fgets(STDIN));
        if(!empty($pattern) && 1 !== preg_match($pattern, $value, $matches)) {
            echo "Valor no válido. Debe cumplir: ".$pattern."\n";
            return '';
        }

        return $value;
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
            return "Esta no es la carpeta raíz del plugin.\n";
        }

        if(empty($project)) {
            return "Proyecto desconocido.\n";
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

            echo " - vacío\n";
        }
    }
}

new fsmaker($argv);