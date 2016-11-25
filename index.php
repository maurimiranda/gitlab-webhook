<?php
// Get config
$config = json_decode(file_get_contents('/etc/webhook/config.json'), true);

// Check token
if (!array_key_exists('HTTP_X_GITLAB_TOKEN', $_SERVER) ||
    $_SERVER['HTTP_X_GITLAB_TOKEN'] !== $config['token']) {
    exit('Invalid token');
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Get project and branch
$project = $data['project']['name'];
$branch = substr($data['ref'], strrpos($data['ref'], '/') + 1);
if (!array_key_exists($branch, $config)) {
    exit('Invalid branch');
}
// Get target, commands and emails from config
$target = $config[$project][$branch]['target'];
$commands = $config[$project][$branch]['commands'];
$emails = $config[$project][$branch]['emails'];

// Create log
$log = array();
$fs = fopen($config['log'], 'a');
$log['date'] = date('c');
$log['branch'] = $branch;
$log['commit'] = $data['after'];
$log['user'] = $data['user_name'];

// Add full request data to log
//$log['data'] = $data;

// Add full config to log
//$log['config'] = config;

// Execute commands
$command = 'cd '.$target.' && '.$config['git'].' checkout '.$branch.' 2>&1 && '.$config['git'].' pull 2>&1';
if (count($commands) > 0) {
  $command = $command . ' && ' .join(' 2>&1 && ', $commands);
}
$log['command'] = $command;
$log['result'] = explode(PHP_EOL, shell_exec($command));
if (end($log['result']) === '') {
    array_pop($log['result']);
}

// Write log
if ($fs) {
    fwrite($fs, print_r($log, true).PHP_EOL);
    $fs and fclose($fs);
}

// Send logs by emails
if (count($emails) > 0) {
  mail(join(',', $emails), 'Webhook Log', print_r($log, true).PHP_EOL);
}

?>
