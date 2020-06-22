<?php

namespace ProcessMaker\Packages\Connectors\Email;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Packages\Connectors\Email\Seeds\EmailSendSeeder;

class Notifications
{
    const TASK_START = 'task-start';
    const TASK_COMPLETED = 'task-end';

    /**
     * Create notification in event activity activated
     *
     * @param ActivityActivatedEvent $event
     */
    public function created(ActivityActivatedEvent $event)
    {
        $this->sendNotificationAt($event, self::TASK_START);
    }

    /**
     * Create notification in event activity completed
     *
     * @param ActivityCompletedEvent $event
     */
    public function completed(ActivityCompletedEvent $event)
    {
        $this->sendNotificationAt($event, self::TASK_COMPLETED);
    }

    /**
     * Check if we need to send a notification at the start
     * or end of the task event
     *
     * @param $event
     * @param $sendAt
     */
    private function sendNotificationAt($event, $sendAt)
    {
$token = $event->token;
$user_id = $token->user ? $token->user_id : null;
Log::info("*************Token*************");
Log::info($token);
// Log::info($token->processRequest->data);
        if (!isset($event->token->getDefinition()['config'])) {
            return;
        }
        $config = json_decode($event->token->getDefinition()['config'], true);
// Log::info("*************Config*************");
// Log::info($config);

        if (isset($config['email_notifications'])) {
            foreach ($config['email_notifications']['notifications'] as $notificationConfig) {
                if ($notificationConfig['sendAt'] !== $sendAt) {
                    continue;
                }
                if ($notificationConfig['sendToAssignee']) {
Log::info("*************before*************");
Log::info($notificationConfig['users']);
                    if (!in_array($event->token->user_id, $notificationConfig['users'])) {
                        array_push($notificationConfig['users'], $event->token->user_id);
                    }
                    // $notificationConfig['users'] = in_array($event->token->user_id, $notificationConfig['users']) ? $notificationConfig['users'] : array_push($notificationConfig['users'], $event->token->user_id);
Log::info("*************after*************");
Log::info($notificationConfig['users']);
                }
                $this->createNotification(
                    $notificationConfig,
                    $event->token
                );
            }
Log::info("*************Config*************");
Log::info($config);
        }
    }

    /**
     * Create notification with sub-process
     *
     * @param $notificationConfig
     * @param $data
     */
    private function createNotification($notificationConfig, $token)
    {
        $subProcess = $this->notificationSubProcess();
// print("<pre>".print_r($subProcess, true)."</pre>");
        $definitions = $subProcess->getDefinitions();
// print("<pre>".print_r($definitions, true)."</pre>");exit;
        $event = $definitions->getEvent(EmailSendSeeder::SUB_PROCESS_START_EVENT);
        WorkflowManager::triggerStartEvent(
            $subProcess, $event, array_merge($token->processRequest->data, [
                '_request_id' => $token->processRequest->id,
                '_task_name' => $token->element_name,
                'notification_config' => $notificationConfig
            ])
        );
    }

    /**
     * Load sub process send email
     *
     * @return mixed
     */
    private function notificationSubProcess()
    {
        return Process::where('package_key', EmailSendSeeder::SUB_PROCESS_ID)->firstOrFail();
    }

}