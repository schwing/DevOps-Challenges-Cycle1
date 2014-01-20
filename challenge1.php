<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\ServerState;
use OpenCloud\Compute\Constants\Network;

// Load custom class(es)
use Challenges\Auth;

$credsFile = $_SERVER['HOME'] . "/.rackspace_cloud_credentials";

try {
    // Auth using credentials from $credsFile
    $client = Auth::authenticate($credsFile);

    // Set the compute region to DFW
    $compute = $client->computeService('cloudServersOpenStack', 'DFW');
/*
    // Debian Wheezy image
    $image = $compute->image('857d7d36-34f3-409f-8435-693e8797be8b');

    // 512MB flavor
    $flavor = $compute->flavor('2');

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
 */
} catch (Exception $e) {
    die($e->getMessage());
}
?>
