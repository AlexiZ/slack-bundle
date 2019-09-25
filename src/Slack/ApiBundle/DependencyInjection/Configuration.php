<?php

declare(strict_types=1);

namespace Slack\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('slack_api');
        }

        $rootNode
            ->children()
                ->arrayNode('http')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('client')->defaultValue('httplug.client')->end()
                    ->end()
                ->end()
                ->scalarNode('endpoint')
                    ->isRequired()->cannotBeEmpty()
                    ->info('The Slack API Incoming WebHooks URL.')
                ->end()
                ->scalarNode('channel')->end()
                ->booleanNode('sticky_channel')->defaultFalse()->end()
                ->scalarNode('username')->end()
                ->scalarNode('icon')->end()
                ->booleanNode('link_names')->end()
                ->booleanNode('unfurl_links')->end()
                ->booleanNode('unfurl_media')->end()
                ->booleanNode('allow_markdown')->end()
                ->arrayNode('markdown_in_attachments')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}