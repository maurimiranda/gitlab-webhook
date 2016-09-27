# gitlab-webhook
GitLab Webhook receiver written in PHP

### How to use it
1. Clone repo or download index.php
1. Create a vhost or copy the file to some browseable location
1. Create your config file based on example
    ```
    {
      "token": "10b3df25867af8bbfb182f76728592cc0b1956d6",
      "git": "/usr/bin/git",
      "log": "/var/log/apache/webhook.log",
      "develop": {
        "target": "/var/www/develop"
      },
      "master": {
        "target": "/var/www/production"
      }
    }
    ```
    *Note:* the script look for the file at `/etc/webhook/config.json`, you can change that location at the first line of the php file
1. Configure GitLab Webhook
1. Enjoy!
