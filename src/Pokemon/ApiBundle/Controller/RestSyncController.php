<?php

/**************************************************************************
 * RestSyncController.php, pokemon Android
 *
 * Copyright 2016
 * Description : 
 * Author(s)   : Harmony
 * Licence     : 
 * Last update : May 26, 2016
 *
 **************************************************************************/

namespace Pokemon\ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController as Controller;

/**
 * Rest Sync Controller
 */
class RestSyncController extends Controller {
    public function getNewEntity() {
        return null;
    }
}

?>
