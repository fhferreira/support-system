<?php
/**
 * Functions for controlling the sidebar in single ticket view.
 *
 * @since 1.4.2
 * @package ucare
 */
namespace ucare;


// Get the eCommerce sidebar
add_action( 'ucare_ticket_sidebar_purchase-details', 'ucare\get_ecommerce_sidebar' );

// Get the customer sidebar
add_action( 'ucare_ticket_sidebar_customer-details', 'ucare\get_customer_sidebar' );

// Get the attachments sidebar
add_action( 'ucare_ticket_sidebar_attachments', 'ucare\get_attachments_sidebar' );

// Get the properties sidebar
add_action( 'ucare_ticket_sidebar_ticket-properties', 'ucare\get_properties_sidebar' );


/**
 * Action to pull in the default eCommerce sidebar.
 *
 * @param \WP_Post $ticket The current ticket.
 *
 * @action ucare_ticket_sidebar_purchase-details
 *
 * @since 1.4.2
 * @return void
 */
function get_ecommerce_sidebar( \WP_Post $ticket ) {

    $product = get_post( get_post_meta( $ticket->ID, 'product', true ) ?: 0 );

    $args = array(
        'ticket'     => $ticket,
        'product'    => $product ? $product->post_title : __( 'Not Available', 'ucare' ),
        'receipt_id' => get_post_meta( $ticket->ID, 'receipt_id', true )
    );


    // Pull in the template
    get_template( 'sidebar-ecommerce', $args );

}


/**
 * Action to get the customer sidebar
 *
 * @param \WP_Post $ticket The current ticket.
 *
 * @action ucare_ticket_sidebar_customer-details
 *
 * @since 1.4.2
 * @return void
 */
function get_customer_sidebar( \WP_Post $ticket ) {

    $author = get_userdata( abs( $ticket->post_author ) );

    // Don't include the current ticket in the recent list
    $recent = array(
        'exclude' => array( $ticket->ID )
    );

    $args = array(
        'ticket' => $ticket,
        'author' => $author,
        'total'  => ucare_count_user_tickets( $author->ID ),
        'recent' => ucare_get_user_recent_tickets( $author, $recent )
    );

    get_template( 'sidebar-customer', $args );

}


/**
 * Action to get the attachments sidebar.
 *
 * @param \WP_Post $ticket
 *
 * @action ucare_ticket_sidebar_attachments
 *
 * @since 1.4.2
 * @return void
 */
function get_attachments_sidebar( \WP_Post $ticket ) {

    $args = array(
        'ticket' => $ticket,
        'files'  => get_attached_media( allowed_mime_types( 'file'  ), $ticket ),
        'images' => get_attached_media( allowed_mime_types( 'image' ), $ticket )
    );

    get_template( 'sidebar-attachments', $args );

}


/**
 * Action to get the properties sidebar.
 *
 * @param \WP_Post $ticket
 *
 * @action ucare_ticket_sidebar_ticket-properties
 *
 * @since 1.4.2
 * @return void
 */
function get_properties_sidebar( \WP_Post $ticket ) {

    $args = array(
        'ticket' => $ticket,

        //TODO remove this
        'form_config' => resolve_path( 'config/ticket_properties_form.php' )
    );

    get_template( 'sidebar-properties', $args );

}


/**
 * Get the details sidebar.
 *
 * @param \WP_Post $ticket The ticket.
 *
 * @since 1.4.2
 * @return void
 */
function get_details_sidebar( \WP_Post $ticket ) {

    $terms = get_the_terms( $ticket, 'ticket_category' );

    $args = array(
        'ticket'      => $ticket,
        'stale'       => !!get_post_meta( $ticket->ID, 'stale', true ),
        'closed_by'   => get_post_meta( $ticket->ID, 'closed_by', true ),
        'closed_date' => get_post_meta( $ticket->ID, 'closed_date', true ),
        'category'    => $terms ? current( $terms )->name : ''
    );

    get_template( 'sidebar-details', $args );

}


/**
 * Get the ticket sidebar.
 *
 * @param string   $sidebar
 * @param \WP_Post $ticket
 *
 * @since 1.4.2
 * @return void
 */
function get_sidebar( $sidebar, $ticket ) {

    /**
     * Pull in sidebar sections
     */
    do_action( "ucare_ticket_sidebar_$sidebar", $ticket );

}


/**
 * Configure the default sidebar sections.
 *
 * @param $ticket
 *
 * @since 1.0.0
 * @return array
 */
function get_sidebar_sections( $ticket ) {

    $sections = array();

    if ( ucare_is_ecommerce_enabled() ) {
        $sections['purchase-details'] = __( 'Purchase Details', 'ucare' );
    }

    if ( current_user_can( 'manage_support_tickets' ) ) {
        $sections['customer-details'] = __( 'Customer Details', 'ucare' );
    }

    $sections['attachments'] = __( 'Attachments', 'ucare' );

    if ( current_user_can( 'manage_support_tickets' ) ) {
        $sections['ticket-properties'] = __( 'Ticket Properties', 'ucare' );
    }

    return apply_filters( 'ucare_ticket_sidebar_sections', $sections, $ticket );

}
