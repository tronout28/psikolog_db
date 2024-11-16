<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .invoice-container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
        }
        .details, .order-items {
            margin-bottom: 20px;
        }
        .details table, .order-items table {
            width: 100%;
            border-collapse: collapse;
        }
        .details th, .details td, .order-items th, .order-items td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .details th {
            background-color: #f4f4f4;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <h1>Invoice</h1>
            <p>Order For: {{ $user->name }}</p>
            <p>Date: {{ $order->created_at->format('d-m-Y') }}</p>
        </div>

        <div class="details">
            <h2>Customer Details</h2>
            <table>
                <tr>
                    <th>Name</th>
                    <td>{{ $user->name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $user->email }}</td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>{{ $user->phone_number }}</td>
                </tr>
                @if($order->buku_id)
                    <tr>
                        <th>Address</th>
                        <td>{{ $order->detailed_address }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="order-items">
            <h2>Order Details</h2>
            @if($order->buku_id)
                <p><strong>Order Type:</strong> Book</p>
                <table>
                    <tr>
                        <th>Title</th>
                        <td>{{ $buku->title }}</td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td>{{ $buku->description }}</td>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <td>Rp {{ number_format($buku->price, 0, ',', '.') }}</td>
                    </tr>
                </table>
            @elseif($order->paket_id)
                <p><strong>Order Type:</strong> Paket</p>
                <table>
                    <tr>
                        <th>Title</th>
                        <td>{{ $paket->title }}</td>
                    </tr>
                    <tr>
                        <th>Paket Type</th>
                        <td>{{ $paket->paket_type }}</td>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <td>Rp {{ number_format($paket->price, 0, ',', '.') }}</td>
                    </tr>
                </table>
            @endif
        </div>

        <div class="footer">
            <p><strong>Total Amount Paid:</strong> Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
            <p>Thank you for your order!</p>
        </div>
    </div>
</body>
</html>
