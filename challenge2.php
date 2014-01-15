<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\ServerState;
use OpenCloud\Compute\Constants\Network;

$credsFile = $_SERVER['HOME'] . "/.rackspace_cloud_credentials";

// Define the function to loop while the servers are building
function waitLoop($servers, $timeout, $interval) {
    while (true) {
        $numLines = 0;
        $completedServers = 0;

        foreach ($servers as &$server) {
            // Define finishing states
            $states = array('ACTIVE', 'ERROR');

            $startTime = time();

            $server->refresh();

            if ((time() - $startTime) > $timeout) {
                echo sprintf("Request timed out: %s", str_pad($server->name(), 50));
                $numLines = $numLines + 1;
                return;
            }

            // If no error was returned, check on the progress of the server build
            if (empty($server->error)) {
                $name = "Building cloud server: " . $server->name();
                $status = "Status: " . $server->status();
                if (!isset($server->progress)) {
                    $server->progress = 0;
                }
                $progress = sprintf("Progress: %s%%", $server->progress);

                if ($server->status == "BUILD") {
                    echo sprintf("%s\n", str_pad($name, 50));
                    echo sprintf("%s\n", str_pad($status, 50));
                    echo sprintf("%s\n\n", str_pad($progress, 50));
                } elseif ($server->status == "ACTIVE") {
                    echo sprintf("Build complete: %s\n", str_pad($server->name(), 50));
                    echo sprintf("Server IP: %s\n", str_pad($server->accessIPv4, 50));
                    echo sprintf("root password: %s\n\n", str_pad($server->adminPass, 50));
                }
                // Add to the total number of lines to remove next round
                $numLines = $numLines + 4;
            } else {
                // This server build failed
                echo sprintf("Build failed: %s\n", str_pad($server->name(), 50));
                $numLines = $numLines + 1;
            }

            if (in_array($server->status(), $states)) {
                $completedServers++;
            }
        }


        // Check to see if servers are still being built, and loop if so
        if ($completedServers != count($servers)) {
            echo chr(27) . "[0G"; // Move the cursor to the first column for overwriting
            echo chr(27) . sprintf("[%sA", $numLines); // Move the cursor up $numLines lines for overwriting

            // Sleep for $interval
            sleep($interval);
        } else {
            // All servers are in ACTIVE or ERROR state. Exit the wait loop
            return;
        }
    }
}

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

    // Get the number of servers to build
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "How many servers would you like to build? Input a number from 1-3: ";
        $handle = fopen ("php://stdin","r");
        $numServers = trim(fgets($handle));
        $filter_options = array(
            'options' => array(
                'min_range' => 1,
                'max_range' => 3
            )
        );
        if (filter_var($numServers, FILTER_VALIDATE_INT, $filter_options) == FALSE){
            // Invalid input
            echo "Invalid input, please try again.\n\n";
        } else {
            // Valid input
            $validInput = TRUE;
            echo "\n";
        }
    }

    // Get the name scheme for the servers (not validating input here)
    echo sprintf("Input naming scheme for %s server(s)--will be of the form \$name# (blank will name numerically only): ", $numServers);
    $handle = fopen ("php://stdin","r");
    $nameScheme = fgets($handle);
    echo "\n";

    for ($serverCount = 1; $serverCount <= $numServers; $serverCount++) {
        // Instantiate a server resource
        $servers[$serverCount] = $compute->server();

        // Spin it up
        $buildResponse[$serverCount] = $servers[$serverCount]->create(array(
            'name'     => sprintf('%s%s', $nameScheme, $serverCount),
            'image'    => $image,
            'flavor'   => $flavor,
            'networks' => array(
                $compute->network(Network::RAX_PUBLIC),
                $compute->network(Network::RAX_PRIVATE)
            )
        ));
    }
    // Loop, waiting for the servers to be built
    waitLoop($servers, 600, 15);

} catch (Exception $e) {
    die($e->getMessage());

}
?>
