<?php

namespace ProcessMaker\Packages\Connectors\Email\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Media;
use ProcessMaker\Packages\Connectors\Email\Jobs\SendEmail;
use ProcessMaker\Packages\Connectors\Email\ScreenRenderer;

class EmailController extends Controller
{
    /**
     * Send and email by request
     *
     * @param Request $request
     * @return Response
     */
    public function send(Request $request)
    {
        $config = $request->input();
 
        //Load data
        $data = json_decode($config['json_data'], true);

        //Mustache
        $mustache = new \Mustache_Engine;

        //Mustache data notification
        if (isset($data['notification_config'])) {
            foreach ($data['notification_config'] as $key => $value) {
                if (is_array($value)) {
                    $data['notification_config'][$key] = $mustache->render(implode(", ", $value), $data);
                } else {
                    $data['notification_config'][$key] = $mustache->render((string) $value, $data);
                }
            }
        }

        if (isset($config['sendToRequester']) && $config['sendToRequester']) {
            if (isset($data['_request']['id'])) {
                $process_data = ProcessRequest::where('id','=', $data['_request']['id'])->first();
                if(isset($config['users']) && is_array($config['users'])) {
                    if (!in_array($process_data["user_id"], $config['users'])) {
                        array_push($config['users'], $process_data["user_id"]);
                    }
                }
                if (!isset($config['users'])) {
                    $config['users'] = [$process_data["user_id"]];
                }                
            }
        }

        //Validate data
        $groups = !empty($config['groups']) ? $this->getData($config['groups'], $data) : [];
        $users = !empty($config['users']) ? $this->getData($config['users'], $data) : [];
        $additionalEmails = !empty($config['addEmails']) ? $this->getData($config['addEmails'], $data) : [];
        $type =!empty( $config['type']) ? $mustache->render($config['type'], $data) : 'screen';
        //Load mails
        $usersGroups = [];
        if(!empty($groups[0])) {
            $usersGroups = GroupMember::whereIn('group_id', $groups)
                ->where('member_type', User::class)
                ->pluck('member_id')
                ->toArray();
        }
        
        if(!empty($users[0])) {
            $users = array_merge($users, $usersGroups);
        }else {
            $users = $usersGroups;
        }
        $emails = User::whereIn('id', $users)
            ->pluck('email')
            ->toArray();

        //Add additional emails
        foreach ($additionalEmails as $item) {
            $item = $mustache->render($item, $data);
            foreach (explode(",", $item) as $email) {
                $emails[] = trim($email);
            }
        }

        //load Body
        if ($type === 'screen') {
            //screen definition
            $screen = Screen::find($mustache->render($config['screenRef'], $data));
            $rendered = ScreenRenderer::render($screen->config, $data);
            $config['body'] = $rendered;
        } else {
            //Plain text
            $config['body'] = htmlentities($mustache->render($config['textBody'], $data), ENT_QUOTES, 'UTF-8');
        }

        if(isset($data['notification_config']['attachUploadFile']) && $data['notification_config']['attachUploadFile']) {

            $media = Media::where('model_id','=', $data['_request_id'])->orderBy('created_at', 'desc')->first();
            $processRequest = ProcessRequest::where('id','=', $data['_request_id'])->first();

            $config['processRequest'] = $processRequest;
            if(isset($media)) {
                $config['media'] = $media;
                $config['file_name'] = $media['file_name'];
            }
        }

        //change mustache
        $config['subject'] = $mustache->render($config['subject'], $data);
        $config['email'] = array_filter($emails);

        //created queue job
        dispatch(new SendEmail($config));

        if (isset($data['notification_config'])) {
            Comment::create([
                'type' => 'LOG',
                'user_id' => null,
                'commentable_type' => ProcessRequest::class,
                'commentable_id' => $data['_request_id'],
                'subject' => 'Email Notification Sent',
                'body' => __(
                    'System has sent a notification to :emails for :task',
                    [
                        "emails" => join(", ", $config['email']),
                        "task" => $data['_task_name']
                    ]
                )
            ]);
        }

        return response()->json();
    }

    private function getData($config, $data)
    {
        //Mustache
        $mustache = new \Mustache_Engine;
        if (is_array($config)) {
            return $config;
        }
        $data = $mustache->render($config, $data);

        if (!is_array($data)) {
            $data = explode(',', $data);
        }

        return $data;
    }

}
