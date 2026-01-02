<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Sidebar Example</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js is included with Livewire 3 -->
    @livewireStyles
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-md border-r border-gray-200">
            <!-- App Logo/Title -->
            <div class="p-4 border-b border-gray-200">
                <h1 class="text-lg font-semibold text-gray-900">My App</h1>
            </div>

            <!-- Organization Widget -->
            <div class="p-4">
                @auth
                    <livewire:org::widget />
                @else
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500">Please log in to access organizations</p>
                    </div>
                @endauth
            </div>

            <!-- Navigation Menu -->
            <nav class="mt-4">
                <div class="px-4 space-y-1">
                    <a href="#" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-blue-500 mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>

                    <a href="#" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-gray-400 mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Team
                    </a>

                    <a href="#" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-gray-400 mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Projects
                    </a>

                    <a href="#" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-gray-400 mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Settings
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16 items-center">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
                            @auth
                                @if(auth()->user()->currentOrganization ?? (property_exists(auth()->user(), 'current_organization_id') && auth()->user()->current_organization_id))
                                    <p class="text-sm text-gray-500">
                                        Welcome to {{ auth()->user()->currentOrganization->name ?? 'your organization' }}
                                    </p>
                                @endif
                            @endauth
                        </div>

                        <!-- User Menu -->
                        <div class="flex items-center space-x-4">
                            @auth
                                <span class="text-sm text-gray-700">{{ auth()->user()->name ?? auth()->user()->email }}</span>
                                <button class="bg-gray-300 rounded-full h-8 w-8 flex items-center justify-center">
                                    <span class="text-sm font-medium">{{ substr(auth()->user()->name ?? auth()->user()->email, 0, 1) }}</span>
                                </button>
                            @else
                                <a href="#" class="text-blue-600 hover:text-blue-500">Sign In</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                    <div class="px-4 py-6 sm:px-0">
                        <!-- Content Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Main Content -->
                            <div class="lg:col-span-2">
                                <div class="bg-white shadow rounded-lg p-6">
                                    <h2 class="text-lg font-medium text-gray-900 mb-4">Welcome to Your Dashboard</h2>
                                    <p class="text-gray-600 mb-4">
                                        This is an example implementation of the Laravel Organization package with Livewire components.
                                    </p>

                                    @guest
                                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                                            <p class="font-medium">Authentication Required</p>
                                            <p class="text-sm">Please log in to access organization features.</p>
                                        </div>
                                    @endguest

                                    @auth
                                        <div class="space-y-4">
                                            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded">
                                                <p class="font-medium">Organization Features Available</p>
                                                <p class="text-sm">Use the sidebar widget to manage your organizations.</p>
                                            </div>

                                            <!-- Quick Actions -->
                                            <div class="flex space-x-4">
                                                <button onclick="Livewire.dispatch('show-create-organization')"
                                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                                    Create Organization
                                                </button>

                                                <button onclick="Livewire.dispatch('show-organization-list')"
                                                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    View All Organizations
                                                </button>
                                            </div>
                                        </div>
                                    @endauth
                                </div>
                            </div>

                            <!-- Sidebar Content -->
                            <div class="space-y-6">
                                <!-- Stats Card -->
                                <div class="bg-white shadow rounded-lg p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
                                    @auth
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Organizations:</span>
                                                <span class="text-sm font-medium">{{ auth()->user()->organizations->count() ?? 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Role:</span>
                                                <span class="text-sm font-medium">{{ auth()->user()->currentOrganization ? 'Member' : 'None' }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">Login to see your stats</p>
                                    @endauth
                                </div>

                                <!-- Recent Activity -->
                                <div class="bg-white shadow rounded-lg p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900">Welcome to the demo</p>
                                                <p class="text-sm text-gray-500">Just now</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Global Modals (if authenticated) -->
    @auth
        <livewire:org::form />
        <livewire:org::manage />
    @endauth

    @livewireScripts

    <script>
        // Handle organization switching
        document.addEventListener('livewire:init', () => {
            Livewire.on('organization-switched', (event) => {
                // Reload the page to update all UI elements
                window.location.reload();
            });
        });
    </script>
</body>
</html>
