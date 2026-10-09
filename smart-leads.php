<?php
/**
 * Plugin Name: Smart Leads
 * Description: Simple lead management system with WordPress admin CRUD, frontend lead form, AJAX and REST API.
 * Version: 1.0
 * Author: Sneha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/*
 * ========================================
 * 1. CREATE DATABASE TABLE
 * ========================================
 */

function smart_leads_create_table() {

    global $wpdb;

    $table_name      = $wpdb->prefix . 'smart_leads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(30) NOT NULL,
        company varchar(150) DEFAULT '',
        requirement text DEFAULT '',
        status varchar(30) NOT NULL DEFAULT 'New',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( $sql );
}

register_activation_hook(
    __FILE__,
    'smart_leads_create_table'
);


/*
 * ========================================
 * 2. ADMIN MENU
 * ========================================
 */

function smart_leads_admin_menu() {

    add_menu_page(
        'Smart Leads',
        'Smart Leads',
        'manage_options',
        'smart-leads',
        'smart_leads_dashboard_page',
        'dashicons-groups',
        25
    );

    add_submenu_page(
        'smart-leads',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'smart-leads',
        'smart_leads_dashboard_page'
    );

    add_submenu_page(
        'smart-leads',
        'Add Lead',
        'Add Lead',
        'manage_options',
        'smart-leads-add',
        'smart_leads_add_lead_page'
    );

    add_submenu_page(
        'smart-leads',
        'All Leads',
        'All Leads',
        'manage_options',
        'smart-leads-all',
        'smart_leads_all_leads_page'
    );
}

add_action(
    'admin_menu',
    'smart_leads_admin_menu'
);


/*
 * ========================================
 * 3. DASHBOARD
 * ========================================
 */

function smart_leads_dashboard_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            'You do not have permission to access this page.'
        );
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'smart_leads';

    $total_leads = $wpdb->get_var(
        "SELECT COUNT(*) FROM $table_name"
    );

    ?>

    <div class="wrap">

        <h1>Smart Leads Dashboard</h1>

        <p>
            Welcome to the Smart Leads management system.
        </p>

        <div
            style="
                background:#fff;
                padding:20px;
                margin-top:20px;
                max-width:300px;
                border:1px solid #ddd;
            "
        >

            <h2>Total Leads</h2>

            <p
                style="
                    font-size:32px;
                    font-weight:bold;
                "
            >
                <?php echo esc_html( $total_leads ); ?>
            </p>

        </div>

    </div>

    <?php
}


/*
 * ========================================
 * 4. ADD LEAD
 * ========================================
 */

function smart_leads_add_lead_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            'You do not have permission to access this page.'
        );
    }

    global $wpdb;

    $errors  = array();
    $success = '';

    $name        = '';
    $email       = '';
    $phone       = '';
    $company     = '';
    $requirement = '';
    $status      = 'New';

    $allowed_statuses = array(
        'New',
        'Contacted',
        'Qualified',
        'Converted',
        'Lost'
    );


    /*
     * Handle form submission
     */

    if ( isset( $_POST['smart_leads_submit'] ) ) {


        /*
         * Verify nonce
         */

        if (
            ! isset( $_POST['smart_leads_nonce'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['smart_leads_nonce']
                    )
                ),
                'smart_leads_add_lead'
            )
        ) {

            $errors[] =
                'Security check failed. Please try again.';
        }


        /*
         * Get form values
         */

        $name = isset( $_POST['name'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['name'] )
            )
            : '';

        $email = isset( $_POST['email'] )
            ? sanitize_email(
                wp_unslash( $_POST['email'] )
            )
            : '';

        $phone = isset( $_POST['phone'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['phone'] )
            )
            : '';

        $company = isset( $_POST['company'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['company'] )
            )
            : '';

        $requirement = isset( $_POST['requirement'] )
            ? sanitize_textarea_field(
                wp_unslash( $_POST['requirement'] )
            )
            : '';

        $status = isset( $_POST['status'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['status'] )
            )
            : 'New';


        /*
         * Validation
         */

        if ( empty( $name ) ) {
            $errors[] = 'Name is required.';
        }

        if (
            empty( $email ) ||
            ! is_email( $email )
        ) {
            $errors[] =
                'Please enter a valid email address.';
        }

        if (
            empty( $phone ) ||
            ! preg_match(
                '/^[0-9+\-\s()]{7,20}$/',
                $phone
            )
        ) {
            $errors[] =
                'Please enter a valid phone number.';
        }

        if (
            ! in_array(
                $status,
                $allowed_statuses,
                true
            )
        ) {
            $errors[] =
                'Invalid status selected.';
        }


        /*
         * Insert lead
         */

        if ( empty( $errors ) ) {

            $table_name =
                $wpdb->prefix . 'smart_leads';

            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'name'        => $name,
                    'email'       => $email,
                    'phone'       => $phone,
                    'company'     => $company,
                    'requirement' => $requirement,
                    'status'      => $status
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

                $errors[] =
                    'Unable to save the lead. Please try again.';

            } else {

                $success =
                    'Lead added successfully.';

                $name        = '';
                $email       = '';
                $phone       = '';
                $company     = '';
                $requirement = '';
                $status      = 'New';
            }
        }
    }

    ?>

    <div class="wrap">

        <h1>Add Lead</h1>


        <?php if ( ! empty( $errors ) ) : ?>

            <div class="notice notice-error">

                <?php foreach ( $errors as $error ) : ?>

                    <p>
                        <?php echo esc_html( $error ); ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <?php if ( ! empty( $success ) ) : ?>

            <div class="notice notice-success">

                <p>
                    <?php echo esc_html( $success ); ?>
                </p>

            </div>

        <?php endif; ?>


        <form method="post">

            <?php
            wp_nonce_field(
                'smart_leads_add_lead',
                'smart_leads_nonce'
            );
            ?>


            <table class="form-table">

                <tr>

                    <th>
                        <label for="name">
                            Name
                        </label>
                    </th>

                    <td>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="regular-text"
                            value="<?php echo esc_attr( $name ); ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="email">
                            Email
                        </label>
                    </th>

                    <td>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="regular-text"
                            value="<?php echo esc_attr( $email ); ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="phone">
                            Phone
                        </label>
                    </th>

                    <td>

                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="regular-text"
                            value="<?php echo esc_attr( $phone ); ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="company">
                            Company
                        </label>
                    </th>

                    <td>

                        <input
                            type="text"
                            name="company"
                            id="company"
                            class="regular-text"
                            value="<?php echo esc_attr( $company ); ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="requirement">
                            Requirement
                        </label>
                    </th>

                    <td>

                        <textarea
                            name="requirement"
                            id="requirement"
                            rows="5"
                            class="large-text"
                        ><?php echo esc_textarea( $requirement ); ?></textarea>

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="status">
                            Status
                        </label>
                    </th>

                    <td>

                        <select
                            name="status"
                            id="status"
                        >

                            <?php foreach ( $allowed_statuses as $allowed_status ) : ?>

                                <option
                                    value="<?php echo esc_attr( $allowed_status ); ?>"
                                    <?php selected(
                                        $status,
                                        $allowed_status
                                    ); ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $allowed_status
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </td>

                </tr>

            </table>


            <p>

                <button
                    type="submit"
                    name="smart_leads_submit"
                    class="button button-primary"
                >
                    Add Lead
                </button>

            </p>

        </form>

    </div>

    <?php
}


/*
 * ========================================
 * 5. ALL LEADS
 * ========================================
 */

function smart_leads_all_leads_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            'You do not have permission to access this page.'
        );
    }

    global $wpdb;

    $table_name =
        $wpdb->prefix . 'smart_leads';


    /*
     * Handle delete
     */

    if ( isset( $_GET['delete_lead'] ) ) {

        if (
            ! isset( $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_GET['_wpnonce']
                    )
                ),
                'smart_leads_delete_lead'
            )
        ) {

            wp_die(
                'Security check failed.'
            );
        }


        $lead_id = absint(
            $_GET['delete_lead']
        );


        if ( $lead_id > 0 ) {

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

                echo '<div class="notice notice-error"><p>Unable to delete the lead.</p></div>';

            } else {

                echo '<div class="notice notice-success"><p>Lead deleted successfully.</p></div>';
            }
        }
    }


    /*
     * Get all leads
     */

    $leads = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY id DESC"
    );

    ?>

    <div class="wrap">

        <h1>All Leads</h1>


        <table class="widefat fixed striped">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th>Requirement</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

                <?php if ( ! empty( $leads ) ) : ?>

                    <?php foreach ( $leads as $lead ) : ?>

                        <tr>

                            <td>
                                <?php
                                echo esc_html(
                                    $lead->id
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $lead->name
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $lead->email
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $lead->phone
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $lead->company
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $lead->requirement
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $lead->status
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $lead->created_at
                                );
                                ?>
                            </td>

                            <td>

                                <a
                                    href="<?php echo esc_url(
                                        admin_url(
                                            'admin.php?page=smart-leads-edit&lead_id=' .
                                            absint( $lead->id )
                                        )
                                    ); ?>"
                                >
                                    Edit
                                </a>

                                |

                                <a
                                    href="<?php echo esc_url(
                                        wp_nonce_url(
                                            admin_url(
                                                'admin.php?page=smart-leads-all&delete_lead=' .
                                                absint( $lead->id )
                                            ),
                                            'smart_leads_delete_lead'
                                        )
                                    ); ?>"
                                    onclick="return confirm('Are you sure you want to delete this lead?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else : ?>

                    <tr>

                        <td colspan="9">
                            No leads found.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <?php
}


/*
 * ========================================
 * 6. EDIT LEAD
 * ========================================
 */

function smart_leads_edit_lead_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            'You do not have permission to access this page.'
        );
    }

    global $wpdb;

    $table_name =
        $wpdb->prefix . 'smart_leads';


    /*
     * Get lead ID
     */

    $lead_id = isset( $_GET['lead_id'] )
        ? absint( $_GET['lead_id'] )
        : 0;


    if ( $lead_id <= 0 ) {

        echo '<div class="wrap">';
        echo '<div class="notice notice-error"><p>Invalid lead ID.</p></div>';
        echo '</div>';

        return;
    }


    /*
     * Get lead
     */

    $lead = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $lead_id
        )
    );


    if ( ! $lead ) {

        echo '<div class="wrap">';
        echo '<div class="notice notice-error"><p>Lead not found.</p></div>';
        echo '</div>';

        return;
    }


    $errors  = array();
    $success = '';

    $name        = $lead->name;
    $email       = $lead->email;
    $phone       = $lead->phone;
    $company     = $lead->company;
    $requirement = $lead->requirement;
    $status      = $lead->status;

    $allowed_statuses = array(
        'New',
        'Contacted',
        'Qualified',
        'Converted',
        'Lost'
    );


    /*
     * Handle update
     */

    if ( isset( $_POST['smart_leads_update'] ) ) {


        /*
         * Verify nonce
         */

        if (
            ! isset( $_POST['smart_leads_edit_nonce'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['smart_leads_edit_nonce']
                    )
                ),
                'smart_leads_edit_lead'
            )
        ) {

            $errors[] =
                'Security check failed. Please try again.';
        }


        /*
         * Get updated values
         */

        $name = isset( $_POST['name'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['name'] )
            )
            : '';

        $email = isset( $_POST['email'] )
            ? sanitize_email(
                wp_unslash( $_POST['email'] )
            )
            : '';

        $phone = isset( $_POST['phone'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['phone'] )
            )
            : '';

        $company = isset( $_POST['company'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['company'] )
            )
            : '';

        $requirement = isset( $_POST['requirement'] )
            ? sanitize_textarea_field(
                wp_unslash( $_POST['requirement'] )
            )
            : '';

        $status = isset( $_POST['status'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['status'] )
            )
            : 'New';


        /*
         * Validation
         */

        if ( empty( $name ) ) {
            $errors[] =
                'Name is required.';
        }

        if (
            empty( $email ) ||
            ! is_email( $email )
        ) {
            $errors[] =
                'Please enter a valid email address.';
        }

        if (
            empty( $phone ) ||
            ! preg_match(
                '/^[0-9+\-\s()]{7,20}$/',
                $phone
            )
        ) {
            $errors[] =
                'Please enter a valid phone number.';
        }

        if (
            ! in_array(
                $status,
                $allowed_statuses,
                true
            )
        ) {
            $errors[] =
                'Invalid status selected.';
        }


        /*
         * Update database
         */

        if ( empty( $errors ) ) {

            $updated = $wpdb->update(
                $table_name,
                array(
                    'name'        => $name,
                    'email'       => $email,
                    'phone'       => $phone,
                    'company'     => $company,
                    'requirement' => $requirement,
                    'status'      => $status
                ),
                array(
                    'id' => $lead_id
                ),
                array(
                    '%s',
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

                $errors[] =
                    'Unable to update the lead. Please try again.';

            } else {

                $success =
                    'Lead updated successfully.';
            }
        }
    }

    ?>

    <div class="wrap">

        <h1>Edit Lead</h1>


        <?php if ( ! empty( $errors ) ) : ?>

            <div class="notice notice-error">

                <?php foreach ( $errors as $error ) : ?>

                    <p>
                        <?php echo esc_html( $error ); ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <?php if ( ! empty( $success ) ) : ?>

            <div class="notice notice-success">

                <p>
                    <?php echo esc_html( $success ); ?>
                </p>

            </div>

        <?php endif; ?>


        <form method="post">

            <?php
            wp_nonce_field(
                'smart_leads_edit_lead',
                'smart_leads_edit_nonce'
            );
            ?>


            <table class="form-table">

                <tr>

                    <th>
                        <label for="edit-name">
                            Name
                        </label>
                    </th>

                    <td>

                        <input
                            type="text"
                            name="name"
                            id="edit-name"
                            class="regular-text"
                            value="<?php echo esc_attr( $name ); ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="edit-email">
                            Email
                        </label>
                    </th>

                    <td>

                        <input
                            type="email"
                            name="email"
                            id="edit-email"
                            class="regular-text"
                            value="<?php echo esc_attr( $email ); ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="edit-phone">
                            Phone
                        </label>
                    </th>

                    <td>

                        <input
                            type="text"
                            name="phone"
                            id="edit-phone"
                            class="regular-text"
                            value="<?php echo esc_attr( $phone ); ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="edit-company">
                            Company
                        </label>
                    </th>

                    <td>

                        <input
                            type="text"
                            name="company"
                            id="edit-company"
                            class="regular-text"
                            value="<?php echo esc_attr( $company ); ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="edit-requirement">
                            Requirement
                        </label>
                    </th>

                    <td>

                        <textarea
                            name="requirement"
                            id="edit-requirement"
                            rows="5"
                            class="large-text"
                        ><?php echo esc_textarea( $requirement ); ?></textarea>

                    </td>

                </tr>


                <tr>

                    <th>
                        <label for="edit-status">
                            Status
                        </label>
                    </th>

                    <td>

                        <select
                            name="status"
                            id="edit-status"
                        >

                            <?php foreach ( $allowed_statuses as $allowed_status ) : ?>

                                <option
                                    value="<?php echo esc_attr( $allowed_status ); ?>"
                                    <?php selected(
                                        $status,
                                        $allowed_status
                                    ); ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $allowed_status
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </td>

                </tr>

            </table>


            <p>

                <button
                    type="submit"
                    name="smart_leads_update"
                    class="button button-primary"
                >
                    Update Lead
                </button>

            </p>

        </form>

    </div>

    <?php
}


/*
 * Register Edit Lead submenu
 */

function smart_leads_register_edit_submenu() {

    add_submenu_page(
        null,
        'Edit Lead',
        'Edit Lead',
        'manage_options',
        'smart-leads-edit',
        'smart_leads_edit_lead_page'
    );
}

add_action(
    'admin_menu',
    'smart_leads_register_edit_submenu'
);


/*
 * ========================================
 * 7. FRONTEND JAVASCRIPT
 * ========================================
 */

function smart_leads_enqueue_scripts() {

    /*
     * Main plugin file is located directly inside:
     *
     * wp-content/plugins/smart-leads.php
     *
     * JavaScript file is located inside:
     *
     * wp-content/plugins/smart-leads/js/smart-leads.js
     */

    $smart_leads_js_url =
        plugin_dir_url( __FILE__ ) .
        'smart-leads/js/smart-leads.js';


    wp_enqueue_script(
        'smart-leads-js',
        $smart_leads_js_url,
        array( 'jquery' ),
        '1.0',
        true
    );


    wp_localize_script(
        'smart-leads-js',
        'smartLeads',
        array(
            'ajax_url' => admin_url(
                'admin-ajax.php'
            ),
            'nonce'    => wp_create_nonce(
                'smart_leads_ajax_nonce'
            )
        )
    );
}

add_action(
    'wp_enqueue_scripts',
    'smart_leads_enqueue_scripts'
);


/*
 * ========================================
 * 8. FRONTEND LEAD FORM SHORTCODE
 * ========================================
 */

function smart_leads_frontend_form() {

    ob_start();

    ?>

    <div class="smart-leads-frontend-form">


        <!-- AJAX MESSAGE -->

        <div
            id="smart-leads-message"
            style="margin-bottom:15px;"
        ></div>


        <form
            method="post"
            id="smart-leads-form"
        >


            <p>

                <label for="smart-leads-name">
                    Name *
                </label>

                <br>

                <input
                    type="text"
                    id="smart-leads-name"
                    name="name"
                    required
                >

            </p>


            <p>

                <label for="smart-leads-email">
                    Email *
                </label>

                <br>

                <input
                    type="email"
                    id="smart-leads-email"
                    name="email"
                    required
                >

            </p>


            <p>

                <label for="smart-leads-phone">
                    Phone *
                </label>

                <br>

                <input
                    type="text"
                    id="smart-leads-phone"
                    name="phone"
                    required
                >

            </p>


            <p>

                <label for="smart-leads-company">
                    Company
                </label>

                <br>

                <input
                    type="text"
                    id="smart-leads-company"
                    name="company"
                >

            </p>


            <p>

                <label for="smart-leads-requirement">
                    Requirement
                </label>

                <br>

                <textarea
                    id="smart-leads-requirement"
                    name="requirement"
                    rows="5"
                ></textarea>

            </p>


            <p>

                <button
                    type="submit"
                    name="smart_leads_frontend_submit"
                    value="1"
                >
                    Submit Lead
                </button>

            </p>


        </form>

    </div>

    <?php

    return ob_get_clean();
}


/*
 * ========================================
 * 9. OLD FRONTEND FORM HANDLER
 * ========================================
 *
 * Kept as a fallback.
 */

function smart_leads_handle_frontend_submit() {


    /*
     * Check nonce
     */

    if (
        ! isset(
            $_POST['smart_leads_frontend_nonce']
        ) ||
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['smart_leads_frontend_nonce']
                )
            ),
            'smart_leads_frontend_submit'
        )
    ) {

        wp_die(
            'Security check failed. Please go back and try again.'
        );
    }


    global $wpdb;


    /*
     * Get and sanitize submitted values
     */

    $name = isset( $_POST['name'] )
        ? sanitize_text_field(
            wp_unslash( $_POST['name'] )
        )
        : '';

    $email = isset( $_POST['email'] )
        ? sanitize_email(
            wp_unslash( $_POST['email'] )
        )
        : '';

    $phone = isset( $_POST['phone'] )
        ? sanitize_text_field(
            wp_unslash( $_POST['phone'] )
        )
        : '';

    $company = isset( $_POST['company'] )
        ? sanitize_text_field(
            wp_unslash( $_POST['company'] )
        )
        : '';

    $requirement = isset( $_POST['requirement'] )
        ? sanitize_textarea_field(
            wp_unslash( $_POST['requirement'] )
        )
        : '';


    /*
     * Validation
     */

    $errors = array();


    if ( empty( $name ) ) {

        $errors[] =
            'Name is required.';
    }


    if (
        empty( $email ) ||
        ! is_email( $email )
    ) {

        $errors[] =
            'Please enter a valid email address.';
    }


    if (
        empty( $phone ) ||
        ! preg_match(
            '/^[0-9+\-\s()]{7,20}$/',
            $phone
        )
    ) {

        $errors[] =
            'Please enter a valid phone number.';
    }


    /*
     * If validation fails
     */

    if ( ! empty( $errors ) ) {

        wp_die(
            esc_html(
                implode(
                    ' ',
                    $errors
                )
            )
        );
    }


    /*
     * Database table
     */

    $table_name =
        $wpdb->prefix . 'smart_leads';


    /*
     * Insert lead
     */

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


    /*
     * Check database result
     */

    if ( false === $inserted ) {

        wp_die(
            'Unable to submit your request. Please try again.'
        );
    }


    /*
     * Redirect user back to lead form
     */

    $redirect_url =
        wp_get_referer();


    if ( ! $redirect_url ) {

        $redirect_url =
            home_url( '/' );
    }


    $redirect_url =
        add_query_arg(
            'lead_submitted',
            'success',
            $redirect_url
        );


    wp_safe_redirect(
        $redirect_url
    );

    exit;
}


/*
 * ========================================
 * 10. REGISTER OLD FRONTEND HANDLER
 * ========================================
 */

add_action(
    'admin_post_smart_leads_frontend_submit',
    'smart_leads_handle_frontend_submit'
);

add_action(
    'admin_post_nopriv_smart_leads_frontend_submit',
    'smart_leads_handle_frontend_submit'
);


/*
 * ========================================
 * 11. AJAX FORM HANDLER
 * ========================================
 */

function smart_leads_ajax_submit() {


    /*
     * Verify AJAX nonce
     */

    if (
        ! isset( $_POST['nonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['nonce']
                )
            ),
            'smart_leads_ajax_nonce'
        )
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Security check failed.'
            )
        );
    }


    global $wpdb;


    /*
     * Get form values
     */

    $name = isset( $_POST['name'] )
        ? sanitize_text_field(
            wp_unslash( $_POST['name'] )
        )
        : '';

    $email = isset( $_POST['email'] )
        ? sanitize_email(
            wp_unslash( $_POST['email'] )
        )
        : '';

    $phone = isset( $_POST['phone'] )
        ? sanitize_text_field(
            wp_unslash( $_POST['phone'] )
        )
        : '';

    $company = isset( $_POST['company'] )
        ? sanitize_text_field(
            wp_unslash( $_POST['company'] )
        )
        : '';

    $requirement = isset( $_POST['requirement'] )
        ? sanitize_textarea_field(
            wp_unslash( $_POST['requirement'] )
        )
        : '';


    /*
     * Validation
     */

    if ( empty( $name ) ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Name is required.'
            )
        );
    }


    if (
        empty( $email ) ||
        ! is_email( $email )
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Please enter a valid email address.'
            )
        );
    }


    if (
        empty( $phone ) ||
        ! preg_match(
            '/^[0-9+\-\s()]{7,20}$/',
            $phone
        )
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Please enter a valid phone number.'
            )
        );
    }


    /*
     * Database table
     */

    $table_name =
        $wpdb->prefix . 'smart_leads';


    /*
     * Insert lead
     */

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


    /*
     * Check database result
     */

    if ( false === $inserted ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Unable to save the lead. Please try again.'
            )
        );
    }


    /*
     * Success response
     */

    wp_send_json_success(
        array(
            'message' =>
                'Thank you! Your lead has been submitted successfully.'
        )
    );
}


/*
 * ========================================
 * 12. REGISTER AJAX ACTIONS
 * ========================================
 */


/*
 * Logged-in users
 */

add_action(
    'wp_ajax_smart_leads_ajax_submit',
    'smart_leads_ajax_submit'
);


/*
 * Visitors who are NOT logged in
 */

add_action(
    'wp_ajax_nopriv_smart_leads_ajax_submit',
    'smart_leads_ajax_submit'
);


/*
 * ========================================
 * 13. LOAD REST API FILE
 * ========================================
 *
 * IMPORTANT:
 *
 * smart-leads.php is directly inside:
 *
 * wp-content/plugins/
 *
 * The REST API file is inside:
 *
 * wp-content/plugins/smart-leads/api/
 *
 */

require_once plugin_dir_path( __FILE__ )
    . 'smart-leads/api/smart-leads-api.php';


/*
 * ========================================
 * 14. REGISTER SHORTCODE
 * ========================================
 */

add_shortcode(
    'smart_leads_form',
    'smart_leads_frontend_form'
);