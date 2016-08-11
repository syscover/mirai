<?php namespace Syscover\Forms\Libraries;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Syscover\Forms\Models\Message;
use Syscover\Pulsar\Models\EmailAccount;
use Syscover\Pulsar\Models\Preference;

class Cron
{
    public static function checkMessageToSend()
    {
        $notificationsAccount   = Preference::getValue('formsNotificationsAccount', 4);
        $emailAccount           = EmailAccount::find($notificationsAccount->value_018);

        if($emailAccount == null) return null;

        $messages = Message::where('dispatched_405', false)->get();

        config(['mail.host'         =>  $emailAccount->outgoing_server_013]);
        config(['mail.port'         =>  $emailAccount->outgoing_port_013]);
        config(['mail.from'         =>  ['address' => $emailAccount->email_013, 'name' => $emailAccount->name_013]]);
        config(['mail.encryption'   =>  $emailAccount->outgoing_secure_013 == 'null'? null : $emailAccount->outgoing_secure_013]);
        config(['mail.username'     =>  $emailAccount->outgoing_user_013]);
        config(['mail.password'     =>  Crypt::decrypt($emailAccount->outgoing_pass_013)]);

        foreach($messages as $message)
        {
            Mail::send(['html' => $message->template_405, 'text' => $message->text_template_405], ['dataMessage' => $message, 'dataTextMessage' => json_decode($message->data_message_405), 'data' => json_decode($message->data_405)], function($m) use ($emailAccount, $message) {
                $m->to($message->email_405, $message->name_405)->subject(trans($message->subject_405) . ' ' . json_decode($message->data_message_405)->name_form_405 . ' ( ID. ' . json_decode($message->data_405)->id_403 . (isset(json_decode($message->data_405)->email_403) && isset(json_decode($message->data_405)->email_403) != ''?  ' - ' . json_decode($message->data_405)->email_403 : null) . ' )');
                if($emailAccount->reply_to_013 != null) $m->replyTo($emailAccount->reply_to_013);
            });

            Message::where('id_405', $message->id_405)->update([
                'dispatched_405'  => true,
                'send_date_405'   =>  date('U')
            ]);
        }
    }
}