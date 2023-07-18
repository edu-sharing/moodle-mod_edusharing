<?php

$mapping = [
    'EduSharingApiClient\AppAuthException'           => __DIR__ . '/apiClient/src/EduSharing/AppAuthException.php',
    'EduSharingApiClient\CurlHandler'                => __DIR__ . '/apiClient/src/EduSharing/CurlHandler.php',
    'EduSharingApiClient\CurlResult'                 => __DIR__ . '/apiClient/src/EduSharing/CurlResult.php',
    'EduSharingApiClient\DefaultCurlHandler'         => __DIR__ . '/apiClient/src/EduSharing/DefaultCurlHandler.php',
    'EduSharingApiClient\DisplayMode'                => __DIR__ . '/apiClient/src/EduSharing/DisplayMode.php',
    'EduSharingApiClient\EduSharingAuthHelper'       => __DIR__ . '/apiClient/src/EduSharing/EduSharingAuthHelper.php',
    'EduSharingApiClient\EduSharingHelper'           => __DIR__ . '/apiClient/src/EduSharing/EduSharingHelper.php',
    'EduSharingApiClient\EduSharingHelperAbstract'   => __DIR__ . '/apiClient/src/EduSharing/EduSharingHelperAbstract.php',
    'EduSharingApiClient\EduSharingHelperBase'       => __DIR__ . '/apiClient/src/EduSharing/EduSharingHelperBase.php',
    'EduSharingApiClient\EduSharingNodeHelper'       => __DIR__ . '/apiClient/src/EduSharing/EduSharingNodeHelper.php',
    'EduSharingApiClient\EduSharingNodeHelperConfig' => __DIR__ . '/apiClient/src/EduSharing/EduSharingNodeHelperConfig.php',
    'EduSharingApiClient\NodeDeletedException'       => __DIR__ . '/apiClient/src/EduSharing/NodeDeletedException.php',
    'EduSharingApiClient\UrlHandling'                => __DIR__ . '/apiClient/src/EduSharing/UrlHandling.php',
    'EduSharingApiClient\Usage'                      => __DIR__ . '/apiClient/src/EduSharing/Usage.php',
    'EduSharingApiClient\UsageDeletedException'      => __DIR__ . '/apiClient/src/EduSharing/UsageDeletedException.php',
];

spl_autoload_register(function ($class) use ($mapping) {
    isset($mapping[$class]) && require_once $mapping[$class];
});