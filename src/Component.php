<?php


namespace conceptho\ServiceLayer;

use Yii;

class Component extends \yii\base\Component
{
    protected $instances = array();
    public $services = array();
    public $namespace = 'app\\services\\';

    public function __get($name)
    {
        if (isset($this->instances[$name]))
        {
            return $this->instances[$name];
        }
        elseif (isset($this->services[$name]) || $service = $this->initService($name))
        {
            return $service;
        }
        else
        {
            return parent::__get($name);
        }
    }

    protected function initService($name)
    {
        if (isset($this->services[$name]))
        {
            return $this->instances[$name] =  Yii::createObject($this->services[$name]);
        }
        else
        {
            try
            {
                return $this->instances[$name] = Yii::createObject(array('class' => $this->namespace.ucfirst($name)));
            }
            catch(\Exception $e)
            {
                Yii::warning("Service $name isn't allowed");
                return false;
            }
        }
    }
}
