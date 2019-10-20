<?php
namespace Upgrade\Console\Command;

use Origin\Filesystem\File;
use Origin\Filesystem\Folder;
use Origin\Console\Command\Command;
use App\Exception\ApplicationException;

class UpgradeCommand extends Command
{
    protected $name = 'upgrade';
    protected $description = 'Upgrades source code from version 1.x -> 2.0';

    /**
     * Namespace Changes
     *
     * @var array
     */
    protected $namespaceChanges = [
        'App\Command' => 'App\Console\Command',
        'App\Controller' => 'App\Http\Controller',
        'App\Middleware' => 'App\Http\Middleware',
        'App\View' => 'App\Http\View',
        'Origin\Command' => 'Origin\Console\Command',
        'Origin\Controller' => 'Origin\Http\Controller',
        'Origin\View' => 'Origin\Http\View',
        'Origin\Http\Middleware' => 'Origin\Http\Middleware\Middleware',
        'Origin\Exception\Exception' => 'Origin\Core\Exception\Exception',
        'Origin\Exception\InvalidArgumentException' => 'Origin\Core\Exception\InvalidArgumentException',
        'Origin\Exception\\' => 'Origin\Http\Exception\\',
        'Origin\Utility\Collection' => 'Origin\Collection\Collection',
        'Origin\Utility\Csv' => 'Origin\Csv\Csv',
        'Origin\Utility\Dom' => 'Origin\Dom\Dom',
        'Origin\Mailer\Email' => 'Origin\Email\Email',
        'Origin\Utility\File' => 'Origin\Filesystem\File',
        'Origin\Utility\Folder' => 'Origin\Filesystem\Folder',
        'Origin\Utility\Html' => 'Origin\Html\Html',
        'Origin\Utility\Yaml' => 'Origin\Yaml\Yaml',
        'Origin\Utility\Markdown' => 'Origin\Markdown\Markdown',
        'Origin\Utility\Inflector' => 'Origin\Inflector\Inflector',
        'Origin\Utility\Security' => 'Origin\Security\Security',
        'Origin\Utility\Text' => 'Origin\Text\Text',
        
    ];

    /**
     * Classes which need to be Renamed
     *
     * @var array
     */
    protected $renameClasses = [
        'AppController' => 'ApplicationController',
        'AppModel' => 'ApplicationModel',
        'AppService' => 'ApplicationService',
        'AppMailer' => 'ApplicationMailer',
        'AppJob' => 'ApplicationJob',
        'AppHelper' => 'ApplicationHelper'
    ];

    protected $find = [
        'function beforeRender(' => 'controller callback',
        'function beforeFilter(' => 'controller callback',
        'function beforeRedirect(' => 'controller callback',
        'function afterFilter(' => 'controller callback',
        'function beforeFind(' => 'model callback',
        'function afterFind(' => 'model callback',
        'function beforeValidate(' => 'model callback',
        'function afterValidate' => 'model callback',
        'function beforeSave(' => 'model callback',
        'function afterSave' => 'model callback',
        'function beforeDelete(' => 'model callback',
        'function afterDelete' => 'model callback',
        'response->cookie(' => 'changed params',
        'Cookie->write(' => 'changed params',
        'Security::uid(' => 'changed result',
        '$this->loadBehavior(' => 'behaviors removed',
        'public $datasource' => 'renamed',
        'public $' => 'public visibility',
        'extends Middleware' => 'middleware changed',
        'extends Behavior' => 'behaviors removed',
        'File::info' => 'changed result',
        'Use Origin\Log\Engine\BaseEngine' => 'changed return type',
        '$this->assertContains(' => 'PHPUnit 8 wont accept string',
        'extends ApplicationMailer' => 'templates names and folder structure changed',
        'Inflector::add' => 'method removed',
        'SRC' => 'constant removed',
        'parent::initialize(' => 'method removed in Framework classes',
        'use Origin\Collection\Collection' => 'require through composer',
        'use Origin\Csv\Csv' => 'require through composer',
        'use Origin\Text\Text' => 'require through composer',
        'use Origin\Markdown\Markdown' => 'require through composer',
        'use Origin\Yaml\Yaml' => 'require through composer',
        'use Origin\Filesystem\Folder' => 'require through composer',
        'use Origin\Filesystem\File' => 'require through composer',
        'collection(' => 'require through composer',
        '$this->runCommand' => 'changed return type'
        
    ];

    protected function initialize() : void
    {
        $this->addOption('dry-run', [
            'type' => 'boolean',
            'description' => 'Does a dry run, changes are only displayed']);
    }

    protected function execute() : void
    {
        $this->out('<cyan>OriginPHP Upgrade tool</cyan>');
        $this->io->nl();

        // First lets move the folders
        $this->moveFolders(['Controller','View','Middleware'], 'Http');
        $this->moveFolders(['Command'], 'Console');
        
        // Adjust namespaces etc
        $this->replace($this->namespaceChanges);
        $this->rename($this->renameClasses);

        $this->find($this->find);

        $this->info('Other files that will contain settings');
        $this->io->list([
            'composer.json','config/.env.php','config/.env.php.default','config/application.php','config/routes.php'
        ]);
    }

    protected function find(array $data)
    {
        $out = [];
        foreach ([APP, TESTS. '/TestCase'] as $root) {
            $files = Folder::list($root, ['recursive' => true]);
            foreach ($files as $item) {
                $file = $item['path'] .'/' . $item['name'];
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $changes = [];
                $contents = file_get_contents($file);
                if (in_array($extension, ['php','ctp'])) {
                    foreach ($data as $find => $replace) {
                        if (strpos($contents, $find) !== false) {
                            $changes[$find] = $replace;
                        }
                    }
                }
                if ($changes) {
                    $out[str_replace(ROOT, '', $file)] = $changes;
                }
            }
        }
        if ($out) {
            $this->warning('The following need your attention');
 
            $tables = [];
            foreach ($out as $file => $changes) {
                if (! isset($tables[$file])) {
                    $tables[$file] = [
                        ['Found','Rule']
                    ];
                }
                foreach ($changes as $find => $replace) {
                    $tables[$file][] = [$find,$replace];
                }
            }
            foreach ($tables as $filename => $table) {
                $this->info($filename);
                $this->io->table($table);
                $this->io->nl();
            }
        }
    }

    /**
     * Renames files and then updates references to them
     *
     * @param array $files ['OldClass' => 'NewClass']
     * @return void
     */
    protected function rename(array $files) : void
    {
        $out = [];
        foreach ([APP,TESTS . '/TestCase'] as $root) {
            $list = Folder::list($root, ['recursive' => true]);
            foreach ($list as $item) {
                $file = $item['path'] .'/' . $item['name'];
                $filename = pathinfo($file, PATHINFO_FILENAME);
              
                if (isset($files[$filename])) {
                    $from = str_replace(ROOT, '', $file);
                    $out[] = [$from,str_replace(ROOT, '', $item['path']) .'/'.$files[$filename] .'.php'];
                }
            }
        }
    
        if ($out) {
            if ($this->options('dry-run')) {
                $this->info('Classes to be Renamed');
                $this->io->table(array_merge([['From','To']], $out));
                $this->io->nl();
            } else {
                foreach ($out as $action) {
                    list($from, $to) = $action;
   
                    if (! File::rename(ROOT . $from, ROOT . $to)) {
                        throw new ApplicationException('Error renaming ' . ROOT . $from);
                    }
                    $this->io->status('ok', 'Rename ' .$from);
                }
            }
            $this->replace($files);
        }
    }

    /**
     * Analyses files for find and replace
     *
     * @param array $from
     * @param string $to
     * @return void
     */
    protected function replace(array $data) : void
    {
        $out = [];
        foreach ([APP, TESTS. '/TestCase'] as $root) {
            $files = Folder::list($root, ['recursive' => true]);
            foreach ($files as $item) {
                $file = $item['path'] .'/' . $item['name'];
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $changes = [];
                $contents = file_get_contents($file);
                if (in_array($extension, ['php','ctp'])) {
                    foreach ($data as $find => $replace) {
                        if (strpos($contents, $find) !== false) {
                            $changes[$find] = $replace;
                        }
                    }
                }
                if ($changes) {
                    $out[str_replace(ROOT, '', $file)] = $changes;
                }
            }
        }
        if ($out) {
            if ($this->options('dry-run')) {
                $this->info('Find and Replace');
                $table = [
                    ['File','Find','Replace']
                ];
                foreach ($out as $file => $changes) {
                    foreach ($changes as $find => $replace) {
                        $table[] = [$file,$find,$replace];
                    }
                }
                $this->io->table($table);
                $this->io->nl();
            } else {
                foreach ($out as $file => $changes) {
                    $contents = file_get_contents(ROOT . $file);
                 
                    foreach ($changes as $find => $replace) {
                        $contents = str_replace($find, $replace, $contents);
                    }

                    file_put_contents(ROOT . $file, $contents);
                    $this->io->status('ok', 'Changed ' . ROOT . $file);
                }
            }
        }
    }

    /**
     * Analyses folder for moving
     *
     * @param array $from
     * @param string $to
     * @return void
     */
    protected function moveFolders(array $from, string $to) : void
    {
        $out = [];

        foreach ([APP,TESTS. '/TestCase'] as $root) {
            foreach ($from as $folder) {
                $item = $root . '/' . $folder;
                if (file_exists($item) and is_dir($item)) {
                    $out[] = [str_replace(ROOT, '', $item), str_replace(ROOT, '', $root) .  '/' . $to  .'/' . $folder];
                }
            }
        }

        if ($out) {
            if ($this->options('dry-run')) {
                $this->info('Folders beeing moved');
                $this->io->table(array_merge([['From','To']], $out));
                $this->io->nl();
            } else {
                foreach ($out as $action) {
                    list($from, $to) = $action;
             
                    // Actually we are merging
                    if (! Folder::copy(ROOT . $from, ROOT . $to) or ! Folder::delete(ROOT . $from, ['recursive' => true])) {
                        throw new ApplicationException('Error moving ' . ROOT . $from);
                    }
                    $this->io->status('ok', 'Moving ' . ROOT . $from);
                }
            }
        }
    }
}
