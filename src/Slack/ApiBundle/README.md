```YAML
# app/config/parameters.yml
parameters:
    slack.client.token: xoxp-595498590262-589504932274-600935093488-82a2ce59da9b876fe914def02153e92c
    slack.client.user: DHSNVKCGN
    slack.client.channel: CHDJWUBRN
```

```YAML
# app/config/services.yml
services:
    Slack\ApiBundle\DependencyInjection\Manager:
        public: false
        arguments:
            $apiToken: "%slack.client.token%"
            $user: "%slack.client.user%"
            $channel: "%slack.client.channel%"

    slack.client:
        public: true
        alias: Slack\ApiBundle\DependencyInjection\Manager

```

# Symfony Slack bundle

## Push de données

### Paramétrage de Slack



### Utilisation

Dans un contrôleur :
```PHP
$slack = $this->get('slack.client');

$slack->send($message);
```

Dans un service :
```PHP
<?php 
// src/AcmeBundle/Service/AcmeService.php
namespace AcmeBundle\DependencyInjection;

use AlexiZ\SlackBundle\DependencyInjection\SlackClient;

class AcmeService
{
    protected $slackClient;

    public function __construct(SlackClient $slackClient) {
        $this->slackClient = $slackClient;
    }
    
    private function acme() {
        $message = 'Contenu du <strong>message</strong>';

        $this->slackClient->send($message);
    }
}
```

Si l'auto-wiring n'est pas activé, il faut déclarer le nouvel argument du service :
```YAML
# app/config/services.yml
services:
    AcmeBundle\Service:
        arguments:
            $slackClient: "@slack.client"
```