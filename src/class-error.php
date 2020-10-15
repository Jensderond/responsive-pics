<?php

class RP_Error extends ResponsivePics {
	// add to error
	public function add_error($code = 'error', $message = '', $data = null) {
		self::$wp_error->add($code, $message, $data);
		return self::$wp_error;
	}

	// get errors
	public function get_error($error = null) {
		if (is_wp_error($error)) {
			if (ResponsivePics()->helpers->is_rest_api_request()) {
				return $error;
			} else {
				$this->show_error($error);
			}
		} else {
			return $error;
		}
	}

	// display error messages
	public function show_error($error) {
		var_dump($error);
		// $error = sprintf('<pre>%s error: %s</pre>', get_parent_class(), $message);
		// echo $error;
	}
}
