<?php

class Ebanx_Gateway_Model_Observer
{
	public function interceptPaymentNotification($observer)
	{
		$request = $observer->getEvent()->getData('front')->getRequest();

		if (!$this->isEbanxPaymentRequest($request)) return;

		// TOOD: Make a Benjamin query to find the payment by hash
		$payment = [
			'payment' => [
				'status' => 'PE'
			]
		];

		$helper = Mage::helper('ebanx');

		$hash = $request->hash;
		$order = $helper->getOrderByHash($hash);
		$ebanxStatus = $payment['payment']['status'];
		$status = $helper->getEbanxMagentoOrder($ebanxStatus);

		// Updating the order status
		$order->setState($status, true);

		// Adding an order note
		$order->addStatusHistoryComment(__('EBANX: The payment has been updated to: %s.', $helper->getTranslatedOrderStatus($ebanxStatus)));

		$order->save();
	}

	private function isEbanxPaymentRequest($request)
	{
		$hasHash = isset($request->hash) && !empty($request->hash);
		$hasPaymentCode = isset($request->merchant_payment_code) && !empty($request->merchant_payment_code);
		$hasPaymentTypeCode = isset($request->payment_type_code) && !empty($request->payment_type_code);

		return $hasHash && $hasPaymentCode && $hasPaymentTypeCode;
	}
}
