<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Distance;
use DTApi\Models\Job;
use DTApi\Repository\BookingRepository;
use Illuminate\Http\Request;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($request->has('user_id')) {
            $response = $this->repository->getUsersJobs($request->get('user_id'));
        } elseif ($user->hasAnyRole([Config::get('roles.admin_role_id'), Config::get('roles.superadmin_role_id')])) {
            $response = $this->repository->getAll($request);
        } else {
            abort(401, 'Unauthorized');
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        try {
            $job = $this->repository->with('translatorJobRel.user')->findOrFail($id);
            return response($job);
        } catch (ModelNotFoundException $e) {
            abort(404, 'Job not found');
        }
    }
    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $user = $request->user();
    
        $response = $this->repository->store($user, $data);
    
        return response($response);
    }
    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->all();
        $user = $request->user();
    
        $response = $this->repository->updateJob($id, $data, $user);
    
        return response($response);
    }
    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }
    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $user = $request->user();
        $userId = $request->input('user_id');
    
        if ($userId && $user->can('view-users-jobs-history')) {
            $response = $this->repository->getUsersJobsHistory($userId, $request);
            return response()->json($response);
        }
    
        abort(401, 'Unauthorized');
    }
    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->user();
    
        $response = $this->repository->acceptJob($data, $user);
    
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJobWithId(Request $request)
    {
        $jobId = $request->input('job_id');
        $user = $request->user();
    
        $response = $this->repository->acceptJobWithId($jobId, $user);
    
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->user();
        $response = $this->repository->cancelJobAjax($data, $user);
    
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->user();

        $response = $this->repository->getPotentialJobs($user);

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->distanceFeed($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            abor('500', 'Something Went Wrong.')
        }
    }
}
