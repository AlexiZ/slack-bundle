# Symfony Slack bundle

## Push de données

### Paramétrage de Slack

 - Aller sur la [liste des applications](https://api.slack.com/apps)
 - CLiquer sur "Create new app"
 - Renseigner le nom (ex : "TrackAd"). Ce nom n'a aucun incidence pour la suite, il permet simplement de s'y retrouver dans la liste des applications disponibles
 - Sélectionner le bon espace de travail dans la liste déroulante
 - Cliquer sur "Permissions" sous "Add features and functionality"
 - Ajouter les 3 permissions suivantes :
    - "Send messages as user" (_chat:write:user_)
    - "Post to specific channel in Slack" (_incoming-webhook_)
    - "Access your workspace's profile information" (_users:read_)
 - Cliquer sur "Save changes"
 - En haut de cette page, cliquer sur "Install App to Workspace"
 - sur la fenêtre qui s'ouvre, il est demandé de renseigner un channel ou une conversation qui ne servira qu'à informer les utilisateurs de l'instance Slack dudit channel ou conversation de l'installation d'une nouvelle application sur l'espace de travail.
 - Récupérer le token "OAuth Access Token" qui est affiché à l'écran 

### Installation et paramétrage du bundle

Installer le bundle avec composer :
> $ composer require alexiz/slack-bundle "dev-master"

Déclarer le bundle :
```PHP
<?php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Slack\ApiBundle\SlackApiBundle(),
        ];
        // ...
    }
    //...
}
```

Ajouter les paramètres pour Slack :
```YAML
# app/config/parameters.yml
parameters:
    slack.client.token: xoxp-111111111111-222222222222-333333333333-82a2ce59da9b876fe914def02153e92c
    slack.client.user: UHBEUTE82
    slack.client.channel: CHDJWUBRN
```


### Utilisation

Dans un contrôleur :

```PHP
$slack = $this->get('slack.client');

$slack->sendMessage($message);
```

Dans un service :

```PHP
<?php 
// src/AcmeBundle/Service/AcmeService.php
namespace AcmeBundle\DependencyInjection;

use Slack\ApiBundle\DependencyInjection\Manager;

class AcmeService
{
    protected $slackClient;

    public function __construct(Manager $slackClient) {
        $this->slackClient = $slackClient;
    }
    
    private function acme() {
        $message = 'Contenu du message';

        $this->slackClient->sendMessage($message);
    }
}
```
