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

    $callback = function($server) {
    if (!empty($server->error)) {
        var_dump($server->error);
        exit;
    } else {
        echo sprintf(
            "Waiting on %s/%-12s %4s%%",
            $server->name(),
            $server->status(),
            isset($server->progress) ? $server->progress : 0
        );
    }
    };

    $server->waitFor(ServerState::ACTIVE, 600, $callback);

} catch (\OpenCloud\Common\Exceptions\CredentialError $e) {
    die($e->getMessage());

} catch (\Guzzle\Http\Exception\BadResponseException $e) {
    die($e->getMessage());

} catch (Exception $e) {
    die($e->getMessage());

}
?>
