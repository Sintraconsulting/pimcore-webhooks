# WebHookBundle Plugin

This bundle provide an automatic sending of dataobjects to an external sites via
 json, whenever a pimcore event occurs on a specified class.

## How to Install WebHookBundle

Clone the repository in the src/WebHookBundle directory in your Pimcore
installation

Enable and Install the WebHookBundle by the Pimcore Extensions Manager directly on Pimcore interface.


## Description

This bundle installs the webHook class through which it is possible to define,
from the UI, which dataObjects to send to an external site, when a pimcore event
 occurs on the specified dataobject.
![](assets/ClassWebHook.png?raw=true)

After installing the bundle, create a WebHook object by entering the class name, the event to listen to and the url of the site to send to. 
Once the set event occurs, the json of the object is generated, and sent to the specified url. In the header it is specified which event was launched.

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


To ensure the authenticity and security of the information transmitted, in the 
header there are a randomly generated api-key, and a signature generated 
through a pair of keys (public / private), stored in the Pimcore website setting.
These are created automatically when the bundle is installed, but you can use your own.

![](assets/WebSiteSettings.png?raw=true)

To use the tests you need to specify a valid url.