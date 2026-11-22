<table class="store-header">
    <tr>
        @if (! empty($store['logo']) && is_file($store['logo']))
            <td style="width: 100px">
                <img class="store-logo" src="{{ $store['logo'] }}" alt="Logo toko">
            </td>
        @endif
        <td>
            <h1 class="store-name">{{ $store['name'] }}</h1>
            @if ($store['address'] !== '')
                <p class="store-address">{{ $store['address'] }}</p>
            @endif
        </td>
    </tr>
</table>
