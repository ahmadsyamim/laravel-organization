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
        .status-accepted {
            background: #d1fae5;
            padding: 15px;
            border-left: 4px solid #10b981;
            margin: 20px 0;
            border-radius: 4px;
        }
        .status-declined {
            background: #fef2f2;
            padding: 15px;
            border-left: 4px solid #ef4444;
            margin: 20px 0;
            border-radius: 4px;
        }
        .details {
            background: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #3b82f6;
            margin: 20px 0;
            border-radius: 4px;
        }
        .details p {
            margin: 8px 0;
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
            @if ($accepted)
                <h1>Ownership Transfer Completed</h1>

                <div class="status-accepted">
                    <strong>Transfer Accepted</strong>
                    <p>The ownership of <strong>{{ $organization->name }}</strong> has been successfully transferred.</p>
                </div>

                <p>Hello {{ $currentOwner->name ?? 'there' }},</p>

                <p>This is to confirm that <strong>{{ $newOwner->name ?? 'the new owner' }}</strong> has accepted your ownership transfer request for <strong>{{ $organization->name }}</strong>.</p>

                <div class="details">
                    <strong>Transfer Details:</strong>
                    <p><strong>Organization:</strong> {{ $organization->name }}</p>
                    <p><strong>Previous Owner:</strong> {{ $currentOwner->name ?? 'N/A' }} (You)</p>
                    <p><strong>New Owner:</strong> {{ $newOwner->name ?? 'N/A' }}</p>
                    <p><strong>Completed:</strong> {{ now()->format('F j, Y \a\t H:i') }}</p>
                </div>

                <p>You have been assigned the Administrator role and will continue to have management access to the organization.</p>
            @else
                <h1>Ownership Transfer Declined</h1>

                <div class="status-declined">
                    <strong>Transfer Declined</strong>
                    <p>The ownership transfer request for <strong>{{ $organization->name }}</strong> was declined.</p>
                </div>

                <p>Hello {{ $currentOwner->name ?? 'there' }},</p>

                <p><strong>{{ $newOwner->name ?? 'The intended recipient' }}</strong> has declined your ownership transfer request for <strong>{{ $organization->name }}</strong>.</p>

                <div class="details">
                    <strong>Request Details:</strong>
                    <p><strong>Organization:</strong> {{ $organization->name }}</p>
                    <p><strong>Current Owner:</strong> {{ $currentOwner->name ?? 'N/A' }} (You)</p>
                    <p><strong>Intended Recipient:</strong> {{ $newOwner->name ?? 'N/A' }}</p>
                    <p><strong>Declined:</strong> {{ now()->format('F j, Y \a\t H:i') }}</p>
                </div>

                <p>You remain the owner of this organization. If you wish to transfer ownership to someone else, you can initiate a new transfer request.</p>
            @endif

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
