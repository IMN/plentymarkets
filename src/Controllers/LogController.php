<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 18/05/20
 * Time: 21:55
 */

namespace IMN\Controllers;

use IMN\Repositories\LogRepository;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;


class LogController extends Controller
{

    public function getLog(
        Request $request,
        LogRepository $logRepository
    ) {
        $filterKeys = array(
            'marketplaceCode',
            'marketplaceOrderId',
            'merchantCode',
            'type',
            'message',
        );
        $data = $request->all();
        $filters = array();
        foreach($filterKeys as $key) {
            if(array_key_exists($key, $data)) {
                $filters[$key] = $data[$key];
            }
        }
        $page = $request->get('page',1);
        $itemsPerPage = $request->get('itemsPerPage',25);
        $sortBy = $request->get("sortBy", 'id');
        $sortOrder = $request->get("sortOrder", 'desc');

        return $logRepository->getLog($page, $itemsPerPage, $filters, $sortBy, $sortOrder);
    }

}