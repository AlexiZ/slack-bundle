<?php

namespace Slack\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Slack\ApiBundle\DependencyInjection\Manager;
use Slack\ApiBundle\Entity\SimpleParameter;

/**
 * SimpleParameterRepository
 */
class SimpleParameterRepository extends EntityRepository
{
    public function findLatest()
    {
        $rawResults = $this
            ->createQueryBuilder('sp')
            ->select('partial sp.{id, name, value}')
            ->where('sp.name IN (:slackParameters)')
            ->setParameter('slackParameters', Manager::$nameValues)
            ->getQuery()
            ->getArrayResult()
        ;

        $results = [];
        foreach ($rawResults as $result) {
            $results[$result['name']] = $result['value'];
        }

        return $results;
    }

    public function saveParameters(array $parameters)
    {
        foreach ($parameters as $name => $value) {
            $this->_em->persist(new SimpleParameter($name, $value));
            $this->_em->flush();
        }
    }
}
