<?php
/**
 * Functions for REST API endpoints.
 *
 * @since 1.6.0
 * @package ucare
 */
namespace ucare;

// Register endpoints
add_action( 'rest_api_init', 'ucare\rest_register_endpoints' );


/**
 * Register custom endpoints with the REST API.
 *
 * @action rest_api_init
 *
 * @since 1.6.0
 * @return void
 */
function rest_register_endpoints() {
    /**
     * User registration endpoint.
     *
     * @since 1.6.0
     */
    register_rest_route( 'ucare/v1', 'users/register', array(
        'methods'  => \WP_REST_Server::CREATABLE,
        'callback' => function ( $request ) {
            increment_ip_request_count();
            if ( is_ip_blocked() ) {
                return too_many_attempts_error();
            }
            return rest_handler_register_user( $request );
        },
        'permission_callback' => function () {
            return verify_request_nonce( 'ucare_rest', '_ucarenonce' );
        }
    ) );

    /**
     * Reset password endpoint.
     *
     * @since 1.6.0
     */
    register_rest_route( 'ucare/v1', 'auth/reset-password', array(
        'methods'  => \WP_REST_Server::CREATABLE,
        'callback' => function ( $request ) {
            increment_ip_request_count();
            if ( is_ip_blocked() ) {
                return too_many_attempts_error();
            }
            return rest_handler_rest_password( $request );
        },
        'permission_callback' => function () {
            return verify_request_nonce( 'ucare_rest', '_ucarenonce' );
        }
    ) );

    /**
     * Extension licenses endpoint.
     *
     * @since 1.6.0
     */
    register_rest_route( 'ucare/v1', 'extensions/licenses/(?P<id>([0-9a-z-_])+)', array(
        array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => fqn( 'rest_manage_extension_license' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            }
        ),
        array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => fqn( 'rest_check_extension_license' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            }
        )
    ) );

    /**
     * Current user endpoints
     *
     * @since 1.7.0
     */
    register_rest_route( 'ucare/v1', 'users/me/authenticate', array(
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => 'ucare\rest_register_user',
        'args'                => array(
            'step'       => array( 'required' => true ),
            'email'      => array( 'sanitize_callback' => 'sanitize_email' ),
            'first_name' => array( 'sanitize_callback' => 'sanitize_text_field' ),
            'last_name'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
            'password'   => array( 'sanitize_callback' => 'sanitize_text_field' )
        ),
        'permission_callback' => function ( \WP_REST_Request $request ) {
            $nonce = $request->get_header( 'X-WP-Nonce' );

            if ( empty( $nonce ) || !wp_verify_nonce( $nonce, 'wp_rest' ) ) {
                return false;
            }
            return true;
        }
    ) );
}

/**
 * Handle the registration form
 *
 * @param \WP_REST_Request $request
 *
 * @since 1.7.0
 * @return mixed
 */
function rest_register_user( $request ) {
    $step  = $request->get_param( 'step' );
    $steps = array(
        'email',
        'terms',
        'profile',
        'password'
    );

    if ( !in_array( $step, $steps ) ) {
        return new \WP_Error( 'invalid_step', __( 'Invalid step', 'ucare' ), array( 'code' => 400 ) );
    }

    $email = $request->get_param( 'email' );

    switch ( $step ) {

        /**
         * Look up the user by email, if they don't exist create them
         */
        case 'email':
            $user = get_user_by( 'email', $email );

            /**
             * Verify Terms of service
             */
            if ( empty( $user ) && get_option( 'terms', true ) ) {
                $terms = $request->get_param( 'terms' );

                if ( !empty( $terms ) && $terms === 'decline' ) {
                    $response = array(
                        'type'   => 'screen',
                        'screen' => 'email'
                    );
                    return rest_ensure_response( $response );
                } else if ( empty( $terms ) ) {
                    $response = array(
                        'type'   => 'screen',
                        'screen' => 'terms',
                        'data'   => array( 'email' => $email )
                    );
                    return rest_ensure_response( $response );
                }
            }

            // Register user
            if ( empty( $user ) ) {
                $data = array(
                    'email' => $email
                );
                $user = ucare_register_user( $data );

                if ( is_wp_error( $user ) ) {
                    return $user;
                }
                wp_set_current_user( $user );

                add_action( 'set_logged_in_cookie', function ( $logged_in_cookie ) {
                   $_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie; // Force update auth cookie
                });

                if ( wp_validate_auth_cookie( '', 'logged_in' ) != $user ) {
                    wp_set_auth_cookie( $user, true );
                }

                $response = array(
                    'type'   => 'screen',
                    'screen' => 'profile',
                    'nonce'  => wp_create_nonce( 'wp_rest' )
                );
            } else {
                $response = array(
                    'type'   => 'screen',
                    'screen' => 'password',
                    'data'   => array( 'log' => $user->user_login )
                );
            }
            return rest_ensure_response( $response );

        /**
         * Update user profile info
         */
        case 'profile':
            $user = array(
                'ID'         => get_current_user_id(),
                'first_name' => $request->get_param( 'first_name' ),
                'last_name'  => $request->get_param( 'last_name' )
            );
            $updated = wp_update_user( (object) $user );

            if ( is_wp_error( $updated ) ) {
                return $updated;
            }

            $response = array(
                'type'  => 'redirect',
                'to'    => create_page_url()
            );
            return rest_ensure_response( $response );

        /**
         * Verify the user's password
         */
        case 'password':
            $user = wp_signon();

            if ( is_wp_error( $user ) ) {
                return $user;
            }

            $response = array(
                'type'  => 'redirect',
                'to'    => create_page_url()
            );
            return rest_ensure_response( $response );
    }

    return new \WP_Error( 'unkown_error', __( 'An unknown error has occurred. Please try again later.', 'ucare' ), array( 'code' => 500 ) );
}

/**
 * Handler for the user registration endpoint.
 *
 * @param \WP_REST_Request $request
 *
 * @since 1.6.0
 * @return mixed
 */
function rest_handler_register_user( $request ) {
    $user = ucare_register_user( $request->get_params(), true );

    if ( is_wp_error( $user ) ) {
        return $user;
    }

    $response = new \WP_REST_Response();
    $response->set_status( 201 );

    return $response;
}


/**
 * Handler for the reset password endpoint.
 *
 * @param \WP_REST_Request $request
 *
 * @since 1.6.0
 * @return mixed
 */
function rest_handler_rest_password( $request ) {
    $reset = ucare_reset_user_password( $request->get_param( 'username' ) );

    if ( is_wp_error( $reset ) ) {
        return $reset;
    }

    $data = array(
        'message' => __( 'Password reset, a temporary password has been sent to your email', 'ucare' )
    );

    return new \WP_REST_Response( $data, 200 );
}


/**
 * Handler to manage license activations and deactivations.
 *
 * @param \WP_REST_Request $request
 *
 * @since 1.6.0
 * @return mixed
 */
function rest_manage_extension_license( $request ) {
    $id = $request->get_param( 'id' );

    if ( empty( $id ) ) {
        return new \WP_Error( 'invalid_product', __( 'Invalid product', 'ucare' ), array( 'status' => 404 ) );
    }

    $manager = ucare_get_license_manager();

    switch ( $request->get_param( 'action' ) ) {
        case 'activate':
            $success = $manager->activate_license( $id, $request->get_param( 'key' ) );

            if ( is_wp_error( $success ) ) {
                return $success;
            }

            return current( get_licensing_data( $id ) );

        case 'deactivate':
            $success = $manager->deactivate_license( $id );

            if ( is_wp_error( $success ) ) {
                return $success;
            }

            return current( get_licensing_data( $id ) );
    }

    return new \WP_Error( 'invalid_action', __( 'Invalid action', 'ucare' ), array( 'status' => 400 ) );
}


/**
 * Check the current status of an extension license.
 *
 * @param \WP_REST_Request $request
 *
 * @since 1.6.0
 * @return mixed
 */
function rest_check_extension_license( $request ) {
    $manager = ucare_get_license_manager();

    $data = $manager->get_license( $request->get_param( 'id' ) );

    if ( is_wp_error( $data ) ) {
        return $data;
    }

    if ( !$data ) {
        return new \WP_Error( 'invalid_product', __( 'Invalid product', 'ucare' ), array( 'status' => 404 ) );
    }

    return $data;
}