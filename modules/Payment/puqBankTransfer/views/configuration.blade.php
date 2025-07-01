<div class="border rounded bg-light p-3">
    <div class="mb-3">
        <label class="form-label"
               for="bank_transfer_instructions">{{ __('Payment.puqBankTransfer.Bank Transfer Instructions') }}</label>
        <textarea class="form-control" id="bank_transfer_instructions" name="bank_transfer_instructions"
                  rows="3">{{$bank_transfer_instructions}}</textarea>
    </div>
    <small
        class="opacity-5">{{ __('main.Available Tags: {INVOICE_NUMBER}, {CURRENCY}, {AMOUNT} ') }}</small>
</div>


<script>
    $("#bank_transfer_instructions").textareaAutoSize().trigger('autosize');
</script>
