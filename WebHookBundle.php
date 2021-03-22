<?php

namespace WebHookBundle;

use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use WebHookBundle\Installer\WebHookBundleInstaller;

class WebHookBundle extends AbstractPimcoreBundle {

    public function getInstaller() {

        return $this->container->get(WebHookBundleInstaller::class);

    }
    
}
