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
            border-left: 4px solid #3b82f6;
            margin: 20px 0;
            border-radius: 4px;
        }
        .details p {
            margin: 8px 0;
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
            <h1>You're Invited! 🎉</h1>

            <p>Hello,</p>

            <p>You've been invited to join <strong>{{ $organization->name }}</strong> as a <strong>{{ $invitation->getRoleEnum()->label() }}</strong>.</p>

            <div class="details">
                <strong>Invitation Details:</strong>
                <p><strong>Organization:</strong> {{ $organization->name }}</p>
                <p><strong>Role:</strong> {{ $invitation->getRoleEnum()->label() }}</p>
                <p><strong>Invited By:</strong> {{ $invitation->invitedByUser?->name ?? 'Administrator' }}</p>
                <p><strong>Expires:</strong> {{ $invitation->expires_at->format('F j, Y \a\t H:i') }}</p>
            </div>

            <div class="button-container">
                @if ($acceptUrl)
                    <a href="{{ $acceptUrl }}" class="button button-success">Accept Invitation</a>
                @endif

                @if ($declineUrl)
                    <a href="{{ $declineUrl }}" class="button button-danger">Decline Invitation</a>
                @endif
            </div>

            <p>If you did not expect this invitation, you can safely ignore this email.</p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
