<?php
    /** @var WC_Order $order */
?>
<div style="text-align: center">
    <p>
        <h3 style="text-align: center"><?= __( 'Your order is on the way!', 'woo-lithuaniapost' ); ?></h3>
        <?= __( 'Order nr.', 'woo-lithuaniapost' ); ?> <?= $order->get_order_number (); ?><br>
        <?= __( 'Order date:', 'woo-lithuaniapost' ); ?> <?= date ( 'Y-m-d', strtotime ( $order->get_date_created () ) ); ?>
    </p>
    <p>
        <?= __( 'Tracking nr.', 'woo-lithuaniapost' ); ?> <?= $order->get_meta ( '_woo_lithuaniapost_barcode' ); ?>
    </p>
    <p style="margin-top: 25px; padding-bottom: 35px;">
        <a href="https://www.post.lt/siuntu-sekimas?parcels=<?= $order->get_meta ( '_woo_lithuaniapost_barcode' ); ?>" target="_blank" style="text-decoration: none;
                            color: #fff;
                            background-color: #96588a;
                            padding: 15px 35px;
                            font-weight: bold;"><?= __( 'TRACK PARCEL', 'woo-lithuaniapost' ); ?></a>
    </p>
</div>