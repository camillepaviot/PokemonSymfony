<?php

/**************************************************************************
 * SyncControllerBase.php, pokemon Android
 *
 * Copyright 2016
 * Description : 
 * Author(s)   : Harmony
 * Licence     : 
 * Last update : May 26, 2016
 *
 **************************************************************************/

namespace Pokemon\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Sync controller.
 *
 */
class SyncControllerBase extends Controller {

    protected $entitiesm;
    protected $entitiesi;
    protected $entitiesu;
    protected $entitiesd;
    protected $logger;
    protected $client;
    protected $restController;

    /**
     * Constructor
     */
    public function __construct(RestSyncController $restController) {
        $this->restController = $restController;
    }

    public function syncAction($syncLogger, $syncClient, $repository, $formEntity, $request, $em, $type)
    {
        //Init
        $this->logger = $syncLogger;
        $this->client = $syncClient;

        // Call
        $this->entitiesm = array();
        $this->entitiesi = array();
        $this->entitiesu = array();
        $this->entitiesd = array();

        $lastSyncDate = new \DateTime($request->request->get('lastSyncDate'));
        $startSyncDate = new \DateTime($request->request->get('startSyncDate'));

        $this->logger->debug('Start Sync' . $this->client . ' @:' . $startSyncDate
                ->format('H:i:s.u') . ' last:' . $lastSyncDate
                ->format('H:i:s.u')); // \DateTime::ISO860

        $this->delete($em, $startSyncDate, $repository, $type, $request, $formEntity);
        $this->insert($em, $startSyncDate, $repository, $type, $request, $formEntity);
        $this->update($em, $startSyncDate, $repository, $type, $request, $formEntity);

        $this->merge($lastSyncDate, $repository);

        // Final
        $em->flush();

        $view = array(
            $type . '-m' => $this->entitiesm,
            $type . '-i' => $this->entitiesi,
            $type . '-u' => $this->entitiesu,
            $type . '-d' => $this->entitiesd
        );

        return $view;
    }

    /**
     * GET /merge
     */
    protected function merge($lastSyncDate, $repository) {

        $query = $repository->createQueryBuilder('e')
            ->where('e.sync_uDate >= :date')
            ->setParameter('date', $lastSyncDate);

        $this->restController->syncCompleteQuery($query);

        $query = $query->getQuery();

        $this->entitiesm = $query->getResult();

        $this->logger->debug(
            "\tServer sync Client:" . $this->client . " append "
            . count($this->entitiesm)
        );
    }

    /**
     * PUT /insert
     */
    protected function insert($em, $startSyncDate, $repository, $type, $request, $formEntity) {
        $entitiesInserted = $request->request->get($type . '-i');

        if ($entitiesInserted) {
            foreach ($entitiesInserted as $requestEntity) {

                $entity = $this->restController->getNewEntity();

                $form = $this->restController->createForm($formEntity, $entity, array(
                    'csrf_protection' => false,
                    'allow_extra_fields' => true
                ));

                $form->submit($requestEntity);

                if ($form->isValid()) {
                    $selectEntity = null;

                    if (array_key_exists('hash', $requestEntity)) {
                        $hash = $requestEntity['hash'];
                        $selectEntity = $repository->findOneByHash($hash);

                        if ($selectEntity) {
                            $this->logger->debug(
                                    "\tSync found potential duplicated entity, find existing hash " . $hash);

                            if ($entity->getSyncUDate() > $selectEntity->getSyncUDate()) {
                                $entity->setId($selectEntity->getId());
                                $entity->setSyncUDate($startSyncDate);
                                // Sync data
                                $em->persist($entity);
                            } else {
                                $selectEntity->setMobileId($entity->getMobileId());
                                $entity = $selectEntity;
                            }
                        }
                    }

                    if (!$selectEntity) {
                        // Insert to server with new entity (db inserted)
                        $entity->setSyncUDate($startSyncDate);

                        // Sync data
                        $em->persist($entity);
                    }

                    $this->logger->debug(
                        '\tServer sync Client:' . $this->client .
                        ' inserted ' . $entity->__toString()
                    );

                    $this->entitiesi[] = $entity;
                } else {
                    $this->logger->debug(
                        "\tServer sync Client:" . $this->client
                        . " Form isn't valid" . $entity->__toString()
                    );
                }
            }
        }
    }

    /**
     * POST /update
     */
    private function update($em, $startSyncDate, $repository, $type, $request, $formEntity) {
        $entitiesUpdated = $request->request->get($type . '-u');

        if ($entitiesUpdated) {
            foreach ($entitiesUpdated as $requestEntity) {

                // check if sync
                if ($requestEntity['id'] != null) {
                    $selectEntity = $repository->findOneById($requestEntity['id']);
                    $entity = clone $selectEntity;

                    $form = $this->restController->createForm($formEntity, $entity, array(
                        'csrf_protection' => false,
                        'allow_extra_fields' => true
                    ));

                    $form->submit($requestEntity, false);

                    if ($form->isValid()) {
                        $selectEntity = $repository->findOneById($requestEntity['id']);

                        if ($entity->getSyncUDate() > $selectEntity->getSyncUDate()) {
                            // Update server with updated entity (db updated)
                            $entity->setSyncUDate($startSyncDate);
                            $selectEntity->setSyncUDate($startSyncDate);

                            // Sync data
                            $em->merge($entity);

                            $this->logger->debug(
                                "\tServer sync Client:" . $this->client
                                . " updated " . $entity->__toString()
                            );
                        } else {
                            // Update mobile with updated entity (db updated)
                            $entity->setSyncDTag(false);
                            $entity->setSyncUDate($startSyncDate);

                            // Sync data
                            $this->logger->debug(
                                "\tServer sync Client:" . $this->client
                                . " refresh " . $entity->__toString()
                            );
                        }

                        $this->entitiesu[] = $entity;
                    } else {
                        $this->logger->debug(
                            "\tServer sync Client:" . $this->client . " Form isn't valid"
                        );
                    }
                } else {
                    $this->logger->debug(
                        "\tServer sync Client:" . $this->client
                        . " WHY NOT INSERT !!!!!!!!! " . $requestEntity->__toString()
                    );
                }
            }
        }
    }

    /**
     * DELETE /delete
     */
    private function delete($em, $startSyncDate, $repository, $type, $request, $formEntity) {
        $entitiesDeleted = $request->request->get($type . '-d');

        if ($entitiesDeleted) {
            foreach ($entitiesDeleted as $requestEntity) {

                // check if sync
                if ($requestEntity['id'] != null) {
                    $selectEntity = $repository->findOneById($requestEntity['id']);
                    $entity = clone $selectEntity;

                    $this->logger->debug("\tServer sync Client: find " . $requestEntity['id']);

                    $form = $this->restController->createForm($formEntity, $entity, array(
                        'csrf_protection' => false,
                        'allow_extra_fields' => true
                    ));

                    $form->submit($requestEntity, false);

                    if ($form->isValid()) {
                        if ($entity->getSyncUDate() > $selectEntity->getSyncUDate()) {

                            // Update/Delete server with updated entity (db updated)
                            $selectEntity->setSyncUDate($startSyncDate);
                            $selectEntity->setSyncDTag($entity->getSyncDTag());
                            $entity->setSync_uDate($startSyncDate);

                            // Sync data
                            $em->merge($entity);

                            $this->logger->debug(
                                "\tServer sync Client:" . $this->client
                                . " delete " . $entity->__toString()
                            );
                        } else {
                            // Update mobile with updated entity (just db select)
                            $entity->setSyncDTag(false);
                            $entity->setSyncUDate($selectEntity->getSyncUDate());

                            // Sync data
                            $this->logger->debug(
                                "\tServer sync Client:" . $this->client
                                . " re-enable and updated " . $entity->__toString());
                        }

                        $this->entitiesd[] = $entity;
                    } else {
                        // Nothing to do.
                        $this->logger->debug("\tServer sync Client:" . $this->client . " nothing to delete ");
                    }
                } else {
                    $this->logger->debug(
                        "\tServer sync Client:" . $this->client
                        . " form not valid " . $requestEntity->__toString());
                }
            }
        }
    }
}

?>
