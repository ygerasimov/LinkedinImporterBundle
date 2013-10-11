<?php

namespace CCC\LinkedinImporterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ccc_linkedin_importer');

        $rootNode
            ->children()
                ->scalarNode('company')->isRequired()->end()
                ->scalarNode('app_name')->isRequired()->end()
                ->scalarNode('api_key')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('secret_key')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('oauth_user_token')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->always(function ($value) {
                            if (strlen($value) != 36) throw new InvalidTypeException('Invalid OAuth Token');
                            return $value;
                        })
                    ->end()
                ->end()
                ->scalarNode('oauth_user_secret')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->always(function ($value) {
                            if (strlen($value) != 36) throw new InvalidTypeException('Invalid OAuth Secret');
                            return $value;
                        })
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
