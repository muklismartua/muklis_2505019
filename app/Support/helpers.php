<?php

if (! function_exists('rupiah')) {
    function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
