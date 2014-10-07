<?php

namespace Undelete\DoctrineMultipleOwnerBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

class OwnerFinder
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function findOwner($entity)
    {
        $all = $this->em->getMetadataFactory()->getAllMetadata();

        foreach ($all as $metadata) {
            /* @var $metadata ClassMetadata */
            $associations = $metadata->getAssociationMappings();

            foreach ($associations as $association) {
                if (
                    ($association['targetEntity'] == get_class($entity))
                    &&
                    in_array($association['type'], [ClassMetadata::ONE_TO_ONE, ClassMetadata::MANY_TO_MANY])
                    &&
                    !$association['inversedBy']
                ) {
                    if ($association['type'] == ClassMetadata::ONE_TO_ONE) {
                        $owner = $this->em->getRepository($association['sourceEntity'])->findOneBy([
                            $association['fieldName'] => $entity,
                        ]);

                        if ($owner) {
                            return $owner;
                        }
                    } elseif ($association['type'] == ClassMetadata::MANY_TO_MANY) {
                        $result = $this
                            ->em
                            ->createQueryBuilder()
                            ->from($association['sourceEntity'], 'e')
                            ->select('e')
                            ->innerJoin('e.' . $association['fieldName'], 'c', 'WITH', 'c = :entity')
                            ->setParameter('entity', $entity)
                            ->getQuery()
                            ->getOneOrNullResult()
                        ;

                        if ($result) {
                            return $result;
                        }
                    }
                }
            }
        }
    }
}
