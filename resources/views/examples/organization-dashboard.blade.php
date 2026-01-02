<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Organization - Example</title>

    <!-- Tailwind CSS CDN (replace with your preferred method) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js is included with Livewire 3 -->

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation Bar -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-xl font-bold text-gray-900">My Application</h1>
                        </div>
                    </div>

                    <!-- Organization Switcher in Navigation -->
                    <div class="flex items-center space-x-4">
                        @auth
                            <livewire:org::switcher :user="auth()->user()" />
                        @endauth

                        <!-- User menu would go here -->
                        <div class="text-sm text-gray-500">
                            @auth
                                {{ auth()->user()->name ?? auth()->user()->email }}
                            @else
                                Not authenticated
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Page header -->
            <div class="px-4 py-6 sm:px-0">
                <div class="border-4 border-dashed border-gray-200 rounded-lg">
                    <div class="text-center py-12">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Laravel Organization Package</h2>
                        <p class="text-lg text-gray-600 mb-8">
                            Example implementation of organization management with Livewire components
                        </p>

                        @guest
                            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                                <p class="font-medium">Authentication Required</p>
                                <p class="text-sm">Please log in to see the organization management features.</p>
                            </div>
                        @endguest
                    </div>
                </div>
            </div>

            @auth
                <!-- Organization Management Section -->
                <div class="px-4 py-6 sm:px-0">
                    <!-- Organization List Component -->
                    <div class="mb-8">
                        <livewire:org::list />
                    </div>
                </div>
            @endauth
        </main>
    </div>

    <!-- Global Livewire Components (Modals) -->
    @auth
        <!-- Create Organization Form Modal -->
        <livewire:org::form />

        <!-- Manage Organization Modal -->
        <livewire:org::manage />
    @endauth    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Additional JavaScript for better UX -->
    <script>
        // Handle flash messages auto-hide
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide flash messages after 5 seconds
            const flashMessages = document.querySelectorAll('[x-data*="show: true"]');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    const alpineComponent = Alpine.$data(message);
                    if (alpineComponent && alpineComponent.show) {
                        alpineComponent.show = false;
                    }
                }, 5000);
            });
        });

        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('organization-switched', (event) => {
                console.log('Organization switched to:', event.organizationId);
                // You can add custom logic here, like updating other UI elements
            });

            Livewire.on('organization-created', (event) => {
                console.log('Organization created:', event.organizationId);
                // You can add custom logic here, like showing a celebration animation
            });

            Livewire.on('organization-updated', (event) => {
                console.log('Organization updated:', event.organizationId);
            });

            Livewire.on('organization-deleted', (event) => {
                console.log('Organization deleted:', event.organizationId);
            });
        });
    </script>
</body>
</html>
