<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;

$credsFile = $_SERVER['HOME'] . "/.rackspace_cloud_credentials";

try {
    // Try to open $credsFile and read the credentials from it
    if (($cloudCreds = @parse_ini_file($credsFile)) == false) {
        throw new Exception("Missing or unreadable INI file: " . $credsFile . "\n");
    }

    // Auth using credentials from $credsFile
    $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
        'username' => $cloudCreds['username'],
        'apiKey'   => $cloudCreds['api_key']
    ));

    $cloudFiles = $client->objectStoreService('cloudFiles', 'DFW');

    // Get the new container name
    // TODO: constraints on input from (https://github.com/rackspace/php-opencloud/blob/master/docs/userguide/ObjectStore/Storage/Container.md):
    //  "Container names must be valid strings between 0 and 256 characters. Forward slashes are not currently permitted."
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "Please input a name for the new container: ";
        $handle = fopen ("php://stdin","r");
        $containerName = trim(fgets($handle));
        if ($containerName == NULL) {
            echo "Invalid input, please try again.\n\n";
        } else {
            $validInput = TRUE;
            echo "\n";
        }
    }

    // Try to create the container
    $createResponse = $cloudFiles->createContainer($containerName);

    // Throw an exception if we weren't able to create the container.
    if ($createResponse == FALSE) {
        // Try to get the container. If this fails for any reason, it will throw an exception.
        // Otherwise, the container exists because we were able to get it, so we throw our own custom exception and exit.
        $cloudFiles->getContainer($containerName);
        throw new Exception(sprintf("A container named \"%s\" already exists.\nPlease run this script again with a different container name.\n", $containerName));
    }

    

} catch (Exception $e) {
    die($e->getMessage());

}
?>
