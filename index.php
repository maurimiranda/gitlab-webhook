<?php

// Create log
$log = array();
$log['date'] = date('c');

// Get config
$config = json_decode(file_get_contents('/etc/webhook/config.json'), true);
//$log['config'] = config;

// Check token
if (!array_key_exists('HTTP_X_GITLAB_TOKEN', $_SERVER) ||
    $_SERVER['HTTP_X_GITLAB_TOKEN'] !== $config['token']) {
    $log['error'] = 'Invalid token';
    error_log(print_r($log, true));
    exit();
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
//$log['data'] = $data;

// Get project
$project = $data['project']['name'];
$log['project'] = $project;
if (!array_key_exists($project, $config['projects'])) {
    $log['error'] = 'Invalid project';
    error_log(print_r($log, true));
    exit();
}

// Get branch
$branch = substr($data['ref'], strrpos($data['ref'], '/') + 1);
$log['branch'] = $branch;
if (!array_key_exists($branch, $config['projects'][$project])) {
    $log['error'] = 'Invalid branch';
    error_log(print_r($log, true));
    exit();
}
$log['commit'] = $data['after'];
$log['user'] = $data['user_name'];
$log['message'] = end($data['commits'])['message'];

// Get target, commands and emails from config
$target = $config['projects'][$project][$branch]['target'];
$commands = $config['projects'][$project][$branch]['commands'];
$emails = $config['projects'][$project][$branch]['emails'];

// Execute commands
$command = 'cd '.$target.' && '.$config['git'].' checkout '.$branch.' 2>&1 && '.$config['git'].' pull 2>&1';
if (count($commands) > 0) {
  $command = $command . ' && ' . join(' 2>&1 && ', $commands) . ' 2>&1';
}
$log['command'] = $command;
$log['result'] = explode(PHP_EOL, shell_exec($command));
if (end($log['result']) === '') {
    array_pop($log['result']);
}

// Write log
$fs = fopen($config['log'], 'a');
if ($fs) {
    fwrite($fs, print_r($log, true).PHP_EOL);
    $fs and fclose($fs);
}

// Send logs by emails
if (count($emails) > 0) {
  mail(join(',', $emails), 'Webhook Log', print_r($log, true).PHP_EOL);
}

?>
