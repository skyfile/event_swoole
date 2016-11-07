<?php
namespace Api\Index\V1;

/**
* 创建资源模块
*/
class Post extends Api
{

	public function appid()
	{
		if (!isset($_GET['name'])) {
			return $this->error('参数错误');
		}
		$name = $_GET['name'];

		$model = new \Model\MailAppid();
		$res = $model->where("name = '{$name}'")->getOne();
		if ($res) {
			return $this->error('该app已授权');
		}
		$appkey = md5($name . time());
		$data = [
			'name'	=>	$name,
			'appkey'=>	$appkey,
			'uptime'=>	time()
		];

		$res = $model->insert($data);
		return $this->success($res);
	}
}