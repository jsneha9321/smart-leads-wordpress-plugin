
<?php

/*
 * ========================================
 * SMART LEADS REST API
 * ========================================
 */


/*
 * ========================================
 * 1. GET ALL LEADS
 * ========================================
 */

function smart_leads_get_all_leads( $request ) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'smart_leads';

    $leads = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY id DESC",
        ARRAY_A
    );

    if ( null === $leads ) {
        return new WP_Error(
            'smart_leads_database_error',
            'Unable to retrieve leads.',
            array( 'status' => 500 )
        );
    }

    return rest_ensure_response( $leads );
}


/*
 * ========================================
 * 2. GET SINGLE LEAD
 * ========================================
 */

function smart_leads_get_single_lead( $request ) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'smart_leads';

    $lead_id = absint(
        $request->get_param( 'id' )
    );

    if ( empty( $lead_id ) ) {
        return new WP_Error(
            'invalid_lead_id',
            'Invalid lead ID.',
            array( 'status' => 400 )
        );
    }

    $lead = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $lead_id
        ),
        ARRAY_A
    );

    if ( null === $lead ) {
        return new WP_Error(
            'lead_not_found',
            'Lead not found.',
            array( 'status' => 404 )
        );
    }

    return rest_ensure_response( $lead );
}


/*
 * ========================================
 * 3. CREATE NEW LEAD - POST
 * ========================================
 */

function smart_leads_create_lead( $request ) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'smart_leads';

    $data = $request->get_json_params();

    $data = is_array( $data ) ? $data : array();

    $name = isset( $data['name'] )
        ? sanitize_text_field( $data['name'] )
        : '';

    $email = isset( $data['email'] )
        ? sanitize_email( $data['email'] )
        : '';

    $phone = isset( $data['phone'] )
        ? sanitize_text_field( $data['phone'] )
        : '';

    $company = isset( $data['company'] )
        ? sanitize_text_field( $data['company'] )
        : '';

    $requirement = isset( $data['requirement'] )
        ? sanitize_textarea_field( $data['requirement'] )
        : '';

    if ( empty( $name ) ) {
        return new WP_Error(
            'name_required',
            'Name is required.',
            array( 'status' => 400 )
        );
    }

    if ( empty( $email ) || ! is_email( $email ) ) {
        return new WP_Error(
            'invalid_email',
            'Please enter a valid email address.',
            array( 'status' => 400 )
        );
    }

    if (
        empty( $phone ) ||
        ! preg_match( '/^[0-9+\-\s()]{7,20}$/', $phone )
    ) {
        return new WP_Error(
            'invalid_phone',
            'Please enter a valid phone number.',
            array( 'status' => 400 )
        );
    }

    $inserted = $wpdb->insert(
        $table_name,
        array(
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'company'     => $company,
            'requirement' => $requirement,
            'status'      => 'New'
        ),
        array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        )
    );

    if ( false === $inserted ) {
        return new WP_Error(
            'database_error',
            'Unable to create lead.',
            array( 'status' => 500 )
        );
    }

    $lead_id = $wpdb->insert_id;

    $lead = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $lead_id
        ),
        ARRAY_A
    );

    return new WP_REST_Response(
        $lead,
        201
    );
}


/*
 * ========================================
 * 4. UPDATE EXISTING LEAD - PUT
 * ========================================
 */

function smart_leads_update_lead( $request ) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'smart_leads';

    $lead_id = absint(
        $request->get_param( 'id' )
    );

    if ( empty( $lead_id ) ) {
        return new WP_Error(
            'invalid_lead_id',
            'Invalid lead ID.',
            array( 'status' => 400 )
        );
    }

    $existing_lead = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $lead_id
        ),
        ARRAY_A
    );

    if ( null === $existing_lead ) {
        return new WP_Error(
            'lead_not_found',
            'Lead not found.',
            array( 'status' => 404 )
        );
    }

    $data = $request->get_json_params();

    $data = is_array( $data ) ? $data : array();

    $name = isset( $data['name'] )
        ? sanitize_text_field( $data['name'] )
        : '';

    $email = isset( $data['email'] )
        ? sanitize_email( $data['email'] )
        : '';

    $phone = isset( $data['phone'] )
        ? sanitize_text_field( $data['phone'] )
        : '';

    $company = isset( $data['company'] )
        ? sanitize_text_field( $data['company'] )
        : '';

    $requirement = isset( $data['requirement'] )
        ? sanitize_textarea_field( $data['requirement'] )
        : '';

    if ( empty( $name ) ) {
        return new WP_Error(
            'name_required',
            'Name is required.',
            array( 'status' => 400 )
        );
    }

    if ( empty( $email ) || ! is_email( $email ) ) {
        return new WP_Error(
            'invalid_email',
            'Please enter a valid email address.',
            array( 'status' => 400 )
        );
    }

    if (
        empty( $phone ) ||
        ! preg_match( '/^[0-9+\-\s()]{7,20}$/', $phone )
    ) {
        return new WP_Error(
            'invalid_phone',
            'Please enter a valid phone number.',
            array( 'status' => 400 )
        );
    }

    $updated = $wpdb->update(
        $table_name,
        array(
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'company'     => $company,
            'requirement' => $requirement
        ),
        array(
            'id' => $lead_id
        ),
        array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        ),
        array(
            '%d'
        )
    );

    if ( false === $updated ) {
        return new WP_Error(
            'database_error',
            'Unable to update lead.',
            array( 'status' => 500 )
        );
    }

    $lead = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $lead_id
        ),
        ARRAY_A
    );

    return new WP_REST_Response(
        $lead,
        200
    );
}


/*
 * ========================================
 * 5. DELETE EXISTING LEAD - DELETE
 * ========================================
 */

function smart_leads_delete_lead( $request ) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'smart_leads';

    $lead_id = absint(
        $request->get_param( 'id' )
    );

    if ( empty( $lead_id ) ) {
        return new WP_Error(
            'invalid_lead_id',
            'Invalid lead ID.',
            array( 'status' => 400 )
        );
    }

    $existing_lead = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $lead_id
        ),
        ARRAY_A
    );

    if ( null === $existing_lead ) {
        return new WP_Error(
            'lead_not_found',
            'Lead not found.',
            array( 'status' => 404 )
        );
    }

    $deleted = $wpdb->delete(
        $table_name,
        array(
            'id' => $lead_id
        ),
        array(
            '%d'
        )
    );

    if ( false === $deleted ) {
        return new WP_Error(
            'database_error',
            'Unable to delete lead.',
            array( 'status' => 500 )
        );
    }

    return new WP_REST_Response(
        array(
            'success'    => true,
            'message'    => 'Lead deleted successfully.',
            'deleted_id' => $lead_id
        ),
        200
    );
}


/*
 * ========================================
 * 6. REGISTER REST API ROUTES
 * ========================================
 */

function smart_leads_register_rest_routes() {

    /*
     * GET ALL LEADS
     * GET /wp-json/smart-leads/v1/leads
     *
     * Public read access for this local learning project.
     */

    register_rest_route(
        'smart-leads/v1',
        '/leads',
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'smart_leads_get_all_leads',
            'permission_callback' => '__return_true',
        )
    );

    /*
     * GET SINGLE LEAD
     * GET /wp-json/smart-leads/v1/leads/8
     */

    register_rest_route(
        'smart-leads/v1',
        '/leads/(?P<id>\d+)',
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'smart_leads_get_single_lead',
            'permission_callback' => '__return_true',
        )
    );

    /*
     * CREATE NEW LEAD
     * POST /wp-json/smart-leads/v1/leads
     */

    register_rest_route(
        'smart-leads/v1',
        '/leads',
        array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => 'smart_leads_create_lead',
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        )
    );

    /*
     * UPDATE EXISTING LEAD
     * PUT /wp-json/smart-leads/v1/leads/8
     */

    register_rest_route(
        'smart-leads/v1',
        '/leads/(?P<id>\d+)',
        array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'smart_leads_update_lead',
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        )
    );

    /*
     * DELETE EXISTING LEAD
     * DELETE /wp-json/smart-leads/v1/leads/8
     */

    register_rest_route(
        'smart-leads/v1',
        '/leads/(?P<id>\d+)',
        array(
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => 'smart_leads_delete_lead',
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        )
    );
}


/*
 * ========================================
 * 7. REGISTER REST API
 * ========================================
 */

add_action(
    'rest_api_init',
    'smart_leads_register_rest_routes'
);