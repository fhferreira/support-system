<?php

use SmartcatSupport\descriptor\Option;
use SmartcatSupport\Plugin;
use SmartcatSupport\utilUtils;

$products = \SmartcatSupport\util\products();
$statuses = \SmartcatSupport\util\statuses();
$status = get_post_meta( $ticket->ID, 'status', true );

$product = get_post_meta( $ticket->ID, 'product', true );
$receipt_id = get_post_meta( $ticket->ID, 'receipt_id', true );

if( array_key_exists( $product, $products ) ) {
    $product = $products[$product];
}

if( empty( $receipt_id ) ) {
    $receipt_id = '—';
}

?>

<div class="panel-group">

    <div class="panel panel-default ticket-details" data-id="ticket-details">

        <div class="panel-body">

            <div class="lead"><?php _e( ( array_key_exists( $status, $statuses ) ? $statuses[ $status ] : '—' ), \SmartcatSupport\PLUGIN_ID ); ?></div>

            <p>
                <?php _e( 'Since ', \SmartcatSupport\PLUGIN_ID ); ?><?php echo \SmartcatSupport\util\just_now( $ticket->post_date ); ?>
            </p>

            <p><?php _e( 'From ' . get_the_date( 'l F j, Y', $ticket ), \SmartcatSupport\PLUGIN_ID ); ?></p>

        </div>

    </div>

    <?php if( \SmartcatSupport\util\ecommerce_enabled() ) : ?>

        <div class="panel panel-default purchase-details" data-id="purchase-details">

            <div class="panel-heading">

                <a href="#collapse-purchase-<?php echo $ticket->ID; ?>" data-toggle="collapse"
                   class="panel-title"><?php _e( 'Purchase Details', \SmartcatSupport\PLUGIN_ID ); ?></a>

            </div>

            <div id="collapse-purchase-<?php echo $ticket->ID; ?>" class="panel-collapse in">

                <div class="panel-body">

                    <div class="product-info">

                        <span class="lead"><?php _e( $product, \SmartcatSupport\PLUGIN_ID ); ?>

                    </div>

                    <div class="purchase-info">

                        <span><?php _e( "Receipt # {$receipt_id}", \SmartcatSupport\PLUGIN_ID ); ?></span>

                    </div>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <?php if ( current_user_can( 'manage_support_tickets' ) ) : ?>

        <div class="panel panel-default customer-details" data-id="customer-details">

            <div class="panel-heading">

                <a href="#collapse-customer-<?php echo $ticket->ID; ?>" data-toggle="collapse"
                   class="panel-title"><?php _e( 'Customer Details', \SmartcatSupport\PLUGIN_ID ); ?></a>

            </div>

            <div id="collapse-customer-<?php echo $ticket->ID; ?>" class="panel-collapse in">

                <div class="panel-body">

                    <div class="media">

                        <div class="media-left">

                            <?php echo get_avatar( $ticket, 48, '', '', array( 'class' => 'img-circle media-object' ) ); ?>

                        </div>

                        <div class="media-body" style="width: auto">

                            <p>

                                <strong class="media-middle"><?php echo get_the_author_meta( 'display_name', $ticket->post_author ); ?></strong>

                            </p>

                            <p><?php _e( 'Email: ', \SmartcatSupport\PLUGIN_ID );
                                echo \SmartcatSupport\util\author_email( $ticket ); ?></p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="panel panel-default ticket-properties" data-id="ticket-properties">

            <div class="panel-heading">

                <a href="#collapse-details-<?php echo $ticket->ID; ?>" data-toggle="collapse"
                   class="panel-title"><?php _e( 'Ticket Properties', \SmartcatSupport\PLUGIN_ID ); ?></a>

            </div>

            <div id="collapse-details-<?php echo $ticket->ID; ?>" class="panel-collapse in">

                <div class="message"></div>

                <div class="panel-body">

                    <form class="ticket-status-form">

                        <?php $form = include_once Plugin::plugin_dir( \SmartcatSupport\PLUGIN_ID ) . '/config/ticket_properties_form.php'; ?>

                        <?php foreach ( $form->fields as $field ) : ?>

                            <div class="form-group">

                                <label><?php echo $field->label; ?></label>

                                <?php $field->render(); ?>

                            </div>

                        <?php endforeach; ?>

                        <input type="hidden" name="id" value="<?php echo $ticket->ID; ?>"/>
                        <input type="hidden" name="<?php echo $form->id; ?>"/>

                        <div class="bottom text-right">

                            <button type="submit" class="button button-submit">

                                <?php _e( get_option( Option::SAVE_BTN_TEXT, Option\Defaults::SAVE_BTN_TEXT ) ); ?>

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <?php do_action( 'support_ticket_side_bar', $ticket ); ?>

</div>