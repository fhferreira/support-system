<?php

namespace SmartcatSupport\form;

use const SmartcatSupport\TEXT_DOMAIN;

/**
 * Smartcat-Form on steroids 
 */
class Form {
    protected $id;
    protected $fields = [];
    protected $method;
    protected $action;
    protected $valid = false;
    protected $valid_values = [];
    protected $errors = [];

    public function __construct( $id, array $fields, $method, $action ) {
        $this->id = $id;
        $this->fields = $fields;
        $this->method = strtoupper( $method );
        $this->action = $action;
    }
    
    public function is_valid() {
        $valid = true;

        if( $this->is_submitted() && wp_verify_nonce( $_REQUEST[ $this->id ], 'submit' ) ) {
            foreach( $this->fields as $id => $field ) {
                $value = $field->validate( $_REQUEST[ $field->get_id() ] );
                
                if( $value === true  ) {
                    $this->valid_values[ $field->get_id() ] = $field->sanitize( $_REQUEST[ $field->get_id() ] );
                } else {
                    $this->errors[ $field->get_id() ] = $value;
                    $valid = false;
                }
            }

            $this->valid = $valid;
        }

        return $valid;
    }
    
    public function is_submitted() {
        return isset( $_REQUEST[ $this->id ] );
    }
    
    public function get_data() {
        return $this->valid_values;
    }
    
    public function get_errors() {
        return $this->errors;
    }

    public function get_fields() {
        return $this->fields;
    }
        
    public function get_id() {
        return $this->id;
    }

    public function get_method() {
        return $this->method;
    }

    public function get_action() {
        return $this->action;
    }
    
    // <editor-fold defaultstate="collapsed" desc="Display Logic">
    public static function form_start( Form $form ) {
        ?>
            <form id="<?php esc_attr_e( $form->id ); ?>"
                method="<?php esc_attr_e( $form->get_method() ); ?>"
                action="<?php esc_attr_e( $form->get_action() ); ?>">
        <?php
    }

    public static function form_fields( Form $form ) { ?>
            
        <table class="form-table">

            <?php foreach( $form->get_fields() as $field ) : ?>

                <tr>
                    <?php if( $field->get_label() != null ) : ?>
                    
                        <th>
                            <label>
                                <?php esc_html_e( __( $field->get_label(), TEXT_DOMAIN ) ); ?>
                            </label>
                        </th>
                        
                    <?php endif; ?>
                    <td>
                        <?php $field->render(); ?>

                        <?php if ( $field->get_desc() != null ) : ?>

                            <p class="description">
                                <?php esc_html_e( $field->get_desc() ); ?>
                            </p>

                        <?php endif; ?>

                    </td>
                </tr>

            <?php endforeach; ?>

        </table>

        <?php wp_nonce_field( 'submit', $form->get_id() );

    }

    public static function form_end( Form $form ) {
        ?></form><?php
    }

// </editor-fold>
}
