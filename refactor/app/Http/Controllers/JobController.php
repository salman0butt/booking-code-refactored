<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use Illuminate\Http\Request;

/**
 * Class JobController
 * @package DTApi\Http\Controllers
 */
class JobController extends Controller
{
    /**
     * @var JobRepository
     */
    protected $repository;

    /**
     * @var JobService
     */
    protected $jobService;

    /**
     * BookingController constructor.
     * @param JobRepository $repository
     */

    public function __construct(JobRepository $repository, $jobService)
    {
        $this->repository = $repository;
        $this->jobService = $jobService;
    }

    public function getAll(Request $request, $limit = null)
    {
        $requestdata = $request->all();
        $cuser = $request->user();
        return $this->repository->getAll($requestdata, $cuser, $limit);
    }

        /**
     * @param $user_id
     * @param $request
     * @return array
     */
    public function getUsersJobsHistory($user_id, Request $request)
    {
        $response = $this->repository->getUsersJobsHistory($user_id, $request);
        return response($response);
    }

    public function cancelJobAjax(Request $request, $user)
    {
        $data = $request->all();
        $response = $this->jobService->cancelJobAjax($data, $user);

        // Handle the response and return a JSON response
        return response()->json($response);
    }

    public function alerts()
    {
        $response = $this->jobService->alerts();
        // Handle the response and return a JSON response
        return response($response);
    }


}