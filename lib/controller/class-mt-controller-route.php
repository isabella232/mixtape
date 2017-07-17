<?php
/**
 * A Route that is part of a controller.
 *
 * @package MT/Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MT_Controller_Route
 */
class MT_Controller_Route {

	/**
	 * Our pattern
	 *
	 * @var string
	 */
	private $pattern;

	/**
	 * Our Handlers
	 *
	 * @var array
	 */
	private $actions;

	/**
	 * Our Controller
	 *
	 * @var MT_Controller
	 */
	private $controller;

	/**
	 * HTTP Methods
	 *
	 * @var array
	 */
	private $http_methods;

	/**
	 * MT_Controller_Route constructor.
	 *
	 * @param MT_Controller $controller A Controller.
	 * @param string        $pattern Pattern.
	 */
	public function __construct( $controller, $pattern ) {
		$this->controller = $controller;
		$this->pattern = $pattern;
		$this->actions = array();
		$this->http_methods = explode( ', ', WP_REST_Server::ALLMETHODS );
	}

	/**
	 * Add/Get an action
	 *
	 * @param MT_Controller_Action $action Action.
	 *
	 * @return MT_Controller_Route
	 */
	public function add_action( $action ) {
		$this->actions[ $action->name() ] = $action;
		return $this;
	}

	/**
	 * Gets Route info to use in Register rest route.
	 *
	 * @throws MT_Exception If invalid callable.
	 * @return array
	 */
	public function as_array() {
		$result = array();
		$result['pattern'] = $this->pattern;
		$result['actions'] = array();
		foreach ( $this->actions as $action => $route_action ) {
			/**
			 * The route action.
			 *
			 * @var MT_Controller_Action $route_action
			 */
			$result['actions'][] = $route_action->as_array();
		}
		return $result;
	}
}
