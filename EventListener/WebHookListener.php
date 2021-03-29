<?php
namespace WebHookBundle\EventListener;
  
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use WebHookBundle\Utils\ExportDataObject;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class WebHookListener {

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

        if ($e instanceof DataObjectEvent ) {
            $dataObject = $e->getObject();

            if($dataObject->getType() != "folder") {
                $entityType = $dataObject->getClassName();
            } else {
                return 0;
            }

            $webHooks = new \Pimcore\Model\DataObject\WebHook\Listing();
            $webHooks->setUnpublished(true);
            $webHooks->setCondition("EntityType LIKE ? AND ListenedEvent LIKE ?", [$entityType, $eventName]);
            $webHooks = $webHooks->load();
            
            if (count($webHooks)) {

                $exportData = new ExportDataObject();
                $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder(), new XmlEncoder()]);
                $arrayData["data"] = $exportData->getDataForObject($dataObject);
                $arrayData["arguments"] = $e->getArguments();

                $jsonContent = $serializer->serialize($arrayData, 'json');

                $signature = $this->generateSignature($jsonContent);
                if(!$signature["success"]){
                    return;
                }

                if(!$apiKey = \Pimcore\Model\WebsiteSetting::getByName('WebHookApi-key')){
                    echo("No api-key found");
                }
                $apiKey = $apiKey->getData();

                foreach ($webHooks as $webHook) {
                    $client = HttpClient::create();
                    $url = $webHook->getURL();
                    $method = 'POST';
                    $headers = ["x-listen-event" => $eventName,
                                "x-apikey" => $apiKey,
                                "x-signature" => base64_encode($signature["signature"])];
                    try {
                        $response = $client->request($method, $url, ['headers' => $headers, 'body' => $jsonContent]);
                        \Pimcore\Log\Simple::log("WebHook", "Event: ".$eventName." Class: ".$entityType." object Id ".$dataObject->getId()." host: ".$webHook->getURL()." Response: ".$response->getStatusCode());

                    } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e){
                        \Pimcore\Log\Simple::log("WebHook", "Event: ".$eventName." Class: ".$entityType." object Id ".$dataObject->getId()." host: ".$webHook->getURL()." Response: ".$e->getMessage());
                    }
                }
           }
        }
    }

    public function generateSignature($jsonContent) {

        $keys = array();
        if(!$privateKey = \Pimcore\Model\WebsiteSetting::getByName('WebHookPrivateKey')){
            echo("No private key found");
            return $keys["success"] = false;
        }
        $privateKey = $privateKey->getData();

        openssl_sign($jsonContent, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        $keys["signature"] = $signature;
        $keys["success"] = true;

        return $keys;
    }
}