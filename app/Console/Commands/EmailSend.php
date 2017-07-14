<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Jpush;

use App\Goal;
use App\Models\Device as Device;


class EmailSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send welcome email to user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = 'http://api.sendcloud.net/apiv2/mail/sendtemplate';

          $users = DB::table('users')
//            ->whereRaw("from_unixtime(reg_time,'%Y-%m-%d %H:%i')='".date('Y-m-d H:i')."'")
             ->whereRaw("from_unixtime(reg_time,'%Y-%m-%d')='".date('Y-m-d')."'")
            ->whereNotNull('email')
            ->get();

        $bar = $this->output->createProgressBar(count($users));

        $tos = [];   

        foreach ($users as $key => $user) {
            $bar->advance();

            $tos[] = $user->email;
        }

        // var_dump($users);

        // exit;

         // $tos = ['ccnuzxg@163.com','281674669@qq.com'];  

        if($tos) {
             $vars = json_encode( array("to" => $tos
                                   // ,"sub" => array("%code%" => Array('123456'))
                                   )
                );

            $API_USER = 'keepdays';
            $API_KEY = 'zDIdjyKEUSqdyESA';
            $param = array(
                'apiUser' => $API_USER, # 使用api_user和api_key进行验证
                'apiKey' => $API_KEY,
                'from' => 'welcome@keepdays.com', # 发信人，用正确邮件地址替代
                'fromName' => '坚持每一天',
                'xsmtpapi' => $vars,
                'templateInvokeName' => 'app_register_template',
                'subject' => '欢迎来到坚持每一天',
                'respEmailId' => 'true'
            );
            

            $data = http_build_query($param);

            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $data
            ));
            $context  = stream_context_create($options);
            $result = file_get_contents($url, FILE_TEXT, $context);   

            echo  $result;
        }

        $bar->finish();
                
    }
}