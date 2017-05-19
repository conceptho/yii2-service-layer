<?php

namespace conceptho\ServiceLayer;

class Response extends \yii\base\Object implements \IteratorAggregate, \ArrayAccess, \Countable {
	use \yii\base\ArrayAccessTrait;

	public $isOk;
	public $data;

	public function __construct( $isOk, $data, $config = array() ) {
		parent::__construct( array_merge( [ 'isOk' => $isOk, 'data' => $data ], $config ) );
	}
}