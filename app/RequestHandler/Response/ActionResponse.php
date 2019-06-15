<?php

namespace App\RequestHandler\Response;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActionResponse{

    private $request;
    private $headers;
    private $config;
    private $ip;
    private $message;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->config = config('guard');
        $this->headers = config('headers');
        $this->message = config('message');
        $this->ip = $this->getUserIP();
    }


    function getUserIP()
    {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];
        if(filter_var($client, FILTER_VALIDATE_IP))
        {
            $ip = $client;
        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP))
        {
            $ip = $forward;
        }
        else
        {
            $ip = $remote;
        }
        return $ip;
    }


    public function checkRequest()
    {

        if ((count($this->getListRequest())> intval($this->config['countRequestPerMinute']) - 1) && $this->getBannedIp() == 0) {
            $this->banIp();
            $this->badRequest();
        }
        elseif ($this->getBannedIp() == 1){
            $this->badRequest();
        }
        elseif($this->getBannedIp() == 0) {
            $this->goodRequest();
        }
    }

    private function goodRequest()
    {
        $this->insertLog();
        $this->getRequest($this->headers['200'],$this->message['success']);

    }


    private function badRequest()
    {
        $this->getRequest($this->headers['400'],$this->message['badRequest']);
    }


    private function getListRequest()
    {
        return DB::table('log')
            ->where('ip',$this->ip)
            ->whereBetween('time',[time()-60,time()])
            ->get();
    }


    private function getBannedIp()
    {
        $ban = DB::table('ban')
            ->where('ip', '=',$this->ip)
            ->where('startBan','<=',time())
            ->where('endBan', '>=', time())
            ->count();

        return $ban;
    }

    private function banIp()
    {
        if ($this->getBannedIp() == 0){
            DB::table('ban')
                ->insert([
                    'ip' => $this->getUserIP(),
                    'startBan'=> time(),
                    'endBan'=> time()+$this->config['timeBan'],
                ]);
        }
    }



    private function insertLog()
    {
        DB::table('log')
            ->insert([
                'ip' => $this->getUserIP(),
                'time'=> time(),
            ]);
    }


    private function getTimeTimeout(){
        $time = 0;
        $endTimeOut = DB::table('ban')
            ->select('endBan')
            ->where('startBan','<=',time())
            ->where('endBan', '>=', time())
            ->first();
        if (isset($endTimeOut->endBan))
        {
            $time = intval($endTimeOut->endBan) - time();
        }
        return $time;
    }


    public function getRequest ($status,$message)
    {
        echo ($message);
        $response_length = ob_get_length();
        if (is_callable('fastcgi_finish_request'))
        {
            session_write_close();
            fastcgi_finish_request();

            return;
        }
        ignore_user_abort(true);
        ob_start();
        if ($this->getTimeTimeout() == null )
        {
            header('HTTP/1.1 '.$status['response'].' '.$status['content']);
        }
        else{
            header('HTTP/1.1 '.$status['response'].' '.$status['content'].' '.$this->getTimeTimeout().' second');
        }
        header('Content-Encoding: none');
        header('Content-Length: ' . $response_length);
        ob_end_flush();
        ob_flush();
        flush();

    }


}