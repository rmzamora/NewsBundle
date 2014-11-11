<?php

/*
 * This file is part of the RzNewsBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\NewsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

use Sonata\EasyExtendsBundle\Mapper\DoctrineCollector;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class RzNewsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $bundles = $container->getParameter('kernel.bundles');

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('admin_orm.xml');
        $loader->load('twig.xml');
        $loader->load('block.xml');

        $config = $this->addDefaults($config);
        $this->configureAdminClass($config, $container);
        $this->configureClass($config, $container);
        $this->configureClassManager($config, $container);

        $this->configureTranslationDomain($config, $container);
        $this->configureController($config, $container);
        $this->configureRzTemplates($config, $container);
        $this->registerService($config, $container);
        $this->configureBlocks($config, $container);

        $this->configureSettings($config, $container);

        if (isset($bundles['IvoryLuceneSearchBundle'])) {
            $loader->load('lucene.xml');
        }
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function addDefaults(array $config)
    {
        if ('orm' === $config['manager_type']) {
            $modelType = 'Entity';
        } elseif ('mongodb' === $config['manager_type']) {
            $modelType = 'Document';
        }

        $defaultConfig['class']['post']  = sprintf('Application\\Sonata\\NewsBundle\\%s\\Post', $modelType);
        $defaultConfig['class']['comment'] = sprintf('Application\\Sonata\\NewsBundle\\%s\\Comment', $modelType);

        return array_replace_recursive($defaultConfig, $config);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureClass($config, ContainerBuilder $container)
    {
        if ('orm' === $config['manager_type']) {
            $modelType = 'entity';
        } elseif ('mongodb' === $config['manager_type']) {
            $modelType = 'document';
        }

        $container->setParameter(sprintf('sonata.news.admin.post.%s', $modelType), $config['class']['post']);
        $container->setParameter(sprintf('sonata.news.admin.comment.%s', $modelType), $config['class']['comment']);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureClassManager($config, ContainerBuilder $container)
    {
        // manager configuration
        $container->setParameter('sonata.news.manager.post.class',     $config['class_manager']['post']);
        $container->setParameter('sonata.news.manager.comment.class',  $config['class_manager']['comment']);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureAdminClass($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.news.admin.post.class', $config['admin']['post']['class']);
        $container->setParameter('sonata.news.admin.comment.class', $config['admin']['comment']['class']);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureTranslationDomain($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.news.admin.post.translation_domain', $config['admin']['post']['translation']);
        $container->setParameter('sonata.news.admin.comment.translation_domain', $config['admin']['comment']['translation']);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureController($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.news.admin.post.controller', $config['admin']['post']['controller']);
        $container->setParameter('sonata.news.admin.comment.controller', $config['admin']['comment']['controller']);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureRzTemplates($config, ContainerBuilder $container)
    {
        $container->setParameter('rz_news.configuration.post.templates', $config['admin']['post']['templates']);
        $container->setParameter('rz_news.configuration.comment.templates', $config['admin']['comment']['templates']);
    }

    protected function registerService(array $config, ContainerBuilder $container)
    {
        $container->setParameter('twig.form.resources',
                                 array_merge($container->getParameter('twig.form.resources'),
                                             array('RzNewsBundle::form.html.twig')
                                 )
        );
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureBlocks($config, ContainerBuilder $container)
    {
        $container->setParameter('rz_news.block.recent_posts', $config['blocks']['class']['recent_posts']);
        $container->setParameter('rz_news.block.recent_comments', $config['blocks']['class']['recent_comments']);
    }

    public function configureIndex($config, ContainerBuilder $container)
    {
        $container->setParameter('rz_news.block.recent_posts', $config['blocks']['class']['recent_posts']);
        $container->setParameter('rz_news.block.recent_comments', $config['blocks']['class']['recent_comments']);
    }

    public function configureSettings($config, ContainerBuilder $container)
    {
        $container->setParameter('rz_news.settings.news_pager_max_per_page', $config['settings']['news_pager_max_per_page']);
    }
}
