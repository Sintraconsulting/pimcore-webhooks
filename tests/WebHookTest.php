<?php

namespace Tests\WebHookBundle;

use Doctrine\DBAL\Migrations\AbortMigrationException;
use WebHookBundle\EventListener\WebHookListener;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Test\KernelTestCase;

class WebHookTest extends KernelTestCase {

    private $webHookListener;
    private $testURL  = "https://enfen5kd4e3ot.x.pipedream.net/"; //Set a valid url
    
    protected function setUp(): void {

        self::bootKernel();
        $container = self::$container;
        $logger = $container->get('Pimcore\Log\ApplicationLogger');
        $this->webHookListener = new WebHookListener($logger);
        $this->installTestClass();
    }
    
    public function testA() {
        $this->assertEquals(1,1);
    }

    public function testAdd () {

        $webHooks = $this->createWebHooks();

        $testClass = new DataObject\TestClass(); 
        $testClass->setKey(\Pimcore\Model\Element\Service::getValidKey('testClass', 'object'));
        $testClass->setParentId(1);
    
        $this->assertEquals(null, $this->webHookListener->onPreAdd(new DataObjectEvent($testClass, [])));
        $testClass->save();
        $this->assertEquals(null, $this->webHookListener->onPostAdd(new DataObjectEvent($testClass, [])));

        $testClass->setTestClassInput("Test Input");
        $this->assertEquals(null, $this->webHookListener->onPreUpdate(new DataObjectEvent($testClass, [])));
        $testClass->save();
        $this->assertEquals(null, $this->webHookListener->onPostUpdate(new DataObjectEvent($testClass, [])));

        $this->assertEquals(null, $this->webHookListener->onPostCsvItemExport(new DataObjectEvent($testClass, [])));
        
        $this->assertEquals(null, $this->webHookListener->onPreDelete(new DataObjectEvent($testClass, [])));
        $testClass->delete();
        $this->assertEquals(null, $this->webHookListener->onPostDelete(new DataObjectEvent($testClass, [])));

        foreach ($webHooks as $key => $value) {
            $value->delete();
        }
    }
 
    public function createWebHooks() {
        $events = ["preAdd", "postAdd", "postAddFailure",
                  "preUpdate", "postUpdate", "postUpdateFailure",
                  "deleteInfo", "preDelete", "postDelete", "postDeleteFailure",
                  "postCopy", "postCsvItemExport"];
        $webHooks = array();
        foreach ($events as $event) {
            $webHooks[$event] = $this->createWebHook($event);
        }
        return $webHooks;
    }
    
    public function createWebHook($listenedEvent, $entityType = "TestClass") {

        $webHooks = new \Pimcore\Model\DataObject\WebHook\Listing();
        $webHooks->setUnpublished(true);
        $webHooks = $webHooks->load();

        foreach ($webHooks as $webHook) {
            if($webHook->getKey() == "webHook-".$listenedEvent) {
                return $webHook;
            }
        }
        $webHook = new DataObject\WebHook(); 
        $webHook->setKey(\Pimcore\Model\Element\Service::getValidKey("webHook-".$listenedEvent, 'object'));
        $webHook->setParentId(1);
        $webHook->setEntityType(["$entityType"]);
        $webHook->setURL($this->testURL);
        $webHook->setListenedEvent([$listenedEvent]);
        $webHook->save();
        return $webHook;
    }

    public function installTestClass() {
        $installSourcesPath = __DIR__ . "/../tests/";
        $classesToInstall = [
            "TestClass" => "WH_TestClass"
        ];

        $classes = [];
        foreach ($classesToInstall as $className => $classIdentifier) {
            $filename = sprintf('class_%s_export.json', $className);
            $path = $installSourcesPath . $filename;
            $path = realpath($path);

            if (false === $path || !is_file($path)) {
                throw new AbortMigrationException(sprintf(
                    'Class "%s" was expected in "%s" but file does not exist', $className, $path
                ));
            }
            $classes[$className] = $path;
        }

        foreach ($classes as $key => $path) {
            $class = ClassDefinition::getByName($key);

            if ($class) {
                echo('Skipping class '.$key.' as it already exists');
                continue;
            }

            $class = new ClassDefinition();
            $classIdentifier = $classesToInstall[$key];
            $class->setName($key);
            $class->setId($classIdentifier);

            $data = file_get_contents($path);
            $success = Service::importClassDefinitionFromJson($class, $data, false, true);

            if (!$success) {
                throw new AbortMigrationException(sprintf(
                    'Failed to create class "%s"',
                    $key
                ));
            }
        }
    }
}