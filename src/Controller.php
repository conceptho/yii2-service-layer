<?php

namespace conceptho\ServiceLayer;

use yii\data\ActiveDataProvider;
use Yii;

class Controller extends \yii\web\Controller {

	public $className;
	protected $_model;
	protected $enableSearch = false;
	protected $searchModelName = 'searchModel';

	protected function getModel($config = []) {
		return Yii::$container->get($this->className, [], $config);
	}

	protected function loadModel($id = null) {
		if ($id === null) {
			if (Yii::$app->request->get('id')) {
				$id = Yii::$app->request->get('id');
			} elseif (Yii::$app->request->post('id')) {
				$id = Yii::$app->request->post('id');
			} else {
				return null;
			}
		}

		return $this->_model = $this->model->findOne($id);
	}

	protected function createModels($model, $data)
	{
		if($model instanceof \yii\base\BaseObject)
		{
			$model = $model->className();
		}

		$base = new $model;

		if(!isset($data[$base->formName()])) return [];
		$size = count($data[$base->formName()]);

		$models = [];
		for($i = 0; $i<$size; $i++) {
			$models[] = new $model;
		}

		return $models;
	}

	protected function getDataProvider($config = []) {
		$query = $this->model->find();

		if($this->enableSearch)
		{
			$query->search(Yii::$app->request->get( $this->model->formName() ));
		}

		return new ActiveDataProvider(\yii\helpers\ArrayHelper::merge([
			'query' => $query,
		], $config));
	}

	public function render($view, $params = []){

		if(!isset($params[$this->searchModelName]) && isset($this->className))
		{
			$model = $this->getModel(['scenario' => 'search']);
			$model->load(Yii::$app->request->get());
			$params[$this->searchModelName] = $model;
		}

		return parent::render($view, $params);
	}

	public function onSuccess() {
		return true;
	}

	public function onFail() {
		return false;
	}

	public function runService($model, $body, $success = null, $fail = null) {
		$post = Yii::$app->request->post();

		if (Yii::$app->request->isPost && (!isset($post[$model->formName()]) || $model->load($post))) {
			return $this->checkResponse($body($model), $model, $success, $fail);
		}
	}

	public function checkResponse($response, $model, $success = null, $fail = null)
	{
		if (!$success)
			$success = [$this, 'onSuccess'];
		if (!$fail)
			$fail = [$this, 'onFail'];


		if ($response->isOk) {
			Yii::$app->session->setFlash('success', $response['message'] ? $response['message'] : Yii::t('yii2-service-layer','Successful operation'));
			return call_user_func($success, $response, $model, $this);
		} else {
			Yii::$app->session->setFlash('danger', $response['message'] ? $response['message'] : Yii::t('yii2-service-layer','Could not perform operation'));
			return call_user_func($fail, $response, $model, $this);
		}
	}

}
