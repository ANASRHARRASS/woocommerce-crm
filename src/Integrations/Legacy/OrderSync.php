<?php
namespace WCP\WooCommerce;

use WCP\Leads\LeadManager;

defined( 'ABSPATH' ) || exit;

class OrderSync {

    protected LeadManager $leads;

    public function __construct( LeadManager $leads ) {
        $this->leads = $leads;
        add_action( 'woocommerce_new_order', [ $this, 'handle_new_order' ], 20, 1 );
    }

    public function handle_new_order( $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $data = [
            'source' => 'woocommerce_order',
            'email'  => $order->get_billing_email(),
            'phone'  => $order->get_billing_phone(),
            'name'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
            'payload'=> [
                'order_id' => $order_id,
                'total'    => $order->get_total(),
                'items'    => array_map(
                    fn( $i ) => [
                        'product_id' => $i->get_product_id(),
                        'name'       => $i->get_name(),
                        'qty'        => $i->get_quantity(),
                    ],
                    $order->get_items()
                )
            ],
        ];
        $this->leads->create_lead( $data );
    }
}
