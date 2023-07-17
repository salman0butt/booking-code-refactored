<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class PushNotificationService
{
    /**
     * Send push notifications to suitable translators
     *
     * @param Job $job
     * @param array $data
     * @param int $exclude_user_id
     */
    public function sendNotificationToTranslators(Job $job, array $data, int $exclude_user_id)
    {
        $users = User::all();
        $translatorArray = [];
        $delayedTranslatorArray = [];

        foreach ($users as $user) {
            if ($user->user_type === '2' && $user->status === '1' && $user->id !== $exclude_user_id) {
                if (!$this->isNeedToSendPush($user->id)) {
                    continue;
                }
                $notGetEmergency = TeHelper::getUsermeta($user->id, 'not_get_emergency');
                if ($data['immediate'] === 'yes' && $notGetEmergency === 'yes') {
                    continue;
                }
                $jobs = $this->getPotentialJobIdsWithUserId($user->id);
                foreach ($jobs as $jobItem) {
                    if ($job->id === $jobItem->id) {
                        $userId = $user->id;
                        $jobForTranslator = Job::assignedToPaticularTranslator($userId, $jobItem->id);
                        if ($jobForTranslator === 'SpecificJob') {
                            $jobChecker = Job::checkParticularJob($userId, $jobItem);
                            if ($jobChecker !== 'userCanNotAcceptJob') {
                                if ($this->isNeedToDelayPush($user->id)) {
                                    $delayedTranslatorArray[] = $user;
                                } else {
                                    $translatorArray[] = $user;
                                }
                            }
                        }
                    }
                }
            }
        }

        $data['language'] = TeHelper::fetchLanguageFromJobId($data['from_language_id']);
        $data['notification_type'] = 'suitable_job';
        $msgContents = ($data['immediate'] === 'no') ? 'Ny bokning för ' . $data['language'] . 'tolk ' . $data['duration'] . 'min ' . $data['due'] : 'Ny akutbokning för ' . $data['language'] . 'tolk ' . $data['duration'] . 'min';
        $msgText = ["en" => $msgContents];

        $logger = new Logger('push_logger');
        $logger->pushHandler(new StreamHandler(storage_path('logs/push/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $logger->pushHandler(new FirePHPHandler());
        $logger->addInfo('Push send for job ' . $job->id, [$translatorArray, $delayedTranslatorArray, $msgText, $data]);

        $this->sendPushNotificationToSpecificUsers($translatorArray, $job->id, $data, $msgText, false);
        $this->sendPushNotificationToSpecificUsers($delayedTranslatorArray, $job->id, $data, $msgText, true);
    }

    // Other methods...

    // Implement the remaining methods according to your application's logic.
}
