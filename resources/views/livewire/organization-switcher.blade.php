<div x-data="{ open: @entangle('showDropdown') }" class="relative inline-block text-left">
    <!-- Organization Switcher Button -->
    <div>
        <button @click="open = !open" type="button"
                class="inline-flex w-full justify-between items-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                id="organization-menu-button" aria-expanded="true" aria-haspopup="true">
            <div class="flex items-center gap-2">
                <div class="flex-shrink-0">
                    <div class="w-6 h-6 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <span class="text-xs font-bold text-white">
                            {{ $currentOrganization ? substr($currentOrganization->name, 0, 1) : 'O' }}
                        </span>
                    </div>
                </div>
                <div class="text-left hidden md:block">
                    <div class="text-sm font-medium text-gray-900">
                        {{ $currentOrganization ? $currentOrganization->name : 'Select Organization' }}
                    </div>
                    @if($currentOrganization)
                        <div class="text-xs text-gray-500">Current workspace</div>
                    @endif
                </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                 :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    <!-- Dropdown Menu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         @click.outside="open = false"
         class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
         role="menu" aria-orientation="vertical" aria-labelledby="organization-menu-button" tabindex="-1">

        <div class="p-2">
            <!-- Current Organization -->
            @if($currentOrganization)
                <div class="px-3 py-2 mb-2 bg-gradient-to-r from-blue-50 to-purple-50 rounded-md border-2 border-blue-300 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-md">
                            <span class="text-sm font-bold text-white">{{ substr($currentOrganization->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">{{ $currentOrganization->name }}</div>
                            <div class="text-xs text-blue-600 font-semibold flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Current workspace
                                @if($isCurrentDefault)
                                    <span class="text-green-600">(Default)</span>
                                @endif
                            </div>
                        </div>
                        @if(!$isCurrentDefault)
                            <button wire:click="setAsDefault"
                                    class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                                    title="Set as default organization">
                                Set Default
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Success Message -->
            @if($successMessage)
                <div class="px-3 py-2 mb-2 bg-green-50 border border-green-200 rounded-md">
                    <div class="text-xs text-green-700">{{ $successMessage }}</div>
                </div>
            @endif

            <!-- Error Message -->
            @if($errorMessage)
                <div class="px-3 py-2 mb-2 bg-red-50 border border-red-200 rounded-md">
                    <div class="text-xs text-red-700">{{ $errorMessage }}</div>
                </div>
            @endif

            <!-- Organizations List -->
            <div class="space-y-1 max-h-60 overflow-y-auto">
                @forelse($organizations as $organization)
                    @if(!$currentOrganization || $currentOrganization->id !== $organization->id)
                        <button wire:click="switchOrganization({{ $organization->id }})"
                                class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-gray-100 transition-colors duration-150 group text-gray-700"
                                role="menuitem">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gradient-to-r from-gray-400 to-gray-600 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-bold text-white">{{ substr($organization->name, 0, 1) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate">{{ $organization->name }}</div>
                                    @if($organization->description)
                                        <div class="text-xs text-gray-500 truncate">{{ $organization->description }}</div>
                                    @endif
                                    <div class="text-xs text-gray-400">
                                        @if($organization->owner_id === auth()->id())
                                            Owner
                                        @else
                                            Member
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endif
                @empty
                    <div class="px-3 py-4 text-center text-gray-500">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <p class="text-sm">No organizations found</p>
                        <p class="text-xs mt-1">Create your first organization to get started</p>
                    </div>
                @endforelse
            </div>

            <!-- Action Buttons -->
            <div class="border-t border-gray-200 mt-2 pt-2 space-y-1">
                <button wire:click="$dispatch('show-create-organization')"
                        class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md transition-colors duration-150 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create new organization
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div wire:loading wire:target="switchOrganization" class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-4 shadow-xl">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600">Switching organization...</span>
            </div>
        </div>
    </div>
</div>

<!-- Flash Messages -->
@if (session()->has('message'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg">
        <div class="flex items-center justify-between">
            <span>{{ session('message') }}</span>
            <button @click="show = false" class="ml-4 text-green-500 hover:text-green-700">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>
@endif

@if (session()->has('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="fixed top-4 right-4 z-50 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg">
        <div class="flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button @click="show = false" class="ml-4 text-red-500 hover:text-red-700">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>
@endif
