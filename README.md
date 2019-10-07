# Symfony Slack bundle

## Push de données

### Principe de fonctionnement

L'application qui va être créée ici a besoin de trois paramètres pour fonctionner :
  - un token d'accès
  - un channel dans lequel écrire
  - un utilisateur au nom duquel écrire
 
Pour récupérer deux de ces données, l'application doit d'abord être autorisée à accéder à l'instance Slack cible. Pour ce faire, on utilisera un [Slack Button](https://api.slack.com/docs/slack-button), qui ne sera utile qu'une seule fois : pour récupérer un token d'accès ainsi qu'un channel de destination.

Ces paramètres doivent être enregistrés en base de données pour pouvoir être utilisés ensuite par l'application de manière transparente et permanente (ou jusqu'à révocation de l'autorisation par un administrateur de l'instance Slack).

On pourra ensuite simplement requêter la base pour ne pas proposer ce bouton si les paramètres sont déjà enregistrés.

Le troisième paramètre, l'utilisateur émetteur, devra être géré séparément, par exemple dans un champ de formulaire dédié. Dans l'idéal il devra être enregistré en base au même titre que les deux autres paramètres pour en faciliter la récupération à chaque appel.

### Paramétrage de Slack

 - Aller sur la [liste des applications](https://api.slack.com/apps)
 - Cliquer sur "Create new app"
 - Renseigner le nom (ex : "MonApplicationSlack") - ce nom sera affiché à toute personne qui installe l'application
 - Sélectionner le bon espace de travail dans la liste déroulante
 - Cliquer sur "Permissions" sous "Add features and functionality"
 - Dans l'encadré "OAuth Tokens & Redirect URLs", cliquer sur "Add new redirect url". Il faut inscrire ici une url par client de la forme "https://www.XXX.com/slack-api-bundle/oauth" (la seconde partie de l'url est gérée par ce bundle et permet de traiter l'authentification OAuth 2 requise par Slack). Il est nécessaire de renseigner ici TOUTES les urls des sites sur lesquels seront mis en place les boutons Slack qui permettent d'associer l'application.
 - Sauvegarder l'étape
 - Dans l'encadré "Scopes", ajouter les 3 permissions suivantes :
    - "Send messages as user" (_chat:write:user_)
    - "Post to specific channel in Slack" (_incoming-webhook_)
    - "Access your workspace's profile information" (_users:read_)
 - Sauvegarder l'étape
 - À gauche cliquer sur "Manage Distribution"
 - Dans l'encadré "Share your app with other teams", cliquer sur "Activate public distribution" (valider les sous-étapes si nécessaire)

Il ne reste plus qu'à cliquer à gauche sur "Basic Information" pour récupérer le "Client Id" et le "Client Secret". Ils sont nécessaires à la configuration du Bundle. 
 
 L'application créée est désormais installable par tous ceux qui cliqueront sur le bouton qu'on insérera plus tard dans une page.


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
            "url": "https://github.com/XXX/slack-bundle.git"
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

Ajouter les paramètres identifiant l'application créée plus haut :

```YAML
# app/config/parameters.yml
# app/config/parameters.yml.dist
parameters:
    slack.api.client_id: '123456789.123456789'
    slack.api.client_secret: ldskf1298flskdjf74897
```


### Utilisation

Comme expliqué plus haut, l'implémentation se fait en deux temps :
  - insérer un bouton sur une page pour ajouter l'application à l'instance Slack cible
  - envoyer les messages voulus sur ledit Slack

#### Bouton d'ajout Slack

On commence par ajouter le bouton sur une page :

```PHP
<?php // src/AppBundle/Controller/DefaultController.php

    /**
     * Contrôleur de la page sur laquelle sera affiché le bouton d'ajout à Slack
     */
    public function indexAction() {
        // Les paramètres nécessaires sont-ils déjà enregistrés ?
        // Exemple de méthode qui renvoie un booléen pour savoir si les valeurs des 3 paramètres sont nulles ou non
        // La classe SimpleParameter utilisée ici est un exemple (voir docs/Entity/SimpleParameter.php pour l'implémentation)
        // Le repository associé et sa méthode d'exmple de récupération de donnée est également donné en exemple dans docs/Repository
        $slackParameters = $this->getDoctrine()->getRepository(SimpleParameter::class)->findLatest();

        // Envoyer à la vue l'information pour savoir s'il faut afficher ou non le bouton d'ajout à Slack
        return $this->render('AppBundle:Default:index.html.twig', [
            'displayButton' => $slackParameters ? true : false,
        ]);
    }
```

Dans la vue associée à ce contrôleur, il faut contrôler l'état de cette nouvelle variable :

```Twig
{# src/appBundle/Resources/views/Defaut/index.html.twig #}

{% if not displayButton|default %}
    <a href="https://slack.com/oauth/authorize?client_id={{ slack_api_client_id }}&scope=chat:write:user,incoming-webhook,users:read&redirect_uri={{ app.request.getSchemeAndHttpHost() }}/slack-api-bundle/oauth&state=oauthslackstate">
        <img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x">
    </a>
{% endif %}
```

On utilise ici un paramètre twig global `slack_api_client_id` qui reprend l'un des paramètres ajoutés plus tôt et qu'il est nécessaire de définir dans le config.yml :

```YAML
# app/config/config.yml
twig:
    globals:
        slack_api_client_id: '%slack.api.client_id%'
```

#### Envoyer un message depuis un contrôleur

```PHP
<?php // src/AppBundle/Controller/DefaultController.php

    /**
     * Contrôleur qui envoie un message sur Slack
     */
    public function sendMessageAction() {
        // Vérifier à chaque envoi que les paramètres sont bien enregistrés
        // La classe SimpleParameter utilisée ici est un exemple (voir docs/Entity/SimpleParameter.php pour l'implémentation)
        // Le repository associé et sa méthode d'exmple de récupération de donnée est également donné en exemple dans docs/Repository
        $slackParameters = $this->getDoctrine()->getRepository(SimpleParameter::class)->findLatest();

        // S'assurer que les paramètres sont bien enregistrés
        if (!$slackParameters) {
            throw new \Exception();
        }

        // Appeler l'envoi de message du service avec les paramètres récupérés
        $messageSent = $this->get('slack.client')->sendMessage('Hello world', $slackParameters);

        if (!$messageSent) {
            throw new \Exception();
        }

        return new Response('ok');
    }
```


#### Envoyer un message depuis un service

```PHP
<?php  // src/AppBundle/Service/AcmeService.php

namespace AppBundle\DependencyInjection;

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

    public function test() {
        // Récupérer les paramètres en base de données
        $slackParameters = $this->registry->getRepository(SimpleParameter::class)->findLatest();

        // S'assurer que les paramètres sont bien enregistrés
        if (!$slackParameters) {
            return false;
        }

        // Envoyer le message via le service
        return $this->slackClient->sendMessage('Hello world', $slackParameters);
    }
}
```

_Note : si l'auto-wiring n'est pas activé, il est nécessaire de rajouter `$slackClient` à la liste des arguments du service._


### Informations complémentaires

Durant la vie de l'application, il sera nécessaire de tenir à jour la liste des _redirect urls_ :
  - ajouter l'url d'un nouveau client
  - supprimer l'url des clients qui n'utlisent plus l'application

Il suffira alors simplement de se rendre sur [la page de listing des application Slack](https://api.slack.com/apps) (l'instance Slack où a été créée l'application), puis d'accéder à la page "Oauth & Permissions" du menu gauche pour éditer les urls autorisés à utiliser l'application dans la section "Redirect Urls".

La structure de donnée pour l'entité `SimpleParameter` donné en exemple plus haut est de la forme :

    > select * from simple_parameter;
    +----+---------+---------------------------------+
    | id | name    | value                           |
    +----+---------+---------------------------------+
    |  1 | token   | xoxp-...                        |
    |  2 | channel | DNL...                          |
    |  3 | user    | UHBE...                         |
    +----+---------+---------------------------------+
