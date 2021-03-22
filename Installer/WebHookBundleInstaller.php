<?php

namespace WebHookBundle\Installer;

use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class WebHookBundleInstaller extends MigrationInstaller
{
    public function migrateInstall(Schema $schema, Version $version) {
        $this->installClasses();
        $this->installKeys();
    }

    public function migrateUninstall(Schema $schema, Version $version) {
    
    }

    private function installKeys() {
                
        if(!\Pimcore\Model\WebsiteSetting::getByName('WebHookApi-key')){
            $settingApiKey = new \Pimcore\Model\WebsiteSetting();
            $settingApiKey->setName("WebHookApi-key");
            $settingApiKey->setType("text");
            $settingApiKey->setData(md5(random_bytes(300)));
            $settingApiKey->save();
        } else {
            $this->outputWriter->write(sprintf('\nFound WebHookApi-key\n'));
        }

        if(\Pimcore\Model\WebsiteSetting::getByName('WebHookPublicKey') && \Pimcore\Model\WebsiteSetting::getByName('WebHookPrivateKey')){
            $this->outputWriter->write(sprintf('\nFound public and private key\n'));
        } else {
            $new_key_pair = openssl_pkey_new(array(
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ));
    
            openssl_pkey_export($new_key_pair, $privateKey);
            
            $details = openssl_pkey_get_details($new_key_pair);
            $publicKey = $details['key'];
            
            if($settingPublicKey = \Pimcore\Model\WebsiteSetting::getByName('WebHookPublicKey')){
                $settingPublicKey->delete();
            }
            $settingPublicKey = new \Pimcore\Model\WebsiteSetting();
            $settingPublicKey->setName("WebHookPublicKey");
            $settingPublicKey->setType("text");
            $settingPublicKey->setData($publicKey);
            $settingPublicKey->save();
            
            if($settingPrivateKey = \Pimcore\Model\WebsiteSetting::getByName('WebHookPrivateKey')) {
                $settingPrivateKey->delete();
            }
            $settingPrivateKey = new \Pimcore\Model\WebsiteSetting();
            $settingPrivateKey->setName("WebHookPrivateKey");
            $settingPrivateKey->setType("text");
            $settingPrivateKey->setData($privateKey);
            $settingPrivateKey->save();        
        }
    }

    private function installClasses() {
        $installSourcesPath = __DIR__ . "/../Resources/install";
        $classesToInstall = [
            "WebHook" => "WB_WebHook"
        ];

        $classes = [];
        foreach ($classesToInstall as $className => $classIdentifier) {
            $filename = sprintf('class_%s_export.json', $className);
            $path = $installSourcesPath . "/class_sources/" . $filename;
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
                $this->outputWriter->write(sprintf('     <comment>WARNING:</comment> Skipping class "%s" as it already exists', $key));
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