<?php

namespace App\Services\Donation;

/**
 * The amount Midtrans reports for an order differs from the stored donation.
 * Needs manual review; retrying will not resolve it.
 */
class GatewayAmountMismatchException extends \RuntimeException {}
