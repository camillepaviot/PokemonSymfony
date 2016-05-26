<?php

/**************************************************************************
 * RestSyncGlobalController.php, pokemon Android
 *
 * Copyright 2016
 * Description : 
 * Author(s)   : Harmony
 * Licence     : 
 * Last update : May 26, 2016
 *
 **************************************************************************/

namespace Pokemon\ApiBundle\Controller;

use Pokemon\ApiBundle\Controller\RestSyncController as Controller;
use Pokemon\ApiBundle\Entity\EntityBase;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sync Global controller.
 *
 */
class RestSyncGlobalController extends Controller {

    /**=========================================================================
     * CRUD REST
     *========================================================================*/

    /**
     * GET
     *
     * @Rest\Get
     * @ApiDoc(
     *   resource=true,
     *   section="Rest CRUD Sync",
     *   description="Get server sync time",
     * )
     * @return type
     */
    public function getAction()
    {
        $utimestamp = microtime(true);
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);

        $data = ['syncdate' => date(preg_replace('`(?<!\\\\)u`',
                        $milliseconds, "Y-m-d\TH:i:s.uO"), $timestamp)];

        $view = $this->view(
                $data,
                200);

        return $this->handleView($view);
    }

    /**
     * POST
     * @Rest\POST("/generatehash")
     * @ApiDoc(
     * resource=true,
     * section="Rest CRUD Sync",
     * description="Insert missing hash for sync entities",
     * )
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @return type
     */
    public function generateHashAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $this->generateHashForEntity($entityManager, 'PokemonApiBundle:TypeDePokemons');
        $this->generateHashForEntity($entityManager, 'PokemonApiBundle:Pokemons');

        $view = $this->view(null, 200);

        return $this->handleView($view);
    }

    private function generateHashForEntity($entityManager, $repositoryName) {
        $entities = $entityManager->getRepository($repositoryName)->findBy(array('hash' => null));

        if ($entities) {
            foreach ($entities as $entity) {
                $entity->setHash(EntityBase::generate_uuid());
                $entityManager->persist($entity);
            }

            $entityManager->flush();
        }
    }

    public function getNewEntity() {
        return null;
    }
}

?>
