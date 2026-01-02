<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .details {
            background: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #f59e0b;
            margin: 20px 0;
            border-radius: 4px;
        }
        .details p {
            margin: 8px 0;
        }
        .message-box {
            background: #fef3c7;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .warning {
            background: #fef2f2;
            padding: 15px;
            border-left: 4px solid #ef4444;
            border-radius: 4px;
            margin: 20px 0;
        }
        .button-container {
            margin: 30px 0;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .button-success {
            background: #10b981;
            color: white;
        }
        .button-success:hover {
            background: #059669;
        }
        .button-danger {
            background: #ef4444;
            color: white;
        }
        .button-danger:hover {
            background: #dc2626;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <h1>Ownership Transfer Request</h1>

            <p>Hello {{ $newOwner->name ?? 'there' }},</p>

            <p><strong>{{ $currentOwner->name ?? 'The current owner' }}</strong> would like to transfer ownership of <strong>{{ $organization->name }}</strong> to you.</p>

            <div class="details">
                <strong>Transfer Details:</strong>
                <p><strong>Organization:</strong> {{ $organization->name }}</p>
                <p><strong>Current Owner:</strong> {{ $currentOwner->name ?? 'N/A' }}</p>
                <p><strong>Expires:</strong> {{ $expiresAt->format('F j, Y \a\t H:i') }}</p>
            </div>

            @if ($personalMessage)
                <div class="message-box">
                    <strong>Message from {{ $currentOwner->name ?? 'the owner' }}:</strong>
                    <p>{{ $personalMessage }}</p>
                </div>
            @endif

            <div class="warning">
                <strong>Important:</strong>
                <p>By accepting this transfer, you will become the owner of this organization with full control over all settings, members, and data. This action cannot be undone without another transfer request.</p>
            </div>

            <div class="button-container">
                @if ($acceptUrl)
                    <a href="{{ $acceptUrl }}" class="button button-success">Accept Ownership</a>
                @endif

                @if ($declineUrl)
                    <a href="{{ $declineUrl }}" class="button button-danger">Decline Request</a>
                @endif
            </div>

            <p>If you did not expect this request, you can safely ignore this email. The request will expire automatically.</p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
