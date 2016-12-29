<?php

// Create log
$log = array();
$log['date'] = date('c');

// Get config
$config = json_decode(file_get_contents('./config.json'), true);
//$log['config'] = $config;

// Check token
if (!array_key_exists('HTTP_X_GITLAB_TOKEN', $_SERVER) ||
    $_SERVER['HTTP_X_GITLAB_TOKEN'] !== getenv('GITLAB_TOKEN')) {
    $log['token'] = $_SERVER['HTTP_X_GITLAB_TOKEN'];
    $log['error'] = 'Invalid token';
    error_log(print_r($log, true).PHP_EOL);
    exit();
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
//$log['data'] = $data;

// Get project
$project = $data['project']['name'];
$log['project'] = $project;
if (!array_key_exists($project, $config)) {
    $log['error'] = 'Invalid project';
    error_log(print_r($log, true).PHP_EOL);
    exit();
}

// Get branch
$branch = substr($data['ref'], strrpos($data['ref'], '/') + 1);
$log['branch'] = $branch;
if (!array_key_exists($branch, $config[$project])) {
    $log['error'] = 'Invalid branch';
    error_log(print_r($log, true).PHP_EOL);
    exit();
}
$log['commit'] = $data['after'];
$log['user'] = $data['user_name'];
$log['message'] = end($data['commits'])['message'];

// Get command and emails from config
$command = $config[$project][$branch]['command'];
$emails = $config[$project][$branch]['emails'];

// Execute commands
$log['result'] = explode(PHP_EOL, shell_exec($command . ' 2>&1'));
if (end($log['result']) === '') {
    array_pop($log['result']);
}

// Write log
$fs = fopen('/var/log/webhook/' . $project . '.log', 'a');
if ($fs) {
    fwrite($fs, print_r($log, true).PHP_EOL);
    $fs and fclose($fs);
}

// Send logs by emails
if (count($emails) > 0) {
  mail(join(',', $emails), 'Webhook Log - ' . $project . ' (' . $branch . ')' , print_r($log, true).PHP_EOL);
}

?>
