<table class="store-header">
    <tr>
        @if (! empty($store['logo']) && is_file($store['logo']))
            <td style="width: 100px">
                <img class="store-logo" src="{{ $store['logo'] }}" alt="Logo toko">
            </td>
        @endif
        <td>
            <h1 class="store-name">{{ $store['name'] }}</h1>
            @if (! empty($store['address']))
                <p class="store-address">{{ $store['address'] }}</p>
            @endif
            @if (! empty($store['phone']))
                <p class="store-address">Telepon: {{ $store['phone'] }}</p>
            @endif
        </td>
    </tr>
</table>
