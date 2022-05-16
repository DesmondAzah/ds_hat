<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Client\Request;
use App\Services\HatSetupService;

class HatSetUpController extends Controller {

  
    /**
     * PROCESS a request to set up hat, level, and rank relationships
     * @param HatSetupService $hatService
     * @param Request $request
     * @return Illuminate\Http\Response
     */
    public function setUpHatLR(HatSetupService $hatService){
        return $hatService->setupHat(request()->all());
    }

    public function updateHatLRSetup($id,HatSetupService $hatService){
        return $hatService->updateHatSetup($id,request()->all());
    }

    /**
     * PROCESS a request to get all hats
     * @param HatSetupService $hatService
     * @return Illuminate\Http\Response
     */

     public function getAllHatSetups(HatSetupService $hatService){
        return $hatService->getAllHats();
    }

    /**
     * PROCESS a request to get a hat
     * @param HatSetupService $hatService
     * @param $id   
     * @return Illuminate\Http\Response
    **/
    public function getHatSetup($id,HatSetupService $hatService){
        return $hatService->getHatDetails($id);
    }

    /**
     * PROCESS a request to get hat table
     * @param HatSetupService $hatService
     * @return Illuminate\Http\Response
     **/
    public function getHatTable(HatSetupService $hatService){
        return $hatService->getHatTable();
    }

    /**
     * PROCESS a request to get hat chart
     * @param HatSetupService $hatService
     * @return Illuminate\Http\Response
     */
    public function getHatChart(HatSetupService $hatService){
        return $hatService->getHatChart();
    }

    public function switchHatParent(HatSetupService $hatService){
        return $hatService->switchHatParent();
    }
}