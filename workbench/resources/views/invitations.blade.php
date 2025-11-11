<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Invitations - Laravel Organization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Manage Invitations</h1>
                    <div class="hidden sm:flex space-x-1">
                        <a href="{{ route('dashboard') }}"
                            class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            Dashboard
                        </a>
                        <a href="{{ route('invitations') }}"
                            class="px-3 py-2 rounded-md text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100">
                            Invitations
                        </a>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    @livewire('org::switcher')
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ Auth::user()->name }}</span>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 text-white text-sm px-4 py-2 rounded-md transition duration-200">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Invitation Management</h2>
            <p class="text-gray-600 dark:text-gray-400">Manage organization member invitations and memberships.</p>
        </div>

        <!-- Current Organization Selection -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Organization</h3>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                @php
                    $currentOrgId = Auth::user()->getOrganizationId();
                    $currentOrg = \CleaniqueCoders\LaravelOrganization\Models\Organization::find($currentOrgId);
                @endphp
                <p class="text-gray-900 dark:text-white font-medium">
                    @if ($currentOrg)
                        {{ $currentOrg->name }}
                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">(ID: {{ $currentOrg->id }})</span>
                    @else
                        <span class="text-yellow-600 dark:text-yellow-400">No organization selected</span>
                    @endif
                </p>
            </div>
        </div>

        <!-- Invitation Manager Component -->
        @if ($currentOrg)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                @livewire('org::invitation-manager', ['organization' => $currentOrg])
            </div>
        @else
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No Organization Selected</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>Please select an organization from the switcher to manage its invitations.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 right-4 space-y-2 z-50"></div>

    @livewireScripts

    <script>
        // Handle Livewire events
        document.addEventListener('livewire:init', () => {
            // Listen for notification events from the InvitationManager component
            Livewire.on('notification', (data) => {
                // data could be an array or object with message and type
                const message = Array.isArray(data) && data.length > 0 ? data[0]?.message || 'Action completed' : data?.message || 'Action completed';
                const type = Array.isArray(data) && data.length > 0 ? data[0]?.type || 'success' : data?.type || 'success';
                showNotification(message, type);
            });
        });

        // Utility function to show notifications
        function showNotification(message, type = 'success') {
            const colors = {
                success: {
                    bg: 'bg-green-50 dark:bg-green-900/20',
                    border: 'border-green-200 dark:border-green-800',
                    text: 'text-green-800 dark:text-green-200',
                    icon: 'text-green-400'
                },
                error: {
                    bg: 'bg-red-50 dark:bg-red-900/20',
                    border: 'border-red-200 dark:border-red-800',
                    text: 'text-red-800 dark:text-red-200',
                    icon: 'text-red-400'
                },
                info: {
                    bg: 'bg-blue-50 dark:bg-blue-900/20',
                    border: 'border-blue-200 dark:border-blue-800',
                    text: 'text-blue-800 dark:text-blue-200',
                    icon: 'text-blue-400'
                },
                warning: {
                    bg: 'bg-yellow-50 dark:bg-yellow-900/20',
                    border: 'border-yellow-200 dark:border-yellow-800',
                    text: 'text-yellow-800 dark:text-yellow-200',
                    icon: 'text-yellow-400'
                }
            };

            const style = colors[type] || colors.success;

            const notification = document.createElement('div');
            notification.className = `${style.bg} ${style.border} border rounded-lg p-4 ${style.text} animate-pulse`;
            notification.innerHTML = `
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 ${style.icon}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                </div>
            `;

            const container = document.getElementById('notification-container');
            container.appendChild(notification);

            // Remove after 4 seconds
            setTimeout(() => {
                notification.remove();
            }, 4000);
        }
    </script>
</body>

</html>
