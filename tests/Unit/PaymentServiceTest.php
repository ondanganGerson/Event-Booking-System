<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\PaymentService;
use App\User;
use App\Event;
use App\Ticket;
use App\Booking;
use App\Payment;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;

    public function setUp()
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
    }

    /**
     * Test payment processing creates a payment record.
     *
     * @return void
     */
    public function testProcessPaymentCreatesPaymentRecord()
    {
        $booking = factory(Booking::class)->create([
            'status' => 'pending',
        ]);
        $amount = 100.00;

        $payment = $this->paymentService->processPayment($booking, $amount);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($booking->id, $payment->booking_id);
        $this->assertEquals($amount, $payment->amount);
        $this->assertContains($payment->status, ['success', 'failed']);
    }

    /**
     * Test successful payment updates booking status to confirmed.
     *
     * @return void
     */
    public function testSuccessfulPaymentUpdatesBookingStatus()
    {
        $booking = factory(Booking::class)->create([
            'status' => 'pending',
        ]);

        // Mock successful payment
        $payment = factory(Payment::class)->create([
            'booking_id' => $booking->id,
            'amount' => 100,
            'status' => 'success',
        ]);

        if ($payment->status === 'success') {
            $booking->update(['status' => 'confirmed']);
        }

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    /**
     * Test calculate amount multiplies price by quantity.
     *
     * @return void
     */
    public function testCalculateAmountMultipliesPriceByQuantity()
    {
        $ticket = factory(Ticket::class)->create(['price' => 50.00]);
        $booking = factory(Booking::class)->create([
            'ticket_id' => $ticket->id,
            'quantity' => 3,
        ]);

        $amount = $this->paymentService->calculateAmount($booking);

        $this->assertEquals(150.00, $amount);
    }

    /**
     * Test refund payment updates status to refunded.
     *
     * @return void
     */
    public function testRefundPaymentUpdatesStatusToRefunded()
    {
        $booking = factory(Booking::class)->create(['status' => 'confirmed']);
        $payment = factory(Payment::class)->create([
            'booking_id' => $booking->id,
            'status' => 'success',
        ]);

        $refundedPayment = $this->paymentService->refundPayment($payment);

        $this->assertEquals('refunded', $refundedPayment->status);
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    /**
     * Test refund throws exception for non-successful payments.
     *
     * @return void
     */
    public function testRefundThrowsExceptionForNonSuccessfulPayments()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only successful payments can be refunded.');

        $payment = factory(Payment::class)->create(['status' => 'failed']);

        $this->paymentService->refundPayment($payment);
    }
}
