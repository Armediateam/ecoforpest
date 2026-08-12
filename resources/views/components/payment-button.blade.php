@if($invoice->payment_url)
    <!-- Payment Button -->
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $invoice->payment_url }}" style="display: inline-block; background-color: #28a745; color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 5px; font-size: 16px; font-weight: 600;">
            Pay Now with Xendit
        </a>
    </div>
@endif
