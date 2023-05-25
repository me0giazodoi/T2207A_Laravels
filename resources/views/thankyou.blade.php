@extends("layouts.layout")
@section("main")
    <h2>Danh sách sản phẩm của đơn hàng số #{{$order->id}}</h2>
    <ul>
        @foreach($order->products as $item)
            <li>{{$item->name}} - ${{$item->pivot->price}}
                -- qty: {{$item->pivot->buy_qty}}</li>
        @endforeach
    </ul>
    <h3>Tổng tiền: {{$order->total}}</h3>

@endsection
