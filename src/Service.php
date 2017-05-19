<?php

namespace conceptho\ServiceLayer;

use Yii;
use yii\base\Model;

class Service extends \yii\base\Component
{
    const EVENT_BEFORE_ACTION = 'beforeAction';
    const EVENT_AFTER_ACTION = 'afterAction';
    const EVENT_FAIL_ACTION = 'failAction';
    
    protected $transaction;
    
    public function init()
    {
        $this->on(self::EVENT_BEFORE_ACTION, [$this, 'startTransaction']);
        $this->on(self::EVENT_AFTER_ACTION, [$this, 'endTransaction']);
        $this->on(self::EVENT_FAIL_ACTION, [$this, 'rollbackTransaction']);
    }
    
    public function startTransaction($event)
    {
        $this->transaction = Yii::$app->db->beginTransaction();
    }
    
    public function endTransaction($event)
    {
        $this->transaction->commit();
    }
    
    public function rollbackTransaction($event)
    {
        $reason = $event->exception instanceof \Exception ? $event->exception->getMessage() : print_r($event,1);
        Yii::error("Rollback transaction reason: ".$reason.". Backtrace: ".$event->exception->getTraceAsString());
        $this->transaction->rollBack();
    }
    
    public function __call($name, $params) {
        
        $methodName = 'action'.ucfirst($name);
        
        if(method_exists($this, $methodName))
        {
            $this->trigger(self::EVENT_BEFORE_ACTION);
            try
            {
                $return = call_user_func_array(array($this, $methodName), $params);
            } catch (\Exception $e) {
                $this->trigger(self::EVENT_FAIL_ACTION, new ErrorEvent(['exception' => $e]));
                return new Response(false, ['message' => $e->getMessage(), 'exception' => $e]);
            }
            $this->trigger(self::EVENT_AFTER_ACTION);
            return $return;
        }
        else
        {
            return parent::__call($name, $params);
        }
    }

    protected function saveModel(Model $model, $errorMessage = '')
    {
        if (!$model->save())
        {
            if($errorMessage == '')
                $errorMessage = 'Não foi possivel salvar o '.$model->modelLabel().'. Verifique as mensagens de erro.';

            if(defined('YII_TEST'))
                $errorMessage .= '. Os erros foram: '.print_r($model->errors,1);

            Yii::error($errorMessage.'. Os erros foram: '.print_r($model->errors,1));
            throw new \Exception($errorMessage);
        }

        return true;
    }

    protected function checkResponse(Response $res, $dataOnError = array())
    {
        if (!$res->isOk) {
            Yii::error($res['message'].' - Data: '.print_r($dataOnError, 1));
            throw new \Exception($res['message']);
        }
    }
}

class ErrorEvent extends \yii\base\Event {
    public $exception;
}