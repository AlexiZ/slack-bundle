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

#### SOIT installation depuis le dépôt public
Installer le bundle avec composer :
> $ composer require alexiz/slack-bundle "dev-master"


#### SOIT installation depuis un dépôt privé
 - Télécharger le bundle depuis Github
 - Ajouter ce bundle dans un nouveau dépôt privé
 - Déclarer ce nouveau dépôt dans le composer.json :
 ```JSON
// composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@gihub.com/XXX/slack-bundle.git"
        }
    ],
    "require": [
        "XXX/slack-bundle": "dev-master",
    ],
}
 ```
  - puis lancer `composer install`


#### Paramétrage

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


### Utilisation

#### Dans un contrôleur

Dans le cas où les paramètres Slack sont stockés en base de données, commencer par créer la méthode du Repository qui permet de récupérer ces éléments (code exemple à adapter à la structure de base de données) :

```PHP
<?php // src/AppBundle/Repository/ParameterRepository.php

namespace SiteBundle\Repository;

use Doctrine\ORM\AbstractQuery;

class ParameterRepository
{
    public function getSlackParameters() {
        return $this
            ->createQueryBuilder('p')
            ->select('partial p.{id, key, value}')
            ->where('p.key IN :slackParameters')
            ->setParameter('slackParameters', [
                'slack.token',
                'slack.channel',
                'slack.user',
            ])
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
    }
}
```

Puis instancier le service dans le controlleur en se servant de ces informations :

```PHP
<?php // src/AppBundle/Controller/DefaultController.php

// Déclarer une nouvelle instance du service
$slack = $this->get('slack.client');

// Récupérer les paramètres Slack
$slackParameters = $this->getDoctrine()->getRepository(Parameter::class)->getSlackParameters();

// Créer le message à envoyer
$message = 'Contenu du message';

// Appeler l'envoi de message du service
$slack->sendMessage(
    [
        'token' => $slackParameters[0],
        'channel' => $slackParameters[1],
        'user' => $slackParameters[2],
    ], 
    $message
);
```


#### Dans un service

```PHP
<?php  // src/AcmeBundle/Service/AcmeService.php

namespace AcmeBundle\DependencyInjection;

use Slack\ApiBundle\DependencyInjection\Manager;
use Doctrine\Common\Persistence\ManagerRegistry;

class AcmeService
{
    /**
     * @var Manager
     */
    protected $slackClient;
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(Manager $slackClient, ManagerRegistry $registry) {
        $this->slackClient = $slackClient;
        $this->registry = $registry;
    }

    private function test() {
        // Récupérer les paramètres en base de données
        $slackParameters = $this->registry->getRepository(Parameter::class)->getSlackParameters();

        // Créer le message à envoyer
        $message = 'Contenu du message';

        // Envoyer le message via le service
        $this->slackClient->sendMessage(
            [
                'token' => $slackParameters[0],
                'channel' => $slackParameters[1],
                'user' => $slackParameters[2],
            ], 
            $message
        );
    }
}
```

_Note : si l'auto-wiring n'est pas activé, il est nécessaire de rajouter `$slackClient` à la liste des arguments du service._