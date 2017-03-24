<?php
/**
 * Takes Siru data that was passed to redirectAfter* or notifyAfter* URL and updates order accordingly.
 */
class WC_Gateway_Sirumobile_Response {

    /**
     * @var \Siru\Signature
     */
    private $signature;

    /**
     * @param \Siru\Signature $signature
     */
    public function __construct(\Siru\Signature $signature)
    {
        $this->signature = $signature;
    }

    /**
     * [handleRequest description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function handleRequest(array $data)
    {
        return $this->updateOrder($data, 'request');
    }

    public function handleNotify(array $data)
    {
        $success = $this->updateOrder($data, 'notification', $errorstr);

        if($success == false) {
            wp_die($errorstr, null, array( 'response' => 500 ) );
        }

        return true;
    }

    private function updateOrder(array $data, $from, &$errorstr = '')
    {
        $event = $data['siru_event'];
        $order_id = $data['siru_purchaseReference'];
        $uuid = $data['siru_uuid'];

        if($this->signature->isNotificationAuthentic($data) == false) {
            WC_Gateway_Sirumobile::log(sprintf('Received %s %s for order %s with invalid or missing signature.', $event, $from, $order_id));
            $errorstr = 'Invalid notification';
            return false;
        }

        // Notification was sent by Siru Mobile and is authentic
        try {
            $order  = wc_get_order($order_id);

            WC_Gateway_Sirumobile::log(sprintf('Received %s %s for order %s (%s).', $event, $from, $order->id, $order->get_status()));
            if ($order->has_status( 'completed')) {
                return true;
            }

            switch($event) {
                case 'success':
                    $order->payment_complete($uuid);
                    break;

                case 'cancel':
                    $order->cancel_order(__('Canceled by user'));
                    break;

                case 'failure':
                    $order->update_status('failed', $uuid);
                    break;
            }
        } catch(Exception $e) {
            error_log($e->getMessage());
            WC_Gateway_Sirumobile::log(sprintf(
                '%s: %s failed to update order %s status to %s.',
                get_class($e),
                $from,
                $order_id,
                $event));
            $errorstr = 'Failed to update order';
            return false;
        }

        return true;

    }

}
