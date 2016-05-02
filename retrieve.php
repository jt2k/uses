<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

require 'vendor/autoload.php';
$config = require 'config.php';

$client = new Client([
    'base_uri' => 'https://api.github.com/'
]);
$options = [
    'headers' => [
        'User-Agent' => $config['user_agent']
    ],
    'query' => [
        'access_token' => $config['access_token'],
        'per_page' => 100
    ]
];
$packages = [];

$repositoriesResponse = $client->get("/orgs/{$config['organization']}/repos", $options);
$repositories = json_decode($repositoriesResponse->getBody());
foreach ($repositories as $repository) {
    try {
        $composerRequest = "/repos/{$config['organization']}/{$repository->name}/contents/composer.json";
        $composerResponse = $client->get($composerRequest, $options);
        $contents = json_decode($composerResponse->getBody());
        $composer = json_decode(base64_decode($contents->content));
        if (!$composer || !isset($composer->require)) {
            continue;
        }
        foreach ($composer->require as $package => $version) {
            if ($package == 'php') {
                continue;
            }
            if (isset($packages[$package])) {
                $packages[$package]++;
            } else {
                $packages[$package] = 1;
            }
        }
    } catch (ClientException $e) {
        // No composer.json found
    }
}
arsort($packages);
print_r($packages);
