<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Laravel Organization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}
    @livewireStyles
</head>

<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">Laravel Organization Dashboard</h1>
                </div>

                <div class="flex items-center space-x-4">
                    @livewire('org::switcher')
                    <span class="text-sm text-gray-700">Welcome, {{ Auth::user()->name }}!</span>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-md transition duration-200">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto sm:px-6 mt-4 lg:px-8 space-y-4">
        <!-- Organization Display & Management -->
        @livewire('org::list')

        <!-- Organization Display & Management -->
        @livewire('org::widget')

        <!-- Modal Components for CRUD Operations -->
        @livewire('org::form')
        @livewire('org::manage')
    </div>


    @livewireScripts

    <script>
        // Handle Livewire events for seamless component integration
        document.addEventListener('livewire:init', () => {
            // Listen for organization-related events from Livewire components
            Livewire.on('organization-created', (event) => {
                console.log('Organization created:', event);
                showNotification(
                    `Organization "${event.organizationName || 'New Organization'}" created successfully!`,
                    'success');
            });

            Livewire.on('organization-switched', (event) => {
                console.log('Organization switched:', event);
                showNotification('Organization switched successfully!', 'info');
            });

            Livewire.on('organization-updated', (event) => {
                console.log('Organization updated:', event);
                showNotification('Organization updated successfully!', 'success');
            });

            Livewire.on('organization-deleted', (event) => {
                console.log('Organization deleted:', event);
                showNotification('Organization deleted successfully!', 'warning');
            });
        });

        // Utility function to show notifications
        function showNotification(message, type = 'success') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };

            const notification = document.createElement('div');
            notification.className =
                `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full`;
            notification.textContent = message;
            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);

            // Auto remove notification
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }
    </script>
</body>

</html>
