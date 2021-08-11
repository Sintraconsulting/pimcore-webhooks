<?php
namespace WebHookBundle\EventListener;
  
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Log\ApplicationLogger;
use WebHookBundle\Utils\ExportDataObject;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class WebHookListener {
    
    private $logger;

    public function __construct(ApplicationLogger $logger) {
        $this->logger = $logger;
    }
    
    public function onPreAdd (ElementEventInterface $e) {
        $this->handleChange($e, "preAdd");
    }

    public function onPostAdd (ElementEventInterface $e) {
        $this->handleChange($e, "postAdd");
    }

    public function onPostAddFailure (ElementEventInterface $e) {
        $this->handleChange($e, "postAddFailure");
    }

    public function onPreUpdate (ElementEventInterface $e) {
        $this->handleChange($e, "preUpdate");
    }

    public function onPostUpdate (ElementEventInterface $e) {
        $this->handleChange($e, "postUpdate");
    }

    public function onPostUpdateFailure (ElementEventInterface $e) {
        $this->handleChange($e, "postUpdateFailure");
    }

    public function onDeleteInfo (ElementEventInterface $e) {
        $this->handleChange($e, "deleteInfo");
    }

    public function onPreDelete (ElementEventInterface $e) {
        $this->handleChange($e, "preDelete");
    }

    public function onPostDelete (ElementEventInterface $e) {
        $this->handleChange($e, "postDelete");
    }

    public function onPostDeleteFailure (ElementEventInterface $e) {
        $this->handleChange($e, "postDeleteFailure");
    }

    public function onPostCopy (ElementEventInterface $e) {
        $this->handleChange($e, "postCopy");
    }

    public function onPostCsvItemExport (ElementEventInterface $e) {
        $this->handleChange($e, "postCsvItemExport");
    }

    public function handleChange(ElementEventInterface $e, $eventName) {
        
        if ($e instanceof DataObjectEvent && class_exists('Pimcore\\Model\\DataObject\\WebHook\\Listing') ) {
            $dataObject = $e->getObject();

            if($dataObject->getType() != "folder") {
                $entityType = $dataObject->getClassName();
            } else {
                return 0;
            }

            \Pimcore\Model\DataObject\AbstractObject::setHideUnpublished(true);
            $webHooksList = new \Pimcore\Model\DataObject\WebHook\Listing();
            $webHooksList->setCondition("EntityType LIKE ? AND ListenedEvent LIKE ?", ["%".$entityType."%", "%".$eventName."%"]);
            $webHooksList = $webHooksList->load();

            $webHooks = array();
            foreach($webHooksList as $webHook) {
                if (in_array($eventName, $webHook->getListenedEvent())) {
                    if (in_array($entityType, $webHook->getEntityType())) {
                        $webHooks[] = $webHook;
                    }
                }
            }

            if (count($webHooks)) {

                $exportData = new ExportDataObject();
                $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder(), new XmlEncoder()]);
                $arrayData["dataObject"] = $exportData->getDataForObject($dataObject);
                $arrayData["arguments"] = $e->getArguments();

                $jsonContent = $serializer->serialize($arrayData, 'json');

                foreach ($webHooks as $webHook) {
                    $url = $webHook->getURL();
                    if(null == $url) {
                        continue;
                    }

                    if ($webHook->getApikey() != null) {
                        $apiKey = $webHook->getApikey();
                    } else if ($webHookApiKey = \Pimcore\Model\WebsiteSetting::getByName('WebHookApi-key')){
                        $apiKey = $webHookApiKey->getData();
                    } else {
                        $apiKey = "no-apy-key-found";
                        $this->logger->error("Web Hook error: no api-key key found \nEvent: ".$eventName." Class: ".$entityType."\nhost: ".$webHook->getURL().['relatedObject' => $dataObject->getId()]);
                        \Pimcore\Log\Simple::log("WebHook", "No webHook api-key found");
                    }

                    if ($webHook->getPrivateKey() != null) {
                        openssl_sign($jsonContent, $signature, $webHook->getPrivateKey(), OPENSSL_ALGO_SHA1);
                        $signature = base64_encode($signature);
                        $usedPrivate = "Use web hook private key";
                    } else if ($webHookprivateKey = \Pimcore\Model\WebsiteSetting::getByName('WebHookPrivateKey')){
                        openssl_sign($jsonContent, $signature, $webHookprivateKey->getData(), OPENSSL_ALGO_SHA1);
                        $signature = base64_encode($signature);
                        $usedPrivate = "Use default private key";
                    } else {
                        $signature = "no-private-key-found";
                        $this->logger->error("Web Hook error: no private key found \nEvent: ".$eventName." Class: ".$entityType."\nhost: ".$webHook->getURL().['relatedObject' => $dataObject->getId()]);
                        \Pimcore\Log\Simple::log("WebHook", "No webHook private key found");
                    }

                    $client = HttpClient::create();
                    $method = 'POST';
                    $headers = ["x-listen-event" => $eventName,
                                "x-apikey" => $apiKey,
                                "x-signature" => $signature,
                                "x-used-private-key" => $usedPrivate
                            ];
                    try {
                        $response = $client->request($method, $url, ['headers' => $headers, 'body' => $jsonContent]);
                        \Pimcore\Log\Simple::log("WebHook", "Event: ".$eventName." Class: ".$entityType." object Id ".$dataObject->getId()." host: ".$webHook->getURL()." Response: ".$response->getStatusCode());

                    } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e){
                        \Pimcore\Log\Simple::log("WebHook", "Event: ".$eventName." Class: ".$entityType." object Id ".$dataObject->getId()." host: ".$webHook->getURL()." Response: ".$e->getMessage());
                        $this->logger->error("Web Hook request error:\nEvent: ".$eventName." Class: ".$entityType."\nhost: ".$webHook->getURL()."\nResponse: ".$e->getMessage(), ['relatedObject' => $dataObject->getId()]);
                    }
                }
           }
        }
    }
}