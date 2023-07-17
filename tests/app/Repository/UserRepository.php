<?php

namespace DTApi\Repository;

use DTApi\Models\Company;
use DTApi\Models\Department;
use DTApi\Models\Type;
use DTApi\Models\UsersBlacklist;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use DTApi\Models\User;
use DTApi\Models\Town;
use DTApi\Models\UserMeta;
use DTApi\Models\UserTowns;
use DTApi\Events\JobWasCreated;
use DTApi\Models\UserLanguages;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\FirePHPHandler;

/**
 * Class BookingRepository
 * @package DTApi\Repository
 */
class UserRepository extends BaseRepository
{

    protected $model;
    protected $logger;

    /**
     * @param User $model
     */
    function __construct(User $model,Logger $logger)
    {
        parent::__construct($model);
//        $this->mailer = $mailer;
        $this->logger = $logger;

        $this->logger->pushHandler(new StreamHandler(storage_path('logs/admin/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
    }

    public function createOrUpdate($id = null, $request)
    {
        $model = is_null($id) ? new User() : User::findOrFail($id);

        $this->updateUserAttributes($model, $request);
        $this->updateUserRoles($model, $request);

        if ($request['role'] == env('CUSTOMER_ROLE_ID')) {
            $this->updateCustomerData($model, $request);
            $this->updateCustomerMeta($model, $request);
            $this->updateBlacklist($model, $request);
        } elseif ($request['role'] == env('TRANSLATOR_ROLE_ID')) {
            $this->updateTranslatorData($model, $request);
            $this->updateTranslatorMeta($model, $request);
            $this->updateUserLanguages($model, $request);
        }

        if ($request['new_towns']) {
            $newTownId = $this->createNewTown($request['new_towns']);
        }

        $this->updateUserTowns($model, $request);

        $this->updateUserStatus($model, $request);

        return $model ? $model : false;
    }

    private function updateUserAttributes(User $model, $request)
    {
        $model->user_type = $request['role'];
        $model->name = $request['name'];
        $model->company_id = $request['company_id'] != '' ? $request['company_id'] : 0;
        $model->department_id = $request['department_id'] != '' ? $request['department_id'] : 0;
        $model->email = $request['email'];
        $model->dob_or_orgid = $request['dob_or_orgid'];
        $model->phone = $request['phone'];
        $model->mobile = $request['mobile'];

        if (!$id || $id && $request['password']) {
            $model->password = bcrypt($request['password']);
        }

        $model->save();
    }

    private function updateUserRoles(User $model, $request)
    {
        $model->detachAllRoles();
        $model->attachRole($request['role']);
    }

    private function updateCustomerData(User $model, $request)
    {
        if ($request['consumer_type'] == 'paid' && $request['company_id'] == '') {
            $type = Type::where('code', 'paid')->first();
            $company = Company::create(['name' => $request['name'], 'type_id' => $type->id, 'additional_info' => 'Created automatically for user ' . $model->id]);
            $department = Department::create(['name' => $request['name'], 'company_id' => $company->id, 'additional_info' => 'Created automatically for user ' . $model->id]);

            $model->company_id = $company->id;
            $model->department_id = $department->id;
            $model->save();
        }
    }

    private function updateCustomerMeta(User $model, $request)
    {
        $userMeta = UserMeta::firstOrCreate(['user_id' => $model->id]);

        $userMeta->consumer_type = $request['consumer_type'];
        $userMeta->customer_type = $request['customer_type'];
        $userMeta->username = $request['username'];
        $userMeta->post_code = $request['post_code'];
        $userMeta->address = $request['address'];
        $userMeta->city = $request['city'];
        $userMeta->town = $request['town'];
        $userMeta->country = $request['country'];
        $userMeta->reference = isset($request['reference']) && $request['reference'] == 'yes' ? '1' : '0';
        $userMeta->additional_info = $request['additional_info'];
        $userMeta->cost_place = isset($request['cost_place']) ? $request['cost_place'] : '';
        $userMeta->fee = isset($request['fee']) ? $request['fee'] : '';
        $userMeta->time_to_charge = isset($request['time_to_charge']) ? $request['time_to_charge'] : '';
        $userMeta->time_to_pay = isset($request['time_to_pay']) ? $request['time_to_pay'] : '';
        $userMeta->charge_ob = isset($request['charge_ob']) ? $request['charge_ob'] : '';
        $userMeta->customer_id = isset($request['customer_id']) ? $request['customer_id'] : '';
        $userMeta->charge_km = isset($request['charge_km']) ? $request['charge_km'] : '';
        $userMeta->maximum_km = isset($request['maximum_km']) ? $request['maximum_km'] : '';

        $userMeta->save();
    }

    private function updateBlacklist(User $model, $request)
    {
        $blacklistUpdated = [];
        $userBlacklist = UsersBlacklist::where('user_id', $id)->get();
        $userTranslId = collect($userBlacklist)->pluck('translator_id')->all();

        $diff = null;
        if ($request['translator_ex']) {
            $diff = array_intersect($userTranslId, $request['translator_ex']);
        }

        if ($diff || $request['translator_ex']) {
            foreach ($request['translator_ex'] as $translatorId) {
                $blacklist = new UsersBlacklist();
                if ($model->id) {
                    $alreadyExist = UsersBlacklist::translatorExist($model->id, $translatorId);
                    if ($alreadyExist == 0) {
                        $blacklist->user_id = $model->id;
                        $blacklist->translator_id = $translatorId;
                        $blacklist->save();
                    }
                    $blacklistUpdated[] = $translatorId;
                }
            }
            if ($blacklistUpdated) {
                UsersBlacklist::deleteFromBlacklist($model->id, $blacklistUpdated);
            }
        } else {
            UsersBlacklist::where('user_id', $model->id)->delete();
        }
    }

    private function updateTranslatorData(User $model, $request)
    {
        $userMeta = UserMeta::firstOrCreate(['user_id' => $model->id]);

        $userMeta->translator_type = $request['translator_type'];
        $userMeta->worked_for = $request['worked_for'];
        if ($request['worked_for'] == 'yes') {
            $userMeta->organization_number = $request['organization_number'];
        }
        $userMeta->gender = $request['gender'];
        $userMeta->translator_level = $request['translator_level'];
        $userMeta->additional_info = $request['additional_info'];
        $userMeta->post_code = $request['post_code'];
        $userMeta->address = $request['address'];
        $userMeta->address_2 = $request['address_2'];
        $userMeta->town = $request['town'];

        $userMeta->save();
    }

    private function updateTranslatorMeta(User $model, $request)
    {
        $data['translator_type'] = $request['translator_type'];
        $data['worked_for'] = $request['worked_for'];
        if ($request['worked_for'] == 'yes') {
            $data['organization_number'] = $request['organization_number'];
        }
        $data['gender'] = $request['gender'];
        $data['translator_level'] = $request['translator_level'];
    }

    private function updateUserLanguages(User $model, $request)
    {
        if ($request['user_language']) {
            foreach ($request['user_language'] as $langId) {
                $userLang = new UserLanguages();
                $alreadyExist = $userLang::langExist($model->id, $langId);
                if ($alreadyExist == 0) {
                    $userLang->user_id = $model->id;
                    $userLang->lang_id = $langId;
                    $userLang->save();
                }
            }
        }
    }

    private function createNewTown($townname)
    {
        $town = new Town();
        $town->townname = $townname;
        $town->save();

        return $town->id;
    }

    private function updateUserTowns(User $model, $request)
    {
        $townidUpdated = [];
        if ($request['user_towns_projects']) {
            $del = DB::table('user_towns')->where('user_id', '=', $model->id)->delete();
            foreach ($request['user_towns_projects'] as $townId) {
                $userTown = new UserTowns();
                $alreadyExist = $userTown::townExist($model->id, $townId);
                if ($alreadyExist == 0) {
                    $userTown->user_id = $model->id;
                    $userTown->town_id = $townId;
                    $userTown->save();
                }
                $townidUpdated[] = $townId;
            }
        }
    }

    private function updateUserStatus(User $model, $request)
    {
        if ($request['status'] == '1') {
            if ($model->status != '1') {
                $this->enable($model->id);
            }
        } else {
            if ($model->status != '0') {
                $this->disable($model->id);
            }
        }
    }

    public function enable($id)
    {
        $user = User::findOrFail($id);
        $user->status = '1';
        $user->save();

    }

    public function disable($id)
    {
        $user = User::findOrFail($id);
        $user->status = '0';
        $user->save();

    }

    public function getTranslators()
    {
        return User::where('user_type', 2)->get();
    }
    
}