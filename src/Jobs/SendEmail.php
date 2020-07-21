<?php

namespace ProcessMaker\Packages\Connectors\Email\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Message;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $properties;

    /**
     * Create a new job instance.
     *
     * @param $properties
     */
    public function __construct($properties)
    {
        $this->properties = $properties;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['package-email', $this->properties['subject']];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->properties['processRequest']->getMedia();

        $file_enc_key = base64_decode(substr(config('app.file_key'), 7));
        $newEncrypter = new Encrypter( $file_enc_key, config('app.cipher'));
        $encryptedContent = Storage::get('public/'. $this->properties['media']->id.'/'. $this->properties['media']->file_name.'.enc');
        try{
            $this->properties['decryptedContent'] = $newEncrypter->decrypt($encryptedContent);
        } catch (DecryptException $e){
            Log::debug("Failed to Decrypt File.");
        }
        
        Mail::send([], [], function (Message $message) {
            $message->to($this->properties['email'])
                ->subject($this->properties['subject'])
                ->setBody(view('email::layout', array_merge($this->properties, ['message' => $message]))->render(), 'text/html');
            $message->attachData($this->properties['decryptedContent'], $this->properties['file_name']);
        });
    }
}
