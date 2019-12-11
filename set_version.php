<?php
namespace Deployer;

desc('Set Version for in Files');

task('rockschtar:set_version', static function() {

    $parseOptions = static function(array $args, array $defaults = []) {
        $parsed_args =& $args;
        if (is_array($defaults)) {
            return array_merge($defaults, $parsed_args);
        }
        return $parsed_args;
    };

    $options = get('version');

    if (!is_array($options)) {
        $options = [];
    }

    $defaultOptions = [
        'default' => 'tag',
        'sed' => 's/Version:.*$/Version: ',
        'files' => []];

    $options = $parseOptions($options, $defaultOptions);
    $files = [];

    $sedPattern = empty($options['sed']) ? 's/Version:.*$/Version: ' : $options['sed'];

    foreach($options['files'] as $file) {
        if(!is_array($file)) {
            $files[] = ['sed' => $sedPattern, $file];
        } else {
            $files[] = $parseOptions($file, ['sed' => $sedPattern, 'file' => '']);
        }
    }

    $git = get('bin/git');
    $currentRevision = run("cd {{release_path}} && $git rev-parse HEAD");
    $currentTag = run("cd {{release_path}} &&  $git tag -l --points-at HEAD");
    $currentBranch = run("cd {{release_path}} &&  $git rev-parse --abbrev-ref HEAD");

    $version = $currentRevision;

    if($defaultOptions['default'] === 'tag' && !empty($currentTag)) {
        $version = $currentTag;
    }

    if($defaultOptions['default'] === ' branch' && !empty($currentBranch)) {
        $version = $currentBranch;
    }

    $updateVersion = static function ($version, $file) use ($sedPattern) {
        $sed_version = str_replace('/', '\/', $version);
        $pathToFile = parse('{{release_path}}' . DIRECTORY_SEPARATOR.  $file);
        echo parse('➤ Update version ' . $version . ' in file ' .   $pathToFile . "\n");
        run('sed -i "' . $sedPattern . $sed_version . '/" ' .  $pathToFile);
    };

    foreach ($files as $file) {
        $updateVersion($version, $file['file']);
    }
});