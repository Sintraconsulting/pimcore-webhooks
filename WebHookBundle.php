<?php

namespace WebHookBundle;

use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use WebHookBundle\Installer\WebHookBundleInstaller;

class WebHookBundle extends AbstractPimcoreBundle {

/*
    use PackageVersionTrait;
    const PACKAGE_NAME = 'sintra/pimcore-webhooks';
*/

    public function getInstaller() {

        return $this->container->get(WebHookBundleInstaller::class);
        
    }
    
    /**
     * {@inheritdoc}
     */
/*
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
*/
    /**
     * @return string
     */
/*
    protected static function getPimcoreVersion()
    {
        return preg_replace('/[^0-9.]/', '', \Pimcore\Version::getVersion());
    }
*/
}
