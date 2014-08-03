<?php

/**
 * A simple controller which gets pinged by a {@link UserForm} to prevent the
 * form from timing out.
 *
 * @package userforms
 */
class UserFormsPingController extends Controller {
	
	private static $allowed_actions = array(
		'index'
	);


	/**
	 * Keep the session alive for the user.
	 *
	 * @return int
	 */
	public function index() {
		return 1;
	}
}