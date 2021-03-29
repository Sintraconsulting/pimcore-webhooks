# WebHookBundle Plugin

This extention provide an easy way to send dataobjects to an external sites via
 json, whenever a pimcore event occurs on a specified class. This bundle installs the webHook class through which it is possible to define, from the UI, which dataObjects to listen for sending data to an external website, whenever a pimcore event occurs on the specified dataobject.

# How to Install WebHookBundle

## 1. download and install the bundle from composer
For installig WebHookBundle, the first step is to open your terminal and type:
```bash
docker-compose exec php bash
composer require sintra/pimcore-webhooks:dev-main
```
After you have installed the Toolbox Bundle via composer, open pimcore backend and go to `Tools` => `Extension`:
- Click the green `+` Button in `Enable / Disable` row
- Click the green `+` Button in `Install/Uninstall` row

![](assets/ExtentionManager.png?raw=true)


## 2. Configure the bundle

Now that the bundle is installed you can start creating your webhooks!. Follows the next stesp.
1. create a folder called `Webhooks`. Although this is not mandatory and you can name it as you want.
2. create a webhook object in it. You can specify the name of the class to monitor, the event to listen to, and the URL of the site to send the json object

The following video shows how this can be done in seconds.
![](assets/CreateWebHooks.gif?raw=true)
Now you are ready to use the Webhok bundle!

# Webook configuration


## Availbale options
You can specify:
- The class to monitor
- The URL to send data to
- The event to listen


The aviable event are:
  - preAdd
  - postAdd
  - postAddFailure
  - preUpdate
  - postUpdate
  - postUpdateFailure
  - deleteInfo
  - preDelete
  - postDelete
  - postDeleteFailure
  - postCopy
  - postCsvItemExport

Once the event occurs, the json represtantion of you data is sent to the specified url. In the header it is specified which event was launched.

Each event will trigger a request like this:

- headers
  - `x-listen-event`: contain the name of the event launched
  - `x-apikey`: contain a random generated key for autentication porpouse
  - `x-signature`: contain the data signature created with OPENSSL_ALGO_SHA1; public key is store in website settings
- body contains two main parts:
  - `dataObject`: json representation of your dataObject
  - `argumentrs`: exception code and message throws if a failure occurs


## Security settings
To ensure the authenticity and security of the information transmitted, in the 
header there are a randomly generated api-key, and a signature generated 
through a pair of keys (public / private), stored in the Pimcore website setting.
These are created automatically when the bundle is installed, but you can use your own.

![](assets/WebSiteSettings.png?raw=true)

To run the tests you don't need to create any webhooks or classes, these will be
created automatically, you just have to initialize the testURL attribute with a valid url.
