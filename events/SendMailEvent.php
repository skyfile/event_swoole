<?php
namespace Event;

class SendMailEvent implements Event\EventInterface
{

    public $transport;
    public $mailer;
    public $message;
    public $data;
    public $error = false;
    public $log = false;
    public $password;

    /**
     * 执行操作
     * @param  [type] $type [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function trigger($type, $data)
    {
        $this->data = unserialize($data);

        if (isset($this->data['password'])) {
            $this->password = $this->data['password'];
        } else {
            //动态获取密码
            $redisPasswd = new \App\Controller\MailPasswd();
            $this->password = $redisPasswd->getEmailPasswd($this->data['sendMail']);
        }

        if(!$this->sendMail()){  //如果发送不成功, 则存入错误数据库
            $model = Model('MailFailed');
            $id = $model->put([
                'appid'     =>  'news',
                'senduser'  =>  $this->data['sendMail'],
                'errmsg'    =>  $this->error,
                'content'   =>  $data,
                'uptime'    =>  time()
            ]);
            if(!$id) {
                $this->log()->error($model->dbs->db->errorInfo());
            }
        }

        if($this->log){
            $this->log()->flush();
        }
    }

    public function log()
    {
        if(!$this->log){
            $this->log = \Swoole::$php->log;
        }
        return $this->log;
    }

    public function sendMail()
    {
        $this->initMail();
        $this->setFrom();
        $this->setTo();
        $this->setSubject();
        $this->setBody();
        if (isset($this->data['attach']) && $this->data['attach']) {
            $this->setAttach($this->data['attach']);
        }

        try{
            $res = $this->mailer->send($this->message);
        }
        catch (\Swift_TransportException $e){
            $res = false;
            $this->error = $e->getMessage();
            $this->log()->error($this->error);
        }
        return $res;
    }

    /**
     * 设置发送服务
     */
    protected function initMail()
    {
        $this->transport = \Swift_SmtpTransport::newInstance('smtp.exmail.qq.com', 25);
        $this->transport->setUsername($this->data['sendMail']);
        $this->transport->setPassword($this->password);
        $this->mailer = \Swift_Mailer::newInstance($this->transport);
        $this->message = \Swift_Message::newInstance();
    }

    /**
     * 设置来源用户
     * @param array $userArray ['邮箱地址' => '用户名称']
     */
    protected function setFrom($userArr = [])
    {
        $this->message->setFrom(empty($userArr) ? [$this->data['sendMail'] => $this->data['sendName']] : []);
    }

    /**
     * 设置目标用户
     * @param string $value [收信人数组] eg:['邮箱地址' => '用户名称']
     */
    protected function setTo($userArr = [])
    {
        $this->message->setTo(empty($userArr) ? $this->data['to'] : []);
    }

    /**
     * 设置标题
     * @param string $subject [标题]
     */
    protected function setSubject($subject = '')
    {
        $subject = $subject == '' ? $this->data['subject'] : '';
        if ($subject) {
            $this->message->setSubject($subject);
        }
    }

    /**
     * 设置发送内容
     * @param string $content [内容]
     * @param string $mims [文档类型]
     * @param string $type [字符编码]
     */
    protected function setBody($content = '', $mims = 'text/html', $type = 'utf-8')
    {
        $this->message->setBody($content == '' ? $this->data['content'] : '', $mims, $type);
    }

    /**
     * 设置附件
     * @param [type] $file [文件地址]
     */
    protected function setAttach($file)
    {
        $filePath = WEBPATH.'/public/'.$file;
        if(!is_file($filePath)) {
            $this->error = true;
            return false;
        }
        $this->message->attach(\Swift_Attachment::fromPath($filePath)->setFilename($file));
    }

}
