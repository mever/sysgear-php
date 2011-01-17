<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Service
{
    /**
     * @var $container \Symfony\Components\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * 
     * @param \Symfony\Components\DependencyInjection\ContainerInterface $container A ContainerInterface instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->_init();
    }

    protected function _init() {}

    public function getDatabaseConnection($name = null)
    {
        if ($name) {
            return $this->container->get(sprintf('doctrine.dbal.%s_connection', $name));
        } else {
            return $this->container->get('database.connection');
        }
    }

    /**
     * Get the default entity manager service or the entity manager
     * with the given name.
     *
     * @param string $name Optional entity manager service name
     *
     * @return object
     */
    protected function getEntityManager($name = null)
    {
        if ($name) {
            return $this->container->get(sprintf('doctrine.orm.%s_entity_manager', $name));
        } else {
            return $this->container->get('doctrine.orm.entity_manager');
        }
    }

    /**
     * Create a new QueryBuilder instance.
     *
     * @param string $name Optional entity manager service name
     * @return object QueryBuilder
     */
    public function createQueryBuilder($name = null)
    {
        return $this->getEntityManager($name)->createQueryBuilder();
    }

    /**
     * Create a new Query instance.
     *
     * @param string $dql  Optional Dql string to create the query from
     * @param string $name Optional entity manager service name
     *
     * @return object QueryBuilder
     */
    public function createQuery($dql = '', $name = null)
    {
        return $this->getEntityManager($name)->createQuery($dql);
    }
}