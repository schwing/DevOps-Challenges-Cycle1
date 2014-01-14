<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\ServerState;
use OpenCloud\Compute\Constants\Network;

$credsFile = $_SERVER['HOME'] . "/.rackspace_cloud_credentials";

// Try opening $credsFile and fetching the credentials from it
try {
    if (($cloudCreds = @parse_ini_file($credsFile)) == false)
        throw new Exception("Missing or unreadable INI file: " . $credsFile . "\n");
} catch (Exception $e) {
    die($e->getMessage());
}

try {
    // Auth using credentials from $credsFile
    $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
        'username' => $cloudCreds['username'],
        'apiKey'   => $cloudCreds['api_key']
    ));

    // Set the compute region to DFW
    $compute = $client->computeService('cloudServersOpenStack', 'DFW');

    // Debian Wheezy image
    $image = $compute->image('857d7d36-34f3-409f-8435-693e8797be8b');

    // 512MB flavor
    $flavor = $compute->flavor('2');
/*
    echo "How many servers would you like to build? Input a number from 1-3:";
    $handle = fopen ("php://stdin","r");
    $line = trim(fgets($handle));
    $filter_options = array(
        'options' => array(
            'min_range' => 1,
            'max_range' => 3
        )
    );
    if(filter_var($line, FILTER_VALIDATE_INT, $filter_options) !== FALSE){
            echo "ABORTING!\n";
                exit;
    }
 */
    // Instantiate a server resource
    $server = $compute->server();

    // Spin it up
    $response = $server->create(array(
        'name'     => 'Challenge 1 Server',
        'image'    => $image,
        'flavor'   => $flavor,
        'networks' => array(
            $compute->network(Network::RAX_PUBLIC),
            $compute->network(Network::RAX_PRIVATE)
        )
    ));

    // Define the callback function for building the server
    $callback = function($server) {
    // If there is an error, exit and dump the error
    if (!empty($server->error)) {
        var_dump($server->error);
        exit;
    } else {
        $name = "Building cloud server: " . $server->name();
        $status = "Status: " . $server->status();
        if (!isset($server->progress)) {
            $server->progress = 0;
        }
        $progress = "Progress: " . $server->progress;

        echo $name . "\n";
        echo $status . "\n";
        echo $progress . "%";

        if ($server->status == "BUILD") {
            echo chr(27) . "[0G"; // Set cursor to first column for overwriting
            echo chr(27) . "[2A"; // Set cursor up 2 lines for overwriting
        } elseif ($server->status == "ACTIVE") {
            echo "\n\nBuild complete.\n";
            echo sprintf("Server IP: %s\n", $server->accessIPv4);
            echo sprintf("root password: %s\n", $server->adminPass);
        } else {
            // Something went wrong
            echo "Build failed!";
        }

    }
    };

    // Loop, waiting for the server to be built
    $server->waitFor(ServerState::ACTIVE, 600, $callback);

} catch (\OpenCloud\Common\Exceptions\CredentialError $e) {
    die($e->getMessage());

} catch (\Guzzle\Http\Exception\BadResponseException $e) {
    die($e->getMessage());

} catch (Exception $e) {
    die($e->getMessage());

}
?>
